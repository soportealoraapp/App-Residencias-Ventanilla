<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\DocumentoModel;
use CodeIgniter\HTTP\Files\UploadedFile;

class DocumentoUploader
{
    protected SupabaseStorage $storage;

    protected array $mimePermitidos = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    protected int $tamanoMaximo = 10485760;

    public function __construct()
    {
        $this->storage = new SupabaseStorage();
    }

    public function subir(UploadedFile $file, string $tipoDocumento, int $solicitudId, int $usuarioId): ?object
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            return null;
        }

        if (ENVIRONMENT !== 'testing' && ! $file->isValid()) {
            return null;
        }

        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $this->mimePermitidos, true)) {
            return null;
        }

        if ($file->getSize() > $this->tamanoMaximo) {
            return null;
        }

        $extension = $this->obtenerExtensionPorMime($mimeType);
        if ($extension === null) {
            return null;
        }

        $nombreInterno = $this->generarUuidCompleto() . '.' . $extension;
        $rutaSupabase = 'solicitud_' . $solicitudId . '/' . $nombreInterno;

        $contenido = file_get_contents($file->getTempName());
        if ($contenido === false) {
            return null;
        }

        $hash = hash('sha256', $contenido);
        if ($hash === false) {
            return null;
        }

        $ok = $this->storage->subir('documentos', $rutaSupabase, $contenido, $mimeType);
        if (! $ok) {
            return null;
        }

        $documentoModel = new DocumentoModel();

        $datos = [
            'solicitud_id'   => $solicitudId,
            'usuario_id'     => $usuarioId,
            'tipo_documento' => $tipoDocumento,
            'nombre_original' => $file->getClientName(),
            'ruta_interna'   => $rutaSupabase,
            'mime_type'      => $mimeType,
            'tamano_bytes'   => $file->getSize(),
            'hash_sha256'    => $hash,
            'fecha_carga'    => date('Y-m-d H:i:s'),
        ];

        $id = $documentoModel->insert($datos);
        if ($id === false) {
            return null;
        }

        return $documentoModel->find($id);
    }

    protected function obtenerExtensionPorMime(string $mime): ?string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            default           => null,
        };
    }

    protected function generarUuidCompleto(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
