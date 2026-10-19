<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Libraries\DesgloseSistema;
use CodeIgniter\Controller;

/**
 * Desglose detallado de todo lo que ocurre en el sistema.
 *
 * Pantalla exclusiva del administrador: concentra métricas de solicitudes,
 * dinero, flujo de estatus, tiempos de atención, documentos, cuentas, bitácora
 * de auditoría, verificaciones físicas, app de tránsito y catálogos. Cada número
 * es un enlace que abre el listado ya filtrado, así que el desglose nunca es un
 * callejón sin salida.
 */
class DesgloseController extends Controller
{
    public function index()
    {
        $desglose = new DesgloseSistema();

        return view('admin/desglose', [
            'kpis'       => $desglose->kpis(),
            'porEstatus' => $desglose->porEstatus(),
            'porTramite' => $desglose->porTramite(),
            'porCanal'   => $desglose->porCanal(),
            'serie'      => $desglose->serieMensual(12),
            'flujo'      => $desglose->flujoEstatus(),
            'tiempos'    => $desglose->tiempos(),
            'vigencias'  => $desglose->vigencias(),
            'documentos' => $desglose->documentos(),
            'cuentas'    => $desglose->cuentas(),
            'auditoria'  => $desglose->auditoria(),
            'transito'   => $desglose->appTransito(),
            'verif'      => $desglose->verificaciones(),
            'catalogos'  => $desglose->catalogos(),
            'alertas'    => $desglose->alertas(),
            'generado'   => date('d/m/Y H:i'),
        ]);
    }
}
