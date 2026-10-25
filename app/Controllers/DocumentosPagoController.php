<?php declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\HojaPagoService;
use App\Models\SolicitudModel;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Documentos de apoyo al pago (COB-05).
 *
 * La hoja de ayuda se imprime desde el panel administrativo, desde el portal
 * del ciudadano y desde el panel del juez. La ruta solo exige sesión; el
 * permiso fino se resuelve aquí, porque un ciudadano únicamente puede imprimir
 * los documentos de sus propios trámites y las multas no le pertenecen a nadie
 * hasta que acude a pagar.
 */
class DocumentosPagoController extends Controller
{
    public function hojaPagoSolicitud(string $folio)
    {
        $solicitud = $this->buscarSolicitud($folio);
        if (! $this->puedeVerSolicitud($solicitud)) {
            return redirect()->to($this->inicio())->with('error', 'No tienes permiso para imprimir ese documento.');
        }

        return $this->pintar('solicitud', (int) $solicitud->id);
    }

    public function hojaPagoBoleta(int $id)
    {
        if (! $this->tieneAlgunRol(['juez_calificador', 'administrador', 'operador_ventanilla'])) {
            return redirect()->to($this->inicio())->with('error', 'No tienes permiso para imprimir ese documento.');
        }

        return $this->pintar('boleta', $id);
    }

    // ─── Internos ─────────────────────────────────────────────────────────────

    private function pintar(string $origen, int $id)
    {
        $servicio = new HojaPagoService();
        $hoja     = $origen === 'boleta'
            ? $servicio->paraBoleta($id)
            : $servicio->paraSolicitud($id);

        if ($hoja === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('documentos/hoja_pago', [
            'origen'  => $origen,
            'hoja'    => $hoja,
            'lugares' => $servicio->lugaresDePago(),
            // La consulta pública por folio es COB-07 y todavía no existe: la
            // hoja imprime la instrucción sin enlace para no dejar un href muerto.
            'volver'  => $this->inicio(),
        ]);
    }

    /** Acepta el folio del trámite o su id numérico. */
    private function buscarSolicitud(string $folio): object
    {
        $modelo    = new SolicitudModel();
        $solicitud = ctype_digit($folio)
            ? $modelo->find((int) $folio)
            : $modelo->findByFolio($folio);

        if ($solicitud === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $solicitud;
    }

    private function puedeVerSolicitud(object $solicitud): bool
    {
        if ($this->tieneAlgunRol(['administrador', 'operador_ventanilla'])) {
            return true;
        }

        return $this->tieneAlgunRol(['ciudadano'])
            && (int) $solicitud->ciudadano_id === (int) session('user_id');
    }

    /** @param list<string> $roles */
    private function tieneAlgunRol(array $roles): bool
    {
        $propios = (array) (session('roles') ?? []);

        return array_intersect($roles, $propios) !== [];
    }

    /** A dónde regresar cuando el documento no está permitido. */
    private function inicio(): string
    {
        if ($this->tieneAlgunRol(['administrador', 'operador_ventanilla'])) {
            return '/admin/solicitudes';
        }
        if ($this->tieneAlgunRol(['juez_calificador'])) {
            return '/juez';
        }

        return '/portal/dashboard';
    }
}
