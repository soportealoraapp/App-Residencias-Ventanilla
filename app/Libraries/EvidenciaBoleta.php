<?php declare(strict_types=1);

namespace App\Libraries;

/**
 * Fotos de evidencia de las boletas de tránsito (auditoría A4 y M2).
 *
 * - Se guardan en el bucket privado `evidencias` de Supabase Storage, o en
 *   `writable/` cuando no hay Supabase. Nunca en `public/`.
 * - El nombre del archivo lleva 128 bits aleatorios: no se puede adivinar.
 * - Se valida el tipo real del contenido (no el que dice el cliente) y el tamaño.
 * - La app recibe una URL del propio servidor (/evidencias/{folio}/{archivo})
 *   que solo abre el personal con sesión (EvidenciasController).
 * - Al sincronizar o al pintar, solo se aceptan URLs con esa forma exacta, así
 *   que un dispositivo no puede colar `javascript:` ni enlaces externos.
 */
class EvidenciaBoleta
{
    public const BUCKET = 'evidencias';
    public const TAMANO_MAXIMO = 5 * 1024 * 1024;

    private const TIPOS = ['placa', 'contexto', 'documento', 'evidencia'];
    private const EXTENSIONES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const MIME_POR_EXTENSION = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

    public function __construct(private readonly SupabaseStorage $storage = new SupabaseStorage())
    {
    }

    public static function carpetaDeFolio(string $folio): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '_', trim($folio)) ?? '';
    }

    /**
     * Valida y guarda la imagen. Devuelve la URL interna para la app, o lanza
     * \InvalidArgumentException con un mensaje para el agente.
     */
    public function guardar(string $folio, string $tipo, string $contenido): string
    {
        $carpeta = self::carpetaDeFolio($folio);
        if ($carpeta === '') {
            throw new \InvalidArgumentException('La evidencia necesita el folio de la boleta.');
        }
        if ($contenido === '') {
            throw new \InvalidArgumentException('La imagen está vacía.');
        }
        if (strlen($contenido) > self::TAMANO_MAXIMO) {
            throw new \InvalidArgumentException('La imagen supera el máximo de 5 MB.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contenido) ?: '';
        $ext  = self::EXTENSIONES[$mime] ?? null;
        if ($ext === null || @getimagesizefromstring($contenido) === false) {
            throw new \InvalidArgumentException('Solo se aceptan imágenes JPG, PNG o WEBP.');
        }

        $tipo    = in_array($tipo, self::TIPOS, true) ? $tipo : 'evidencia';
        $archivo = $tipo . '_' . bin2hex(random_bytes(16)) . '.' . $ext;
        $ruta    = $carpeta . '/' . $archivo;

        if (! $this->storage->subir(self::BUCKET, $ruta, $contenido, $mime, false)) {
            throw new \RuntimeException('No fue posible guardar la evidencia.');
        }

        return site_url('evidencias/' . $carpeta . '/' . $archivo);
    }

    /**
     * Ruta relativa segura (/evidencias/{carpeta}/{archivo}) si la URL es una
     * evidencia de esta boleta guardada por el servidor; null en cualquier otro
     * caso (URL externa, javascript:, otro folio, nombre inventado…).
     */
    public static function rutaSegura(?string $url, ?string $folio = null, bool $exigirHostPropio = false): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $partes = parse_url($url);
        if ($partes === false) {
            return null;
        }
        $esquema = strtolower((string) ($partes['scheme'] ?? ''));
        if (isset($partes['scheme']) && ! in_array($esquema, ['http', 'https'], true)) {
            return null;
        }
        if (isset($partes['query']) || isset($partes['fragment']) || isset($partes['user'])) {
            return null;
        }
        // Al recibirla de la app debe ser de este mismo servidor. Al pintarla
        // basta la ruta, para que un cambio de dominio no oculte las fotos.
        if ($exigirHostPropio && isset($partes['host'])
            && strcasecmp((string) $partes['host'], (string) parse_url(base_url(), PHP_URL_HOST)) !== 0) {
            return null;
        }

        $patron = '#^/evidencias/([A-Za-z0-9_-]+)/((?:' . implode('|', self::TIPOS) . ')_[a-f0-9]{32}\.(?:jpg|png|webp))$#';
        if (preg_match($patron, (string) ($partes['path'] ?? ''), $m) !== 1) {
            return null;
        }
        if ($folio !== null && $m[1] !== self::carpetaDeFolio($folio)) {
            return null;
        }

        return '/evidencias/' . $m[1] . '/' . $m[2];
    }

    /** Ruta dentro del bucket a partir de los segmentos de la URL, o null si no es válida. */
    public static function rutaEnBucket(string $carpeta, string $archivo): ?string
    {
        $ruta = self::rutaSegura('/evidencias/' . $carpeta . '/' . $archivo);

        return $ruta === null ? null : substr($ruta, strlen('/evidencias/'));
    }

    public static function mimeDe(string $archivo): string
    {
        $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

        return self::MIME_POR_EXTENSION[$ext] ?? 'application/octet-stream';
    }
}
