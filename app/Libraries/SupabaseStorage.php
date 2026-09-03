<?php declare(strict_types=1);

namespace App\Libraries;

class SupabaseStorage
{
    protected string $baseUrl;
    protected string $anonKey;
    protected string $serviceKey;

    public function __construct()
    {
        $this->baseUrl    = ($_ENV['SUPABASE_URL'] ?? '') . '/storage/v1';
        $this->anonKey    = $_ENV['SUPABASE_ANON_KEY'] ?? '';
        $this->serviceKey = $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '';
    }

    public function subir(string $bucket, string $path, string $contenido, string $mimeType, bool $upsert = true): bool
    {
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
        $supabaseUrl = $_ENV['SUPABASE_URL'] ?? '';
        return $supabaseUrl . '/storage/v1/object/public/' . $bucket . '/' . $path;
    }

    public function descargar(string $bucket, string $path): ?string
    {
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
            log_message('error', 'SupabaseStorage cURL error: ' . $error);
            return false;
        }

        return ['status' => $statusCode, 'body' => $responseBody];
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
