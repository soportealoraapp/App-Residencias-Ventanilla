<?php declare(strict_types=1);

namespace App\Libraries;

/**
 * Almacenamiento de archivos (documentos de trámites, formatos, INE).
 *
 * Usa Supabase Storage cuando SUPABASE_URL y SUPABASE_SERVICE_ROLE_KEY están
 * configurados. Si no lo están (entorno local o servidor municipal sin
 * Supabase), guarda los archivos en disco dentro de writable/uploads/storage,
 * que no es accesible por web, y los entrega mediante URLs firmadas con
 * caducidad, igual que Supabase (ver ArchivosLocalesController).
 */
class SupabaseStorage
{
    /** Buckets que se pueden servir desde el almacenamiento local. */
    public const BUCKETS_LOCALES = ['documentos', 'formatos', 'ine'];

    protected string $baseUrl;
    protected string $anonKey;
    protected string $serviceKey;
    protected bool $usarLocal;
    protected string $rutaLocal;

    public function __construct()
    {
        $supabaseUrl      = trim((string) ($_ENV['SUPABASE_URL'] ?? ''));
        $this->baseUrl    = $supabaseUrl . '/storage/v1';
        $this->anonKey    = $_ENV['SUPABASE_ANON_KEY'] ?? '';
        $this->serviceKey = $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '';
        $this->usarLocal  = parse_url($supabaseUrl, PHP_URL_HOST) === null || $this->serviceKey === '';
        $this->rutaLocal  = rtrim((string) ($_ENV['STORAGE_LOCAL_PATH'] ?? (WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'storage')), '/\\');
    }

    /** true cuando los archivos se guardan en disco local en lugar de Supabase. */
    public function esLocal(): bool
    {
        return $this->usarLocal;
    }

    public function subir(string $bucket, string $path, string $contenido, string $mimeType, bool $upsert = true): bool
    {
        if ($this->usarLocal) {
            $destino = $this->rutaArchivoLocal($bucket, $path);
            if ($destino === null || (! $upsert && is_file($destino))) {
                return false;
            }
            if (! is_dir(dirname($destino)) && ! mkdir(dirname($destino), 0755, true) && ! is_dir(dirname($destino))) {
                return false;
            }
            return file_put_contents($destino, $contenido) !== false;
        }

        $url = $this->baseUrl . '/object/' . $bucket . '/' . $path;

        $headers = [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->serviceKey,
            'Content-Type: ' . $mimeType,
        ];

        if ($upsert) {
            $headers[] = 'x-upsert: true';
        }

        $result = $this->request('POST', $url, $contenido, $headers);
        return $result !== false && $result['status'] >= 200 && $result['status'] < 300;
    }

    public function subirArchivo(string $bucket, string $path, $file, string $mimeType, bool $upsert = true): bool
    {
        $contenido = file_get_contents($file->getTempName());
        if ($contenido === false) {
            return false;
        }

        return $this->subir($bucket, $path, $contenido, $mimeType, $upsert);
    }

    public function eliminar(string $bucket, array $paths): bool
    {
        if ($this->usarLocal) {
            $ok = true;
            foreach ($paths as $path) {
                $archivo = $this->rutaArchivoLocal($bucket, (string) $path);
                if ($archivo !== null && is_file($archivo)) {
                    $ok = unlink($archivo) && $ok;
                }
            }
            return $ok;
        }

        $url = $this->baseUrl . '/object/' . $bucket;

        $result = $this->request('DELETE', $url, json_encode(['prefixes' => $paths]), [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->serviceKey,
            'Content-Type: application/json',
        ]);

        return $result !== false && $result['status'] >= 200 && $result['status'] < 300;
    }

    public function urlFirmada(string $bucket, string $path, int $expiraSegundos = 3600): ?string
    {
        if ($this->usarLocal) {
            $archivo = $this->rutaArchivoLocal($bucket, $path);
            if ($archivo === null || ! is_file($archivo)) {
                return null;
            }
            $expira = time() + $expiraSegundos;
            return site_url('archivos/' . $bucket) . '?' . http_build_query([
                'p'   => $path,
                'exp' => $expira,
                'sig' => $this->firmaLocal($bucket, $path, $expira),
            ]);
        }

        $url = $this->baseUrl . '/object/sign/' . $bucket . '/' . $path;

        $result = $this->request('POST', $url, json_encode(['expiresIn' => $expiraSegundos]), [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->serviceKey,
            'Content-Type: application/json',
        ]);

        if ($result !== false && $result['status'] >= 200 && $result['status'] < 300) {
            $body = json_decode($result['body'], true);
            $signedPath = $body['signedURL'] ?? null;
            if ($signedPath !== null) {
                return $this->extraerUrlCompleta($signedPath);
            }
        }

        return null;
    }

    public function urlPublica(string $bucket, string $path): string
    {
        if ($this->usarLocal) {
            return $this->urlFirmada($bucket, $path) ?? '';
        }

        $supabaseUrl = $_ENV['SUPABASE_URL'] ?? '';
        return $supabaseUrl . '/storage/v1/object/public/' . $bucket . '/' . $path;
    }

    public function descargar(string $bucket, string $path): ?string
    {
        if ($this->usarLocal) {
            $archivo = $this->rutaArchivoLocal($bucket, $path);
            if ($archivo === null || ! is_file($archivo)) {
                return null;
            }
            $contenido = file_get_contents($archivo);
            return $contenido === false ? null : $contenido;
        }

        $url = $this->baseUrl . '/object/' . $bucket . '/' . $path;

        $result = $this->request('GET', $url, null, [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->serviceKey,
        ]);

        if ($result !== false && $result['status'] >= 200 && $result['status'] < 300) {
            return $result['body'];
        }

        return null;
    }

