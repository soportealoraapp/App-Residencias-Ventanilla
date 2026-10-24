<?php declare(strict_types=1);

namespace App\Controllers\Juez;

use CodeIgniter\Controller;

/**
 * Entrada de solo lectura para el juez calificador.
 *
 * COB-09 todavía no define el estado que identifica una boleta pendiente. Por
 * eso no se consulta ni se clasifica boletas hasta que COB-10 formalice ese
 * contrato; mostrar todas como pendientes produciría decisiones incorrectas.
 */
class DashboardController extends Controller
{
    public function index()
    {
        return view('juez/dashboard');
    }

    public function boletas()
    {
        return view('juez/boletas');
    }
}