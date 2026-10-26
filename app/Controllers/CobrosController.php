<?php declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\CobroException;
use App\Libraries\CobroService;
use App\Libraries\CorteCajaService;
use App\Models\CobroModel;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Listado de cobros (COB-02): Cobros → Listado de cobros.
 *
 * Lo usan dos paneles con las mismas acciones: el del administrador y la
 * ventanilla (/admin/cobros) y el del juez calificador (/juez/cobros). Cada
 * panel conserva su menú; la base de la URL decide cuál se muestra.
 *
 * Botones por estatus:
 *   pendiente → Cobrar, Mandar a completar, Cancelar
 *   aprobado  → recibo (COB-06)
 *   cancelado → solo consulta, con el motivo
 */
class CobrosController extends Controller
{
    private const POR_PAGINA = 20;

    public function index()
    {
        $filtros = [
            'q'       => trim((string) $this->request->getGet('q')),
            'estatus' => (string) $this->request->getGet('estatus'),
            'origen'  => (string) $this->request->getGet('origen'),
            'desde'   => $this->fecha($this->request->getGet('desde')),
            'hasta'   => $this->fecha($this->request->getGet('hasta')),
        ];
        if (! in_array($filtros['estatus'], ['', 'pendiente', 'por_completar', 'aprobado', 'cancelado'], true)) {
            $filtros['estatus'] = '';
        }
        if (! in_array($filtros['origen'], ['', 'solicitud', 'boleta'], true)) {
            $filtros['origen'] = '';
        }

        $listado = $this->filtrar(new CobroModel(), $filtros)
            ->select('cobros.*, solicitudes.folio AS solicitud_folio, solicitudes.tramite AS solicitud_tramite, boletas_infracciones.folio AS boleta_folio')
            ->join('solicitudes', 'solicitudes.id = cobros.solicitud_id', 'left')
            ->join('boletas_infracciones', 'boletas_infracciones.id = cobros.boleta_id', 'left')
            ->orderBy("CASE WHEN cobros.estatus = 'pendiente' THEN 0 ELSE 1 END", '', false)
            ->orderBy('COALESCE(cobros.fecha_cobro, cobros.created_at)', 'DESC', false)
            ->orderBy('cobros.id', 'DESC');

        $cobros = $listado->paginate(self::POR_PAGINA);

        return view('cobros/index', [
            'layout'    => $this->layout(),
            'base'      => $this->base(),
            'filtros'   => $filtros,
            'cobros'    => $cobros,
            'pager'     => $listado->pager,
            'resumen'   => $this->resumen(),
            'lugares'   => CobroService::LUGARES_PAGO,
            'lugarPorDefecto' => $this->esJuez() ? 'juez_calificador' : 'ventanilla',
        ]);
    }