    public function listar(string $bucket, string $prefix = '', int $limite = 100, int $offset = 0): array
    {
        if ($this->usarLocal) {
            $dir = $this->rutaArchivoLocal($bucket, $prefix === '' ? '.' : $prefix);
            if ($dir === null || ! is_dir($dir)) {
                return [];
            }
            $nombres = array_values(array_diff(scandir($dir) ?: [], ['.', '..']));
            sort($nombres);
            return array_map(static fn (string $n) => ['name' => $n], array_slice($nombres, $offset, $limite));
        }

        $url = $this->baseUrl . '/object/list/' . $bucket;

        $body = json_encode([
            'prefix' => $prefix,
            'limit'  => $limite,
            'offset' => $offset,
            'sortBy' => ['column' => 'name', 'order' => 'asc'],
        ]);

        $result = $this->request('POST', $url, $body, [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->serviceKey,
            'Content-Type: application/json',
        ]);

        if ($result !== false && $result['status'] >= 200 && $result['status'] < 300) {
            return json_decode($result['body'], true) ?? [];
        }

        return [];
    }

    public function existe(string $bucket, string $path): bool
    {
        if ($this->usarLocal) {
            $archivo = $this->rutaArchivoLocal($bucket, $path);
            return $archivo !== null && is_file($archivo);
        }

        $url = $this->baseUrl . '/object/info/' . $bucket . '/' . $path;

        $result = $this->request('HEAD', $url, null, [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->serviceKey,
        ]);

        return $result !== false && $result['status'] >= 200 && $result['status'] < 300;
    }

    public function crearBucket(string $nombre, bool $publico = false, ?int $tamanoMaximo = null, ?array $mimeTypes = null): bool
    {
        $url = $this->baseUrl . '/bucket';

        $body = [
            'name'   => $nombre,
            'public' => $publico,
        ];

        if ($tamanoMaximo !== null) {
            $body['file_size_limit'] = $tamanoMaximo;
        }

        if ($mimeTypes !== null) {
            $body['allowed_mime_types'] = $mimeTypes;
        }

        $result = $this->request('POST', $url, json_encode($body), [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->serviceKey,
            'Content-Type: application/json',
        ]);

        return $result !== false && $result['status'] >= 200 && $result['status'] < 300;
    }

    public function listarBuckets(): array
    {
        $url = $this->baseUrl . '/bucket';

        $result = $this->request('GET', $url, null, [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->serviceKey,
        ]);

        if ($result !== false && $result['status'] >= 200 && $result['status'] < 300) {
            return json_decode($result['body'], true) ?? [];
        }

        return [];
    }

    protected function request(string $method, string $url, ?string $body, array $headers): array|false
    {
        $ch = curl_init($url);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
        ];

        if ($method === 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
            unset($options[CURLOPT_RETURNTRANSFER]);
        }

        if ($body !== null && $method !== 'GET' && $method !== 'HEAD') {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $options);

        $responseBody = curl_exec($ch);
        $statusCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error        = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false) {
            if (function_exists('log_message')) {
                \log_message('error', 'SupabaseStorage cURL error: ' . $error);
            } else {
                error_log('SupabaseStorage cURL error: ' . $error);
            }
            return false;
        }

        return ['status' => $statusCode, 'body' => $responseBody];
    }

    /**
     * Ruta absoluta del archivo en disco, o null si el bucket no está permitido
     * o la ruta intenta salir de su carpeta (p. ej. con "..").
     */
    public function rutaArchivoLocal(string $bucket, string $path): ?string
    {
        if (! in_array($bucket, self::BUCKETS_LOCALES, true)) {
            return null;
        }
        $path = str_replace('\\', '/', $path);
        if ($path === '' || str_contains($path, "\0") || str_starts_with($path, '/')) {
            return null;
        }
        foreach (explode('/', $path) as $segmento) {
            if ($segmento === '..') {
                return null;
            }
        }
        return $this->rutaLocal . DIRECTORY_SEPARATOR . $bucket . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    /** Firma HMAC de una URL local; la valida ArchivosLocalesController. */
    public function firmaLocal(string $bucket, string $path, int $expira): string
    {
        return hash_hmac('sha256', $bucket . '|' . $path . '|' . $expira, $this->secretoLocal());
    }

    /**
     * Secreto para firmar URLs locales: la clave de cifrado de la app si existe;
     * si no, una clave aleatoria generada una sola vez en el almacenamiento local.
     */
    protected function secretoLocal(): string
    {
        $clave = (string) (config('Encryption')->key ?? '');
        if ($clave !== '') {
            return $clave;
        }

        $archivo = $this->rutaLocal . DIRECTORY_SEPARATOR . '.firma';
        if (! is_file($archivo)) {
            if (! is_dir($this->rutaLocal)) {
                mkdir($this->rutaLocal, 0755, true);
            }
            file_put_contents($archivo, bin2hex(random_bytes(32)), LOCK_EX);
        }
        return trim((string) file_get_contents($archivo));
    }

    protected function extraerUrlCompleta(string $signedPath): string
    {
        if (str_starts_with($signedPath, 'http')) {
            return $signedPath;
        }

        $supabaseUrl = $_ENV['SUPABASE_URL'] ?? '';
        return $supabaseUrl . $signedPath;
    }
}
