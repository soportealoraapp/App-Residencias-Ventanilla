<?php declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;

class Home extends Controller
{
    public function index()
    {
        if (! session('user_id')) {
            return view('portal/home');
        }

        $roles = (array) (session('roles') ?? []);
        if (in_array('administrador', $roles, true) || in_array('operador_ventanilla', $roles, true)) {
            return redirect()->to('/admin/dashboard');
        }
        if (in_array('juez_calificador', $roles, true)) {
            return redirect()->to('/juez');
        }

        return redirect()->to('/portal/tramites');
    }
}