    public function ver(int $id)
    {
        $cobro = (new CobroModel())
            ->select('cobros.*, solicitudes.folio AS solicitud_folio, solicitudes.tramite AS solicitud_tramite, solicitudes.estatus AS solicitud_estatus, boletas_infracciones.folio AS boleta_folio, cobrador.nombre_completo AS cobrado_por_nombre')
            ->join('solicitudes', 'solicitudes.id = cobros.solicitud_id', 'left')
            ->join('boletas_infracciones', 'boletas_infracciones.id = cobros.boleta_id', 'left')
            ->join('users cobrador', 'cobrador.id = cobros.cobrado_por', 'left')
            ->where('cobros.id', $id)
            ->first();
        if ($cobro === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $historial = \Config\Database::connect()->table('cobro_movimientos m')
            ->select('m.*, u.nombre_completo AS usuario_nombre, u.username AS usuario_username')
            ->join('users u', 'u.id = m.usuario_id', 'left')
            ->where('m.cobro_id', $id)
            ->orderBy('m.id', 'ASC')
            ->get()->getResult();

        return view('cobros/ver', [
            'layout'    => $this->layout(),
            'base'      => $this->base(),
            'cobro'     => $cobro,
            'historial' => $historial,
            'lugares'   => CobroService::LUGARES_PAGO,
            'lugarPorDefecto' => $this->esJuez() ? 'juez_calificador' : 'ventanilla',
        ]);
    }

    public function cobrar(int $id): RedirectResponse
    {
        return $this->ejecutar($id, function (CobroService $servicio, int $usuarioId) use ($id): string {
            $lugar = (string) $this->request->getPost('lugar_pago');
            $referencia = trim((string) $this->request->getPost('referencia_pago'));
            if ($lugar === 'banbajio') {
                // COB-04: con BanBajío se registra el comprobante y se valida su importe.
                $importe = str_replace([',', '$', ' '], '', (string) $this->request->getPost('importe_pagado'));
                if (! is_numeric($importe)) {
                    throw new CobroException('Escriba el importe que aparece en el comprobante.');
                }
                $servicio->registrarPagoBancario($id, $referencia, (string) $this->request->getPost('fecha_pago_banco'), (float) $importe, $usuarioId);
            } else {
                $servicio->marcarCobrado($id, $lugar, $referencia, $usuarioId);
            }
            $cobro = (new CobroModel())->find($id);

            return 'Cobro registrado. Recibo ' . $cobro->folio_recibo . ' por $' . number_format((float) $cobro->total, 2) . '.';
        });
    }

    public function completar(int $id): RedirectResponse
    {
        return $this->ejecutar($id, function (CobroService $servicio, int $usuarioId) use ($id): string {
            $servicio->mandarACompletar($id, (string) $this->request->getPost('motivo'), $usuarioId);

            return 'El cobro quedó pendiente por completar.';
        });
    }

    public function cancelar(int $id): RedirectResponse
    {
        return $this->ejecutar($id, function (CobroService $servicio, int $usuarioId) use ($id): string {
            $servicio->cancelar($id, (string) $this->request->getPost('motivo'), $usuarioId);

            return 'Cobro cancelado.';
        });
    }

    /**
     * Pago en BanBajío (COB-04): se busca el cobro por la referencia impresa en
     * la hoja de pago y se registra el comprobante.
     */
    public function banco()
    {
        $referencia = trim((string) $this->request->getGet('referencia'));
        $cobro = null;
        $aviso = null;
        if ($referencia !== '') {
            $encontrado = (new CobroService())->buscarPorReferencia($referencia);
            if ($encontrado === null) {
                $aviso = service('referenciaBancaria')->esValida($referencia)
                    ? 'No hay ningún cobro con esa referencia.'
                    : 'La referencia no es válida. Revise que esté completa y sin errores de captura.';
            } else {
                $cobro = (new CobroModel())
                    ->select('cobros.*, solicitudes.folio AS solicitud_folio, boletas_infracciones.folio AS boleta_folio, boletas_infracciones.fecha_infraccion')
                    ->join('solicitudes', 'solicitudes.id = cobros.solicitud_id', 'left')
                    ->join('boletas_infracciones', 'boletas_infracciones.id = cobros.boleta_id', 'left')
                    ->where('cobros.id', $encontrado->id)
                    ->first();
            }
        }

        return view('cobros/banco', [
            'layout'     => $this->layout(),
            'base'       => $this->base(),
            'referencia' => $referencia,
            'cobro'      => $cobro,
            'aviso'      => $aviso,
        ]);
    }

    /** Corte de caja del día, la semana, el mes o un rango (COB-03). */
    public function corte()
    {
        [$filtros, $rango, $cobradorId] = $this->filtrosCorte();
        $servicio = new CorteCajaService();

        return view('cobros/corte', [
            'layout'     => $this->layout(),
            'base'       => $this->base(),
            'filtros'    => $filtros,
            'rango'      => $rango,
            'corte'      => $servicio->corte($rango['desde'], $rango['hasta'], $cobradorId),
            'cobradores' => $servicio->cobradores(),
            'periodos'   => CorteCajaService::PERIODOS,
        ]);
    }

    /** El mismo corte en CSV para abrirlo en Excel. */
    public function corteCsv()
    {
        [, $rango, $cobradorId] = $this->filtrosCorte();
        $corte = (new CorteCajaService())->corte($rango['desde'], $rango['hasta'], $cobradorId);

        $salida = fopen('php://temp', 'r+');
        fputcsv($salida, ['Recibo', 'Folio de cobro', 'Fecha de cobro', 'Nombre', 'Concepto', 'Tipo', 'Monto base', 'Descuento', 'Recargo', 'Condonado', 'Total', 'Lugar de pago', 'Referencia', 'Cobró']);
        foreach ($corte['cobros'] as $c) {
            fputcsv($salida, [
                $c->folio_recibo, $c->folio, $c->fecha_cobro, $c->nombre_pagador, $c->concepto, $c->tipo_concepto,
                number_format((float) $c->monto_base, 2, '.', ''), number_format((float) $c->descuento, 2, '.', ''),
                number_format((float) $c->recargo, 2, '.', ''), number_format((float) $c->condonado, 2, '.', ''),
                number_format((float) $c->total, 2, '.', ''), $c->lugar, $c->referencia_pago, $c->cobrador,
            ]);
        }
        fputcsv($salida, ['', '', '', '', '', 'Total del corte', '', '', '', '', number_format($corte['total'], 2, '.', '')]);
        rewind($salida);
        $csv = stream_get_contents($salida);
        fclose($salida);

        $nombre = 'corte-caja-' . $rango['desde']->format('Y-m-d') . ($rango['hasta'] != $rango['desde'] ? '-a-' . $rango['hasta']->format('Y-m-d') : '') . '.csv';

        // BOM para que Excel lea bien los acentos.
        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $nombre . '"')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    // ─── Internos ─────────────────────────────────────────────────────────────

    /** @return array{0: array<string, string>, 1: array, 2: ?int} */
    private function filtrosCorte(): array
    {
        $filtros = [
            'periodo'  => (string) $this->request->getGet('periodo'),
            'fecha'    => $this->fecha($this->request->getGet('fecha')),
            'desde'    => $this->fecha($this->request->getGet('desde')),
            'hasta'    => $this->fecha($this->request->getGet('hasta')),
            'cobrador' => ctype_digit((string) $this->request->getGet('cobrador')) ? (string) $this->request->getGet('cobrador') : '',
        ];
        if (! array_key_exists($filtros['periodo'], CorteCajaService::PERIODOS)) {
            $filtros['periodo'] = 'dia';
        }
        $rango = CorteCajaService::rango($filtros['periodo'], $filtros['fecha'], $filtros['desde'], $filtros['hasta']);

        return [$filtros, $rango, $filtros['cobrador'] !== '' ? (int) $filtros['cobrador'] : null];
    }

    /** @param callable(CobroService, int): string $accion */
    private function ejecutar(int $id, callable $accion): RedirectResponse
    {
        $volver = $this->volver($id);
        try {
            $mensaje = $accion(new CobroService(), (int) session('user_id'));
        } catch (CobroException $e) {
            return redirect()->to($volver)->with('error', $e->getMessage());
        }

        return redirect()->to($volver)->with('message', $mensaje);
    }

    /** Regresa a donde se presionó el botón (listado con sus filtros o detalle). */
    private function volver(int $id): string
    {
        $destino = (string) $this->request->getPost('volver');
        $base = $this->base();
        if ($destino !== '' && str_starts_with($destino, $base) && ! str_contains($destino, '//')) {
            return $destino;
        }

        return $base . '/' . $id;
    }

    private function filtrar(CobroModel $modelo, array $filtros): CobroModel
    {
        if ($filtros['estatus'] === 'por_completar') {
            $modelo->where('cobros.estatus', 'pendiente')->where('cobros.pendiente_documentos', 1);
        } elseif ($filtros['estatus'] !== '') {
            $modelo->where('cobros.estatus', $filtros['estatus']);
        }
        if ($filtros['origen'] !== '') {
            $modelo->where('cobros.origen', $filtros['origen']);
        }
        if ($filtros['q'] !== '') {
            $modelo->groupStart()
                ->like('cobros.nombre_pagador', $filtros['q'], 'both', null, true)
                ->orLike('cobros.concepto', $filtros['q'], 'both', null, true)
                ->orLike('cobros.folio', $filtros['q'], 'both', null, true)
                ->orLike('cobros.folio_recibo', $filtros['q'], 'both', null, true)
                ->orLike('cobros.referencia_bancaria', preg_replace('/[\s\-.]+/', '', $filtros['q']), 'both', null, true)
                ->groupEnd();
        }
        if ($filtros['desde'] !== '') {
            $modelo->where('COALESCE(cobros.fecha_cobro, cobros.created_at) >=', $filtros['desde'] . ' 00:00:00');
        }
        if ($filtros['hasta'] !== '') {
            $modelo->where('COALESCE(cobros.fecha_cobro, cobros.created_at) <=', $filtros['hasta'] . ' 23:59:59');
        }

        return $modelo;
    }

    /** Cifras de la cabecera: lo pendiente y lo cobrado hoy. */
    private function resumen(): array
    {
        $hoy = date('Y-m-d');
        $pendientes = (new CobroModel())->selectCount('id', 'n')->selectSum('total', 'monto')
            ->where('estatus', 'pendiente')->first();
        $cobradoHoy = (new CobroModel())->selectCount('id', 'n')->selectSum('total', 'monto')
            ->where('estatus', 'aprobado')
            ->where('fecha_cobro >=', $hoy . ' 00:00:00')->where('fecha_cobro <=', $hoy . ' 23:59:59')
            ->first();

        return [
            'pendientes'       => (int) ($pendientes->n ?? 0),
            'monto_pendiente'  => (float) ($pendientes->monto ?? 0),
            'por_completar'    => (new CobroModel())->where('estatus', 'pendiente')->where('pendiente_documentos', 1)->countAllResults(),
            'cobrados_hoy'     => (int) ($cobradoHoy->n ?? 0),
            'monto_cobrado_hoy' => (float) ($cobradoHoy->monto ?? 0),
        ];
    }

    private function fecha(mixed $valor): string
    {
        $valor = trim((string) $valor);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1 ? $valor : '';
    }

    private function esJuez(): bool
    {
        return str_starts_with(uri_string(), 'juez/');
    }

    private function base(): string
    {
        return $this->esJuez() ? '/juez/cobros' : '/admin/cobros';
    }

    private function layout(): string
    {
        return $this->esJuez() ? 'layouts/juez' : 'layouts/admin';
    }
}
