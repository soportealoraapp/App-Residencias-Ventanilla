<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use CodeIgniter\Controller;
use App\Models\ConvocatoriaModel;
use App\Models\SolicitudModel;
use App\Models\SolicitudDatoModel;
use App\Models\AuditoriaModel;
use App\Libraries\EstadoSolicitudService;
use App\Libraries\SupabaseStorage;
use Config\Services;

/**
 * Convocatorias de otorgamiento de concesiones (trámite UR-TT-T-01).
 *
 * Una convocatoria es la ventanilla por la que varios ciudadanos presentan su
 * expediente y el Ayuntamiento designa un solo ganador. Antes de esta clase el
 * panel solo tenía la pantalla de evaluación apuntando a la convocatoria #1 por
 * hardcode, así que sin esa fila exacta la sección entera se veía vacía y sin
 * ninguna forma de crear, corregir o cancelar una convocatoria.
 */
class ConvocatoriasController extends Controller
{
    /** Bucket y prefijo donde viven las bases oficiales en PDF. */
    private const BUCKET_BASES = 'documentos';
    private const RUTA_BASES   = 'convocatorias';

    /** Máximo de las bases: 10 MB, el mismo techo que los formatos oficiales. */
    private const BASE_MAX_BYTES = 10485760;

    public function index()
    {
        $convocatoriaModel = new ConvocatoriaModel();

        $filtros = [
            'filtro' => (string) ($this->request->getGet('filtro') ?? ''),
            'q'      => trim((string) ($this->request->getGet('q') ?? '')),
        ];
        if (! array_key_exists($filtros['filtro'], ConvocatoriaModel::FILTROS)) {
            $filtros['filtro'] = '';
        }

        // El modelo resuelve la ventana en SQL para que el conteo y la página
        // coincidan con lo que realmente se muestra.
        [$paginas, $total]     = $convocatoriaModel->buscar($filtros);
        $ids                   = array_map(static fn ($c): int => (int) $c->id, $paginas);
        $postulaciones         = $convocatoriaModel->conteoPostulaciones($ids);

        return view('admin/convocatorias/index', [
            'convocatorias' => $paginas,
            'total'         => $total,
            'postulaciones' => $postulaciones,
            'filtros'       => $filtros,
            'conteos'       => (new ConvocatoriaModel())->conteos(),
            'paginador'     => $convocatoriaModel->pager,
        ]);
    }

    public function formNuevo()
    {
        $hoy = date('Y-m-d');

        $convocatoria = (object) [
            'id'                     => null,
            'titulo'                 => '',
            'fecha_publicacion'      => $hoy,
            'periodo_registro_inicio' => $hoy,
            'periodo_registro_fin'   => date('Y-m-d', strtotime('+30 days')),
            'bases'                  => '',
            'estatus'                => ConvocatoriaModel::VIGENTE,
            'bases_archivo'          => null,
            'bases_nombre'           => null,
            'bases_mime'             => null,
            'bases_tamano'           => null,
        ];

        return view('admin/convocatorias/form', [
            'convocatoria' => $convocatoria,
            'modo'         => 'nuevo',
        ]);
    }

    public function guardar()
    {
        $convocatoriaModel = new ConvocatoriaModel();

        $extra = $this->extraerDatos();
        if ($extra['errores'] !== []) {
            return redirect()->back()->withInput()->with('errors', $extra['errores']);
        }
        $data = $extra['data'];

        // Las bases se suben antes de insertar: el PDF no depende del ID, así
        // que si el storage falla no queda una convocatoria a medias.
        $bases = $this->subirBases();
        if ($bases['error'] !== null) {
            return redirect()->back()->withInput()->with('error', $bases['error']);
        }
        if ($bases['datos'] !== null) {
            $data = array_merge($data, $bases['datos']);
        }

        $data['created_by'] = (int) (Services::session()->get('user_id') ?? 0) ?: null;

        $convocatoriaModel->insert($data);
        $nuevoId = (int) $convocatoriaModel->getInsertID();

        $this->auditar('crear', $nuevoId, $data);

        return redirect()->to(site_url('admin/convocatorias/' . $nuevoId . '/evaluacion'))
            ->with('message', "Convocatoria #{$nuevoId} publicada. Ya pueden postular expedientes en el portal ciudadano.");
    }

