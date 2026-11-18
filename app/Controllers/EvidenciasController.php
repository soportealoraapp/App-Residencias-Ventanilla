<?php declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\EvidenciaBoleta;
use App\Libraries\SupabaseStorage;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Sirve las fotos de evidencia de las boletas solo al personal con sesión
 * (filtro de rol en la ruta). El archivo vive en almacenamiento privado; aquí
 * se lee y se entrega, sin exponer URLs públicas ni firmadas.
 */
class EvidenciasController extends Controller
{
    public function ver(string $carpeta, string $archivo): ResponseInterface
    {
        $ruta = EvidenciaBoleta::rutaEnBucket($carpeta, $archivo);
        if ($ruta === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $contenido = (new SupabaseStorage())->descargar(EvidenciaBoleta::BUCKET, $ruta);
        if ($contenido === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader('Content-Type', EvidenciaBoleta::mimeDe($archivo))
            ->setHeader('Content-Disposition', 'inline; filename="' . $archivo . '"')
            ->setHeader('Cache-Control', 'private, max-age=300')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($contenido);
    }
}
