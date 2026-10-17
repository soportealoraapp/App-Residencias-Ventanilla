<?php declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\SupabaseStorage;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Entrega archivos guardados en el almacenamiento local (cuando Supabase no
 * está configurado). Funciona como las URLs firmadas de Supabase: el enlace lo
 * genera SupabaseStorage::urlFirmada() después de que el controlador que lo
 * pide ya verificó los permisos, y aquí solo se valida la firma y la caducidad.
 */
class ArchivosLocalesController extends Controller
{
    private const MIME = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
    ];

    public function servir(string $bucket): ResponseInterface
    {
        $storage = new SupabaseStorage();
        $path    = (string) $this->request->getGet('p');
        $expira  = (int) $this->request->getGet('exp');
        $firma   = (string) $this->request->getGet('sig');

        if (! $storage->esLocal() || $path === '' || $expira < time()
            || ! hash_equals($storage->firmaLocal($bucket, $path, $expira), $firma)) {
            return $this->response->setStatusCode(403)->setBody('Enlace inválido o caducado.');
        }

        $archivo = $storage->rutaArchivoLocal($bucket, $path);
        if ($archivo === null || ! is_file($archivo)) {
            return $this->response->setStatusCode(404)->setBody('Archivo no encontrado.');
        }

        $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

        return $this->response
            ->setContentType(self::MIME[$extension] ?? 'application/octet-stream')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($archivo) . '"')
            ->setHeader('Cache-Control', 'private, max-age=300')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((string) file_get_contents($archivo));
    }
}
