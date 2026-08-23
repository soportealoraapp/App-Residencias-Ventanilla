<?php declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;

class Home extends Controller
{
    public function index()
    {
        if (session('user_id')) {
            return redirect()->to('/portal/tramites');
        }

        return view('portal/home');
    }
}
