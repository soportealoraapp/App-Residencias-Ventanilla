<?php declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;

class SupabaseStorage
{
    protected string $baseUrl;
    protected string $anonKey;
    protected string $serviceKey;
    protected CURLRequest $client;

    public function __construct()
    {
        $this->baseUrl     = ($_ENV['SUPABASE_URL'] ?? '') . '/storage/v1';
        $this->anonKey     = $_ENV['SUPABASE_ANON_KEY'] ?? '';
        $this->serviceKey  = $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '';
        $this->client      = \Config\Services::curlrequest([
            'baseURI' => '',
            'timeout' => 30,
        ]);
    }

    public function subir(string $bucket, string $path, string $contenido, string $mimeType, bool $upsert = true): bool
    {
        $url = $this->baseUrl . '/object/' . $bucket . '/' . $path;

        $headers = [
            'apikey'       => $this->anonKey,
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'Content-Type'  => $mimeType,
        ];

        if ($upsert) {
            $headers['x-upsert'] = 'true';
        }

        try {
            $response = $this->client->post($url, [
                'body'    => $contenido,
                'headers' => $headers,
            ]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (\Throwable $e) {
            log_message('error', 'SupabaseStorage::subir failed: ' . $e->getMessage());
            return false;
        }
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

        try {
            $response = $this->client->delete($url, [
                'json'    => ['prefixes' => $paths],
                'headers' => [
                    'apikey'       => $this->anonKey,
                    'Authorization' => 'Bearer ' . $this->serviceKey,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (\Throwable $e) {
            log_message('error', 'SupabaseStorage::eliminar failed: ' . $e->getMessage());
            return false;
        }
    }

    public function urlFirmada(string $bucket, string $path, int $expiraSegundos = 3600): ?string
    {
        $url = $this->baseUrl . '/object/sign/' . $bucket . '/' . $path;

        try {
            $response = $this->client->post($url, [
                'json'    => ['expiresIn' => $expiraSegundos],
                'headers' => [
                    'apikey'       => $this->anonKey,
                    'Authorization' => 'Bearer ' . $this->serviceKey,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $body = json_decode($response->getBody(), true);
                $signedPath = $body['signedURL'] ?? null;
                if ($signedPath !== null) {
                    return $this->extraerUrlCompleta($signedPath);
                }
            }

            return null;
        } catch (\Throwable $e) {
            log_message('error', 'SupabaseStorage::urlFirmada failed: ' . $e->getMessage());
            return null;
        }
    }

    public function urlPublica(string $bucket, string $path): string
    {
        $supabaseUrl = $_ENV['SUPABASE_URL'] ?? '';
        return $supabaseUrl . '/storage/v1/object/public/' . $bucket . '/' . $path;
    }

    public function descargar(string $bucket, string $path): ?string
    {
        $url = $this->baseUrl . '/object/' . $bucket . '/' . $path;

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'apikey'       => $this->anonKey,
                    'Authorization' => 'Bearer ' . $this->serviceKey,
                ],
                'response' => ['save_to' => null],
            ]);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return $response->getBody();
            }

            return null;
        } catch (\Throwable $e) {
            log_message('error', 'SupabaseStorage::descargar failed: ' . $e->getMessage());
            return null;
        }
    }

    public function listar(string $bucket, string $prefix = '', int $limite = 100, int $offset = 0): array
    {
        $url = $this->baseUrl . '/object/list/' . $bucket;

        try {
            $response = $this->client->post($url, [
                'json' => [
                    'prefix'  => $prefix,
                    'limit'   => $limite,
                    'offset'  => $offset,
                    'sortBy'  => ['column' => 'name', 'order' => 'asc'],
                ],
                'headers' => [
                    'apikey'       => $this->anonKey,
                    'Authorization' => 'Bearer ' . $this->serviceKey,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return json_decode($response->getBody(), true) ?? [];
            }

            return [];
        } catch (\Throwable $e) {
            log_message('error', 'SupabaseStorage::listar failed: ' . $e->getMessage());
            return [];
        }
    }

    public function existe(string $bucket, string $path): bool
    {
        $url = $this->baseUrl . '/object/info/' . $bucket . '/' . $path;

        try {
            $response = $this->client->head($url, [
                'headers' => [
                    'apikey'       => $this->anonKey,
                    'Authorization' => 'Bearer ' . $this->serviceKey,
                ],
            ]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function crearBucket(string $nombre, bool $publico = false, ?int $tamanoMaximo = null, ?array $mimeTypes = null): bool
    {
        $url = $this->baseUrl . '/bucket';

        $body = [
            'name'    => $nombre,
            'public'  => $publico,
        ];

        if ($tamanoMaximo !== null) {
            $body['file_size_limit'] = $tamanoMaximo;
        }

        if ($mimeTypes !== null) {
            $body['allowed_mime_types'] = $mimeTypes;
        }

        try {
            $response = $this->client->post($url, [
                'json'    => $body,
                'headers' => [
                    'apikey'       => $this->anonKey,
                    'Authorization' => 'Bearer ' . $this->serviceKey,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (\Throwable $e) {
            log_message('error', 'SupabaseStorage::crearBucket failed: ' . $e->getMessage());
            return false;
        }
    }

    public function listarBuckets(): array
    {
        $url = $this->baseUrl . '/bucket';

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'apikey'       => $this->anonKey,
                    'Authorization' => 'Bearer ' . $this->serviceKey,
                ],
            ]);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return json_decode($response->getBody(), true) ?? [];
            }

            return [];
        } catch (\Throwable $e) {
            return [];
        }
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
