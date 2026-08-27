<?php declare(strict_types=1);

namespace App\Controllers\Portal;

use CodeIgniter\Controller;
use App\Libraries\EstadoSolicitudService;
use App\Libraries\FolioGenerator;
use App\Libraries\TarifarioService;
use App\Models\ConvocatoriaModel;
use App\Models\SolicitudDatoModel;
use App\Models\SolicitudModel;
use App\Models\VerificacionFisicaModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class TramitesController extends Controller
{
    private const TRAMITES = ['UR-TT-T-01', 'UR-TT-T-02', 'UR-TT-T-03'];

    private function input(): array
    {
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : $this->request->getPost();
    }

    private function json(mixed $data, int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON($data);
    }

    private function userId(): int
    {
        $id = (int) Services::session()->get('user_id');
        if ($id <= 0 && isset($_SESSION['user_id'])) {
            $id = (int) $_SESSION['user_id'];
        }
        return $id;
    }

    public function crear(): ResponseInterface
    {
        $data = $this->input();
        $rules = [
            'tramite' => 'required|in_list[UR-TT-T-01,UR-TT-T-02,UR-TT-T-03]',
            'datos' => 'permit_empty',
            'concesion_id' => 'permit_empty|is_natural_no_zero',
            'convocatoria_id' => 'permit_empty|is_natural_no_zero',
        ];
        if (! $this->validateData($data, $rules)) {
            return $this->json(['errors' => $this->validator->getErrors()], 422);
        }

        $tramite = (string) $data['tramite'];
        $convocatoriaId = ! empty($data['convocatoria_id']) ? (int) $data['convocatoria_id'] : null;
        if ($tramite === 'UR-TT-T-01') {
            if ($convocatoriaId === null || (new ConvocatoriaModel())->vigente($convocatoriaId) === null) {
                return $this->json(['error' => 'UR-01 requiere una convocatoria vigente.'], 422);
            }
        } elseif ($convocatoriaId !== null) {
            return $this->json(['error' => 'Solo UR-01 puede asociarse a una convocatoria.'], 422);
        }

        $monto = (new TarifarioService())->calcularMonto($tramite);
        if ($monto === null) {
            return $this->json(['error' => 'No existe una tarifa vigente para el tramite.'], 422);
        }

        $solicitudModel = new SolicitudModel();
        $db = $solicitudModel->db;
        $db->transStart();
        $solicitudId = $solicitudModel->insert([
            'folio' => FolioGenerator::generar(),
            'tramite' => $tramite,
            'ciudadano_id' => $this->userId(),
            'concesion_id' => ! empty($data['concesion_id']) ? (int) $data['concesion_id'] : null,
            'convocatoria_id' => $convocatoriaId,
            'estatus' => 'Recibido',
            'monto' => $monto,
            'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);
        if ($solicitudId === false) {
            $db->transRollback();
            return $this->json(['error' => 'No fue posible crear la solicitud.'], 500);
        }
        $datos = is_array($data['datos'] ?? null) ? $data['datos'] : [];
        (new SolicitudDatoModel())->guardarDatos((int) $solicitudId, $datos);
        $db->transComplete();
        if ($db->transStatus() === false) {
            return $this->json(['error' => 'No fue posible guardar la solicitud.'], 500);
        }

        return $this->json(['solicitud' => $solicitudModel->find($solicitudId)], 201);
    }

    public function consultar(string $folio): ResponseInterface
    {
        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->findByFolio($folio);
        if ($solicitud === null) {
            return $this->json(['error' => 'Solicitud no encontrada.'], 404);
        }
        if ((int) $solicitud->ciudadano_id !== $this->userId() && ! $this->esPersonal()) {
            return $this->json(['error' => 'No autorizado.'], 403);
        }

        return $this->json([
            'solicitud' => $solicitud,
            'datos' => (new SolicitudDatoModel())->porSolicitudAgrupado((int) $solicitud->id),
            'verificaciones' => (new VerificacionFisicaModel())->porSolicitud((int) $solicitud->id),
        ]);
    }

    public function agendarVerificacion(int $solicitudId): ResponseInterface
    {
        $data = $this->input();
        if (! $this->validateData($data, ['fecha_cita' => 'required|valid_date[Y-m-d H:i:s]'])) {
            return $this->json(['errors' => $this->validator->getErrors()], 422);
        }
        $solicitud = (new SolicitudModel())->find($solicitudId);
        if ($solicitud === null || $solicitud->tramite !== 'UR-TT-T-02') {
            return $this->json(['error' => 'La solicitud no existe o no corresponde a UR-02.'], 404);
        }
        if ((int) $solicitud->ciudadano_id !== $this->userId() && ! $this->esPersonal()) {
            return $this->json(['error' => 'No autorizado.'], 403);
        }
        if (! (new EstadoSolicitudService())->cambiarEstatus($solicitudId, 'Cita agendada', $this->userId())) {
            return $this->json(['error' => 'La solicitud no permite agendar una cita.'], 422);
        }
        $model = new VerificacionFisicaModel();
        $id = $model->insert(['solicitud_id' => $solicitudId, 'fecha_cita' => $data['fecha_cita']]);

        return $this->json(['verificacion' => $model->find($id)], 201);
    }

    public function registrarResultado(int $solicitudId): ResponseInterface
    {
        $data = $this->input();
        if (! $this->validateData($data, [
            'resultado' => 'required|in_list[aprobado,rechazado]',
            'observaciones' => 'required|min_length[3]',
        ])) {
            return $this->json(['errors' => $this->validator->getErrors()], 422);
        }
        $solicitud = (new SolicitudModel())->find($solicitudId);
        if ($solicitud === null || $solicitud->tramite !== 'UR-TT-T-02') {
            return $this->json(['error' => 'La solicitud no existe o no corresponde a UR-02.'], 404);
        }
        if (! $this->esPersonal()) {
            return $this->json(['error' => 'No autorizado.'], 403);
        }
        $model = new VerificacionFisicaModel();
        $verificacion = $model->where('solicitud_id', $solicitudId)->orderBy('fecha_cita', 'DESC')->first();
        if ($verificacion === null) {
            return $this->json(['error' => 'Debe existir una cita antes del resultado.'], 422);
        }
        $nuevoEstatus = $data['resultado'] === 'aprobado' ? 'Verificado' : 'Rechazado';
        if (! (new EstadoSolicitudService())->cambiarEstatus($solicitudId, $nuevoEstatus, $this->userId(), $data['observaciones'])) {
            return $this->json(['error' => 'La solicitud no permite registrar este resultado.'], 422);
        }
        $model->update($verificacion->id, ['resultado' => $data['resultado'], 'observaciones' => $data['observaciones']]);

        return $this->json(['verificacion' => $model->find($verificacion->id)]);
    }

    public function listarConvocatoria(int $convocatoriaId): ResponseInterface
    {
        if (! $this->esPersonal()) {
            return $this->json(['error' => 'Solo el personal puede comparar solicitudes.'], 403);
        }
        $convocatoria = (new ConvocatoriaModel())->find($convocatoriaId);
        if ($convocatoria === null) {
            return $this->json(['error' => 'Convocatoria no encontrada.'], 404);
        }

        return $this->json(['convocatoria' => $convocatoria, 'solicitudes' => (new SolicitudModel())->porConvocatoria($convocatoriaId)]);
    }

    public function seleccionar(int $convocatoriaId): ResponseInterface
    {
        if (! $this->esPersonal()) {
            return $this->json(['error' => 'Solo el personal puede seleccionar.'], 403);
        }
        $data = $this->input();
        if (! $this->validateData($data, ['solicitud_id' => 'required|is_natural_no_zero'])) {
            return $this->json(['errors' => $this->validator->getErrors()], 422);
        }
        $model = new SolicitudModel();
        $solicitudes = $model->porConvocatoria($convocatoriaId);
        $seleccionadaId = (int) $data['solicitud_id'];
        $ids = array_map(static fn ($row): int => (int) $row->id, $solicitudes);
        if (! in_array($seleccionadaId, $ids, true)) {
            return $this->json(['error' => 'La solicitud no pertenece a la convocatoria.'], 422);
        }
        $service = new EstadoSolicitudService();
        $db = $model->db;
        $db->transStart();
        foreach ($solicitudes as $solicitud) {
            $id = (int) $solicitud->id;
            $solicitudActual = (new SolicitudModel())->find($id);
            if ($solicitudActual === null) {
                continue;
            }
            $estatus = $solicitudActual->estatus;
            if ($estatus === 'Recibido') {
                $service->cambiarEstatus($id, 'En revisión', $this->userId());
                $estatus = 'En revisión';
            }
            if ($estatus === 'En revisión') {
                $service->cambiarEstatus($id, 'Evaluación comparativa', $this->userId());
                $estatus = 'Evaluación comparativa';
            }
            if ($estatus === 'Evaluación comparativa') {
                $objetivo = $id === $seleccionadaId ? 'Seleccionado' : 'No seleccionado';
                $comentario = $id === $seleccionadaId
                    ? 'Seleccionado en evaluación comparativa.'
                    : 'No seleccionado en evaluación comparativa.';
                $service->cambiarEstatus($id, $objetivo, $this->userId(), $comentario);
            }
        }
        $db->transComplete();
        if ($db->transStatus() === false) {
            return $this->json(['error' => 'No fue posible registrar la evaluación comparativa.'], 500);
        }

        return $this->json(['solicitudes' => $model->porConvocatoria($convocatoriaId)]);
    }

    private function esPersonal(): bool
    {
        $userId = $this->userId();
        if ($userId > 0) {
            $userModel = new \App\Models\UserModel();
            if ($userModel->tieneRol($userId, 'administrador') || $userModel->tieneRol($userId, 'operador_ventanilla')) {
                return true;
            }
        }
        $roles = Services::session()->get('roles') ?? ($_SESSION['roles'] ?? []);
        return (bool) array_intersect(['administrador', 'operador_ventanilla'], (array) $roles);
    }
}