    public function formEditar(int $id)
    {
        $convocatoria = (new ConvocatoriaModel())->find($id);
        if (! $convocatoria) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('La convocatoria solicitada no existe.');
        }

        return view('admin/convocatorias/form', [
            'convocatoria' => $convocatoria,
            'modo'         => 'editar',
        ]);
    }

    public function actualizar(int $id)
    {
        $convocatoriaModel = new ConvocatoriaModel();

        $anterior = $convocatoriaModel->find($id);
        if (! $anterior) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('La convocatoria solicitada no existe.');
        }

        $extra = $this->extraerDatos();
        if ($extra['errores'] !== []) {
            return redirect()->back()->withInput()->with('errors', $extra['errores']);
        }
        $data = $extra['data'];

        $reemplazo = (string) ($anterior->bases_archivo ?? '');

        if ($this->request->getPost('quitar_bases_pdf') === '1') {
            $data = array_merge($data, [
                'bases_archivo' => null, 'bases_nombre' => null, 'bases_mime' => null, 'bases_tamano' => null,
            ]);
        } else {
            $bases = $this->subirBases();
            if ($bases['error'] !== null) {
                return redirect()->back()->withInput()->with('error', $bases['error']);
            }
            if ($bases['datos'] !== null) {
                $data = array_merge($data, $bases['datos']);
            } else {
                // Sin archivo nuevo y sin quite: el PDF vigente sigue siendo válido.
                $reemplazo = '';
            }
        }

        $convocatoriaModel->update($id, $data);

        // El PDF viejo ya no tiene referencia en la BD: se elimina para no
        // dejar huérfanos ocupando espacio.
        if ($reemplazo !== '') {
            (new SupabaseStorage())->eliminar(self::BUCKET_BASES, [$reemplazo]);
        }

        $this->auditar('editar', $id, $data);

        return redirect()->to(site_url('admin/convocatorias'))
            ->with('message', "Convocatoria #{$id} actualizada correctamente.");
    }

    /**
     * Baja definitiva. Se niega si alguien ya postuló: borrar una convocatoria
     * con expedientes dejaría las solicitudes huérfanas y el dictamen sin
     * sustento, así que en ese caso la opción correcta es Anularla.
     */
    public function eliminar(int $id)
    {
        $convocatoriaModel = new ConvocatoriaModel();

        $convocatoria = $convocatoriaModel->find($id);
        if (! $convocatoria) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('La convocatoria solicitada no existe.');
        }

        $postulaciones = $convocatoriaModel->conteoPostulaciones([$id])[$id] ?? 0;
        if ($postulaciones > 0) {
            return redirect()->to(site_url('admin/convocatorias'))
                ->with('error', "La convocatoria #{$id} tiene {$postulaciones} postulación(es). No se puede eliminar: cámbiele el estatus a Anulada o Cerrada desde Editar.");
        }

        $convocatoriaModel->delete($id);

        if (! empty($convocatoria->bases_archivo)) {
            (new SupabaseStorage())->eliminar(self::BUCKET_BASES, [(string) $convocatoria->bases_archivo]);
        }

        $this->auditar('eliminar', $id, (array) $convocatoria);

        return redirect()->to(site_url('admin/convocatorias'))
            ->with('message', "Convocatoria #{$id} eliminada. El cambio quedó registrado en auditoría.");
    }

    /**
     * Cuadro comparativo de postulantes y designación del ganador. Busca la
     * convocatoria exacta: si no existe responde 404 en lugar de mostrar otra,
     * que es lo que hacía que el panel pareciera tener datos inventados.
     */
    public function evaluacion(int $id)
    {
        $convocatoria = (new ConvocatoriaModel())->find($id);
        if (! $convocatoria) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('No existe la convocatoria #' . $id . '.');
        }

        $solicitudModel     = new SolicitudModel();
        $solicitudDatoModel = new SolicitudDatoModel();

        $solicitudes = [];
        foreach ($solicitudModel->where('convocatoria_id', $convocatoria->id)->orderBy('id', 'ASC')->findAll() as $sol) {
            $solicitudes[] = [
                'solicitud' => $sol,
                'datos'     => $solicitudDatoModel->porSolicitudAgrupado((int) $sol->id),
            ];
        }

        return view('admin/convocatorias/evaluacion', [
            'convocatoria' => $convocatoria,
            'solicitudes'  => $solicitudes,
        ]);
    }

    public function seleccionar(int $convocatoriaId)
    {
        $userId      = (int) (Services::session()->get('user_id') ?? 0);
        $solicitudId = (int) $this->request->getPost('solicitud_id');

        $convocatoria = (new ConvocatoriaModel())->find($convocatoriaId);
        if (! $convocatoria) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('No existe la convocatoria #' . $convocatoriaId . '.');
        }

        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($solicitudId);
        if (! $solicitud || (int) $solicitud->convocatoria_id !== $convocatoriaId) {
            return redirect()->back()->with('error', 'La solicitud no pertenece a esta convocatoria.');
        }

        $estadoService = new EstadoSolicitudService();
        foreach ($solicitudModel->where('convocatoria_id', $convocatoriaId)->findAll() as $participante) {
            $participanteId = (int) $participante->id;
            if ($participanteId === $solicitudId) {
                $estadoService->cambiarEstatus($participanteId, 'Seleccionado', $userId, 'Dictamen comparativo: Seleccionado como GANADOR de la Convocatoria #' . $convocatoriaId);
            } else {
                $estadoService->cambiarEstatus($participanteId, 'No seleccionado', $userId, 'Dictamen comparativo: No seleccionado en Convocatoria #' . $convocatoriaId);
            }
        }

        (new AuditoriaModel())->registrar('convocatorias', $convocatoriaId, 'seleccionar_ganador', $userId, [
            'solicitud_id' => $solicitudId,
            'folio'        => (string) ($solicitud->folio ?? ''),
        ]);

        return redirect()->to(site_url('admin/convocatorias/' . $convocatoriaId . '/evaluacion'))
            ->with('message', 'Participante ' . (string) ($solicitud->folio ?? '') . ' seleccionado como GANADOR de la convocatoria.');
    }

    /** Abre el PDF de bases oficiales mediante una URL firmada del bucket. */
    public function bases(int $id)
    {
        $convocatoria = (new ConvocatoriaModel())->find($id);
        if (! $convocatoria || empty($convocatoria->bases_archivo)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Esta convocatoria no tiene bases oficiales en PDF.');
        }

        $url = (new SupabaseStorage())->urlFirmada(self::BUCKET_BASES, (string) $convocatoria->bases_archivo);
        if ($url === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('No se pudo abrir el PDF de bases oficiales.');
        }

        return redirect()->to($url);
    }

    /**
     * Reglas, validación y datos del formulario. La comprobación de que el fin
     * del registro no sea anterior a su inicio se hace a mano porque CI4 no
     * tiene ninguna regla entre dos campos, y va al mismo listado de errores
     * que produce el validator para que la vista los muestre igual.
     *
     * @return array{data: array<string, mixed>, errores: array<string, string>}
     */
    private function extraerDatos(): array
    {
        $etiquetas = [
            'titulo'                  => 'Título de la convocatoria',
            'fecha_publicacion'       => 'Fecha de publicación',
            'periodo_registro_inicio' => 'Inicio del periodo de registro',
            'periodo_registro_fin'    => 'Fin del periodo de registro',
            'bases'                   => 'Bases (resumen)',
            'estatus'                 => 'Estatus',
        ];

        $reglas = [
            'titulo' => [
                'rules' => 'required|max_length[200]',
                'label' => $etiquetas['titulo'],
            ],
            'fecha_publicacion' => [
                'rules' => 'required|valid_date',
                'label' => $etiquetas['fecha_publicacion'],
            ],
            'periodo_registro_inicio' => [
                'rules' => 'required|valid_date',
                'label' => $etiquetas['periodo_registro_inicio'],
            ],
            'periodo_registro_fin' => [
                'rules' => 'required|valid_date',
                'label' => $etiquetas['periodo_registro_fin'],
            ],
            'bases' => [
                'rules' => 'permit_empty|max_length[5000]',
                'label' => $etiquetas['bases'],
            ],
            'estatus' => [
                'rules' => 'required|in_list[' . implode(',', array_keys(ConvocatoriaModel::ESTATUS_OPCIONES)) . ']',
                'label' => $etiquetas['estatus'],
            ],
        ];

        $errores = [];
        if (! $this->validate($reglas)) {
            $errores = $this->validator->getErrors();
        } else {
            $inicio = (string) $this->request->getPost('periodo_registro_inicio');
            $fin    = (string) $this->request->getPost('periodo_registro_fin');

            if (strcmp($fin, $inicio) < 0) {
                $errores['periodo_registro_fin'] = $etiquetas['periodo_registro_fin']
                    . ' no puede ser anterior al ' . strtolower($etiquetas['periodo_registro_inicio']) . '.';
            }
        }

        if ($errores !== []) {
            return ['data' => [], 'errores' => $errores];
        }

        $basesTexto = trim((string) ($this->request->getPost('bases') ?? ''));

        return [
            'data' => [
                'titulo'                  => trim((string) $this->request->getPost('titulo')),
                'fecha_publicacion'       => (string) $this->request->getPost('fecha_publicacion'),
                'periodo_registro_inicio' => (string) $this->request->getPost('periodo_registro_inicio'),
                'periodo_registro_fin'    => (string) $this->request->getPost('periodo_registro_fin'),
                'bases'                   => $basesTexto === '' ? null : $basesTexto,
                'estatus'                 => (string) $this->request->getPost('estatus'),
            ],
            'errores' => [],
        ];
    }

    /**
     * Sube el PDF de bases si el usuario adjuntó uno. No usar archivo también
     * es válido: las bases pueden quedar redactadas en el campo de texto.
     *
     * @return array{datos: array<string, mixed>|null, error: string|null}
     */
    private function subirBases(): array
    {
        $archivo = $this->request->getFile('bases_pdf');
        if ($archivo === null || ! $archivo->isValid()) {
            return ['datos' => null, 'error' => null];
        }

        if ($archivo->getClientMimeType() !== 'application/pdf') {
            return ['datos' => null, 'error' => 'Las bases oficiales deben ser un archivo PDF.'];
        }

        if ($archivo->getSize() > self::BASE_MAX_BYTES) {
            return ['datos' => null, 'error' => 'El PDF de bases no debe exceder 10 MB.'];
        }

        $rutaInterna = self::RUTA_BASES . '/conv_' . bin2hex(random_bytes(8)) . '.pdf';
        $contenido   = file_get_contents($archivo->getTempName());

        if ($contenido === false || ! (new SupabaseStorage())->subir(self::BUCKET_BASES, $rutaInterna, $contenido, 'application/pdf')) {
            return ['datos' => null, 'error' => 'No se pudo subir el PDF de bases oficiales al almacenamiento.'];
        }

        return [
            'datos' => [
                'bases_archivo' => $rutaInterna,
                'bases_nombre'  => $archivo->getName(),
                'bases_mime'    => 'application/pdf',
                'bases_tamano'  => (int) $archivo->getSize(),
            ],
            'error' => null,
        ];
    }

    private function auditar(string $accion, int $id, array $detalle): void
    {
        (new AuditoriaModel())->registrar('convocatorias', $id, $accion, (int) (Services::session()->get('user_id') ?? 0), $detalle);
    }
}
