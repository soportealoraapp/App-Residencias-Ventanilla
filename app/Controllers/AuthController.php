<?php declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;
use App\Models\RoleModel;
use Config\Services;
use Psr\Log\LoggerInterface;

class AuthController extends Controller
{
    protected UserModel $userModel;
    protected RoleModel $roleModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
    }

    public function login()
    {
        return view('auth/login');
    }

    public function attemptLogin()
    {
        $rules = [
            'username' => [
                'rules' => 'required',
                'label' => 'Usuario o correo electrónico',
            ],
            'password' => [
                'rules' => 'required',
                'label' => 'Contraseña',
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = $this->userModel
            ->groupStart()
                ->where('username', $username)
                ->orWhere('email', $username)
            ->groupEnd()
            ->first();

        if ($user === null || ! password_verify($password, $user->password_hash)) {
            return redirect()->back()->withInput()->with('errors', ['credenciales' => 'Usuario o contraseña incorrectos.']);
        }

        $roles = $this->userModel->conRoles((int) $user->id);

        $session = Services::session();
        $session->set([
            'user_id'        => (int) $user->id,
            'username'       => $user->username,
            'nombre_completo' => $user->nombre_completo,
            'roles'          => $roles,
        ]);

        $esAdmin = false;
        foreach ($roles as $rol) {
            if (str_contains($rol, 'admin') || str_contains($rol, 'operador')) {
                $esAdmin = true;
                break;
            }
        }

        if ($esAdmin) {
            return redirect()->to('/admin/dashboard');
        }

        return redirect()->to('/portal/dashboard');
    }

    public function register()
    {
        return view('auth/register');
    }

    public function attemptRegister()
    {
        $rules = [
            'curp' => [
                'rules' => 'required|exact_length[18]|regex_match[/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z\d]{2}\d$/]',
                'label' => 'CURP',
            ],
            'nombre' => [
                'rules' => 'required|min_length[2]',
                'label' => 'Nombre(s)',
            ],
            'apellido' => [
                'rules' => 'required|min_length[2]',
                'label' => 'Apellido(s)',
            ],
            'telefono' => [
                'rules' => 'required|min_length[7]',
                'label' => 'Teléfono',
            ],
            'estado' => [
                'rules' => 'required|min_length[2]',
                'label' => 'Estado',
            ],
            'ciudad' => [
                'rules' => 'required|min_length[2]',
                'label' => 'Ciudad / Municipio',
            ],
            'domicilio' => [
                'rules' => 'required|min_length[5]',
                'label' => 'Dirección',
            ],
            'email' => [
                'rules' => 'required|valid_email|is_unique[users.email]',
                'label' => 'Correo electrónico',
            ],
            'username' => [
                'rules' => 'required|alpha_numeric|min_length[5]|is_unique[users.username]',
                'label' => 'Nombre de usuario',
            ],
            'password' => [
                'rules' => 'required|min_length[6]',
                'label' => 'Contraseña',
            ],
            'password_confirm' => [
                'rules' => 'required|matches[password]',
                'label' => 'Confirmar contraseña',
            ],
            'rfc' => [
                'rules' => 'permit_empty|exact_length[13]|regex_match[/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/]',
                'label' => 'RFC',
            ],
            'acepto_terminos' => [
                'rules' => 'required',
                'label' => 'Términos y condiciones',
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $nombre  = trim((string) $this->request->getPost('nombre'));
        $apellido = trim((string) $this->request->getPost('apellido'));

        $data = [
            'nombre_completo' => $nombre . ' ' . $apellido,
            'curp'            => strtoupper($this->request->getPost('curp')),
            'apellido'        => $apellido,
            'email'           => $this->request->getPost('email'),
            'username'        => $this->request->getPost('username'),
            'password_hash'   => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'telefono'        => $this->request->getPost('telefono'),
            'estado'          => $this->request->getPost('estado'),
            'ciudad'          => $this->request->getPost('ciudad'),
            'domicilio'       => $this->request->getPost('domicilio'),
            'rfc'             => $this->request->getPost('rfc') !== '' ? strtoupper((string) $this->request->getPost('rfc')) : null,
        ];

        $this->userModel->insert($data);
        $userId = (int) $this->userModel->getInsertID();

        $this->userModel->asignarRol($userId, 'ciudadano');

        return redirect()->to('/auth/login')->with('message', 'Registro exitoso');
    }

    public function logout()
    {
        Services::session()->destroy();
        return redirect()->to('/auth/login');
    }

    public function forgot()
    {
        return view('auth/forgot');
    }

    public function attemptForgot()
    {
        $email = $this->request->getPost('email');

        $user = $this->userModel->where('email', $email)->first();

        if ($user !== null) {
            $token = bin2hex(random_bytes(20));
            $expira = date('Y-m-d H:i:s', time() + 86400);

            $this->userModel->update($user->id, [
                'reset_token'   => $token,
                'reset_expira'  => $expira,
            ]);
        }

        return redirect()->back()->with('message', 'Se envió link de recuperación (simulado)');
    }

    public function reset(?string $token = null)
    {
        $user = null;
        if ($token !== null) {
            $user = $this->userModel
                ->where('reset_token', $token)
                ->where('reset_expira >=', date('Y-m-d H:i:s'))
                ->first();
        }

        if ($this->request->getMethod() === 'post') {
            if ($user === null) {
                return redirect()->back()->with('errors', ['token' => 'Token inválido o expirado.']);
            }

            $rules = [
                'password'         => 'required|min_length[8]',
                'password_confirm' => 'required|matches[password]',
            ];

            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $this->userModel->update($user->id, [
                'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
                'reset_token'   => null,
                'reset_expira'  => null,
            ]);

            return redirect()->to('/auth/login')->with('message', 'Contraseña actualizada correctamente.');
        }

        return view('auth/reset', ['token' => $token, 'user' => $user]);
    }

    public function terminos()
    {
        return view('auth/terminos');
    }

    public function privacidad()
    {
        return view('auth/privacidad');
    }
}
