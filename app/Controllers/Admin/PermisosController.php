<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Libraries\PermisosOperador;
use App\Models\AuditoriaModel;
use CodeIgniter\Controller;

/**
 * Configuración de permisos del operador de ventanilla.
 *
 * Exclusiva del administrador: decide qué secciones del panel puede usar
 * (habilita o deshabilita solicitudes, usuarios, catálogos, App Tránsito, etc.).
 * Los cambios aplican de inmediato para todas las sesiones de ventanilla.
 * Las bajas definitivas (eliminar) no son configurables: siempre son del admin.
 */
class PermisosController extends Controller
{
    protected AuditoriaModel $auditoriaModel;

    public function __construct()
    {
        helper(['form', 'url']);
        $this->auditoriaModel = new AuditoriaModel();
    }

    public function index()
    {
        return view('admin/permisos_operador', [
            'definiciones' => PermisosOperador::definiciones(),
            'permisos'     => PermisosOperador::todos(),
        ]);
    }

    public function guardar()
    {
        $entrada = $this->request->getPost('permisos');

        $mapa = [];
        foreach (PermisosOperador::claves() as $clave) {
            $mapa[$clave] = is_array($entrada) && array_key_exists($clave, $entrada);
        }

        PermisosOperador::guardar($mapa);

        $this->auditoriaModel->registrar(
            'permisos_operador',
            null,
            'actualizar',
            (int) (session('user_id') ?? 0) ?: null,
            ['permisos' => $mapa]
        );

        return redirect()->to('/admin/permisos-operador')->with('message', 'Los permisos del operador se guardaron y aplican de inmediato.');
    }
}
