<?php declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\AuditoriaModel;
use App\Libraries\CuentaCiudadanoService;
use Config\Services;
use Psr\Log\LoggerInterface;

class AuthController extends Controller
{
    protected UserModel $userModel;
    protected RoleModel $roleModel;
    protected AuditoriaModel $auditoriaModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
        $this->auditoriaModel = new AuditoriaModel();
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

        $username = (string) $this->request->getPost('username');
        $password = (string) $this->request->getPost('password');
        $ip       = $this->request->getIPAddress();

        // Limita la fuerza bruta por IP. Se apoya en auditoria (BD) porque el
        // Throttler de CI4 no persiste en entornos sin estado como Vercel.
        if (ENVIRONMENT !== 'testing' && $this->auditoriaModel->intentosFallidosPorIp($ip) >= 8) {
            return redirect()->back()->withInput()->with('errors', [
                'credenciales' => 'Demasiados intentos fallidos. Espera unos minutos e inténtalo de nuevo.',
            ]);
        }

        $user = $this->userModel
            ->groupStart()
                ->where('username', $username)
                ->orWhere('email', $username)
            ->groupEnd()
            ->first();

        // Verifica siempre contra un hash (real o señuelo) para igualar el tiempo
        // de respuesta y no delatar si el usuario existe.
        $hashParaVerificar = $user?->password_hash
            ?? '$2y$10$EHgK/vUWPycfFKsgTmpnZehdoMfpOuqjcMUd8T9wOebEyWhBKT3GO';

        // Una cuenta sin contraseña (alta en ventanilla) nunca entra con login, aunque
        // alguien conozca el texto del hash señuelo.
        $sinContrasena = $user !== null && trim((string) ($user->password_hash ?? '')) === '';

        if ($user === null || ! password_verify($password, $hashParaVerificar) || $sinContrasena) {
            $this->auditoriaModel->registrar('users', null, 'login_fallido', null, [
                'metodo' => 'username_o_email',
                'ip'     => $ip,
            ]);
            return redirect()->back()->withInput()->with('errors', ['credenciales' => 'Usuario o contraseña incorrectos.']);
        }

        if (isset($user->activo) && (int) $user->activo !== 1) {
            $this->auditoriaModel->registrar('users', (int) $user->id, 'login_cuenta_inactiva', null, [
                'ip' => $ip,
            ]);
            return redirect()->back()->withInput()->with('errors', ['credenciales' => 'Tu cuenta está desactivada. Acude a la ventanilla de Tránsito y Transporte.']);
        }

        $roles = $this->userModel->conRoles((int) $user->id);

        $session = Services::session();
        // Previene la fijación de sesión: emite un nuevo ID al autenticarse.
        $session->regenerate(true);
        $session->set([
            'user_id'        => (int) $user->id,
            'username'       => $user->username,
            'nombre_completo' => $user->nombre_completo,
            'roles'          => $roles,
        ]);

        $this->auditoriaModel->registrar('users', (int) $user->id, 'login_exitoso', (int) $user->id, [
            'roles' => $roles,
        ]);

        // Conserva la prioridad histórica del panel administrativo para cuentas
        // con varios roles. Una cuenta exclusivamente juez entra a su panel.
        if (in_array('administrador', $roles, true) || in_array('operador_ventanilla', $roles, true)) {
            return redirect()->to('/admin/dashboard');
        }

        if (in_array('juez_calificador', $roles, true)) {
            return redirect()->to('/juez');
        }

        return redirect()->to('/portal/dashboard');
    }

    public function register()
    {
        return view('auth/register');
    }

    public function attemptRegister()
    {
        normalizar_campos_mayusculas($this->request, ['curp', 'rfc']);

        // COB-08 y auditoría A1: una cuenta que ventanilla dio de alta sin
        // acceso se reclama SOLO con el código de activación impreso en el
        // acuse. La CURP no basta, porque se deduce de datos públicos. Con el
        // código la persona conserva los trámites que ya tenía.
        $curp          = CuentaCiudadanoService::normalizarCurp((string) $this->request->getPost('curp'));
        $codigo        = CuentaCiudadanoService::normalizarCodigo((string) $this->request->getPost('codigo_activacion'));
        $cuentaService = new CuentaCiudadanoService();
        $sinAcceso     = null;
        $ip            = $this->request->getIPAddress();

        if ($codigo !== '') {
            if (ENVIRONMENT !== 'testing' && $this->auditoriaModel->registrosPorIp($ip, 60, 'codigo_activacion_fallido') >= 10) {
                return redirect()->back()->withInput()->with('errors', [
                    'codigo_activacion' => 'Demasiados intentos con códigos incorrectos. Espera una hora o acude a la ventanilla.',
                ]);
            }
            $sinAcceso = $cuentaService->cuentaPorCodigoActivacion($codigo);
            if ($sinAcceso === null) {
                $this->auditoriaModel->registrar('users', null, 'codigo_activacion_fallido', null, ['ip' => $ip]);
                return redirect()->back()->withInput()->with('errors', [
                    'codigo_activacion' => 'El código de activación no es válido o ya venció. Pide uno nuevo en la ventanilla.',
                ]);
            }
            $curpCuenta = (string) ($sinAcceso->curp ?? '');
            if ($curp !== '' && $curpCuenta !== '' && $curp !== $curpCuenta) {
                return redirect()->back()->withInput()->with('errors', [
                    'curp' => 'La CURP no coincide con la registrada en ventanilla para este código.',
                ]);
            }
        }

        if ($curp !== '') {
            foreach ($cuentaService->buscarPorCurp($curp) as $existente) {
                if ($sinAcceso !== null && (int) $existente->id === (int) $sinAcceso->id) {
                    continue;
                }
                if (trim((string) ($existente->password_hash ?? '')) !== '') {
                    return redirect()->back()->withInput()->with('errors', [
                        'curp' => 'Ya existe una cuenta con esta CURP. Inicia sesión o recupera tu contraseña.',
                    ]);
                }
                if ((int) ($existente->activo ?? 1) !== 1) {
                    return redirect()->back()->withInput()->with('errors', [
                        'curp' => 'La cuenta asociada a esta CURP está desactivada. Acude a la ventanilla de Tránsito y Transporte.',
                    ]);
                }
                return redirect()->back()->withInput()->with('errors', [
                    'codigo_activacion' => 'Esta CURP ya tiene trámites registrados en ventanilla. Escribe el código de activación impreso en tu acuse o pide uno en la ventanilla.',
                ]);
            }
        }

        // Al reclamar una cuenta ya se capturó el correo en ventanilla: la
        // comprobación de unicidad excluye esa misma fila para no bloquear el alta.
        // El formato de la regla es is_unique[tabla.campo,campo_a_ignorar,valor].
        $reglaEmail = $sinAcceso === null
            ? 'required|valid_email|max_length[180]|is_unique[users.email]'
            : 'required|valid_email|max_length[180]|is_unique[users.email,id,' . (int) $sinAcceso->id . ']';

        $rules = [
            // 18 caracteres: 4 letras, 6 dígitos, H/M, 5 letras, homoclave y dígito verificador.
            // Opcional: sin CURP la cuenta funciona igual, solo no se liga al historial de ventanilla.
            'curp'     => ['rules' => 'permit_empty|exact_length[18]|regex_match[' . CuentaCiudadanoService::REGEX_CURP . ']', 'label' => 'CURP'],
            'nombre'   => ['rules' => 'required|min_length[2]|max_length[80]', 'label' => 'Nombre(s)'],
            'apellido' => ['rules' => 'required|min_length[2]|max_length[120]', 'label' => 'Apellido(s)'],
            'telefono' => ['rules' => 'required|min_length[7]|max_length[20]', 'label' => 'Teléfono'],
            'estado'   => ['rules' => 'required|min_length[2]|max_length[60]', 'label' => 'Estado'],
            'ciudad'   => ['rules' => 'required|min_length[2]|max_length[60]', 'label' => 'Ciudad / Municipio'],
            'domicilio' => ['rules' => 'required|min_length[5]|max_length[200]', 'label' => 'Dirección'],
            'email'    => ['rules' => $reglaEmail, 'label' => 'Correo electrónico'],
            'username' => ['rules' => 'required|alpha_numeric|min_length[5]|max_length[40]|is_unique[users.username]', 'label' => 'Nombre de usuario'],
            'password' => ['rules' => 'required|min_length[8]|max_length[100]|regex_match[/^(?=.*[A-Za-z])(?=.*\d).+$/]', 'label' => 'Contraseña', 'errors' => ['regex_match' => 'La contraseña debe incluir al menos una letra y un número.']],
            'password_confirm' => ['rules' => 'required|matches[password]', 'label' => 'Confirmar contraseña'],
            'rfc'      => ['rules' => 'permit_empty|min_length[12]|max_length[13]|regex_match[/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/]', 'label' => 'RFC'],
            'acepto_terminos' => ['rules' => 'required', 'label' => 'Términos y condiciones'],
            // INE opcional: solo se valida si la persona decide adjuntarla ahora.
            'ine_frente'  => ['rules' => 'permit_empty|mime_in[ine_frente,image/jpeg,image/png]|max_size[ine_frente,5120]', 'label' => 'INE (frente)'],
            'ine_reverso' => ['rules' => 'permit_empty|mime_in[ine_reverso,image/jpeg,image/png]|max_size[ine_reverso,5120]', 'label' => 'INE (reverso)'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Anti abuso: frena el alta automatizada masiva desde una misma IP.
        if (ENVIRONMENT !== 'testing' && $this->auditoriaModel->registrosPorIp($ip) >= 5) {
            return redirect()->back()->withInput()->with('errors', [
                'registro' => 'Has enviado demasiados registros seguidos. Espera un momento o acude a la ventanilla.',
            ]);
        }

        $nombre   = trim((string) $this->request->getPost('nombre'));
        $apellido = trim((string) $this->request->getPost('apellido'));

        $data = [
            'nombre_completo' => $nombre . ' ' . $apellido,
            'curp'            => $curp !== '' ? $curp : null,
            'apellido'        => $apellido,
            'email'           => $this->request->getPost('email'),
            'username'        => $this->request->getPost('username'),
            'password_hash'   => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'telefono'        => $this->request->getPost('telefono'),
            'estado'          => $this->request->getPost('estado'),
            'ciudad'          => $this->request->getPost('ciudad'),
            'domicilio'       => $this->request->getPost('domicilio'),
            'rfc'             => $this->request->getPost('rfc') !== '' ? strtoupper((string) $this->request->getPost('rfc')) : null,
            'activo'          => 1,
        ];

        if ($sinAcceso !== null) {
            if (! $cuentaService->habilitarAcceso((int) $sinAcceso->id, $data)) {
                return redirect()->back()->withInput()->with('errors', [
                    'registro' => 'No fue posible vincular tu cuenta con los trámites de ventanilla. Intenta de nuevo o acude a la ventanilla.',
                ]);
            }

            $this->guardarIneOpcional((int) $sinAcceso->id);

            return redirect()->to('/auth/login')
                ->with('message', 'Vinculamos los trámites que ya tenías registrados en ventanilla. Ya puedes iniciar sesión.');
        }

        if (! $this->userModel->insert($data)) {
            return redirect()->back()->withInput()->with('errors', ['registro' => 'No fue posible crear tu cuenta. Intenta de nuevo.']);
        }

        $userId = (int) $this->userModel->getInsertID();
        if ($userId <= 0) {
            return redirect()->back()->withInput()->with('errors', ['registro' => 'No fue posible crear tu cuenta. Intenta de nuevo.']);
        }

        // El autorregistro SOLO produce cuentas de ciudadano; nunca de agente,
        // operador o administrador (esos los da de alta el personal).
        if ($this->userModel->asignarRol($userId, 'ciudadano')) {
            $this->auditoriaModel->registrar('users', $userId, 'asignar_rol', null, ['rol' => 'ciudadano']);
        }

        $this->auditoriaModel->registrar('users', $userId, 'registro_ciudadano', null, [
            'username'    => $data['username'],
            'rol_inicial' => 'ciudadano',
            'ip'          => $ip,
        ]);

        $this->guardarIneOpcional($userId);

        return redirect()->to('/auth/login')->with('message', 'Cuenta creada con éxito. Ya puedes iniciar sesión.');
    }

    /**
     * Guarda la INE solo si el ciudadano la adjuntó durante el registro.
     */
    protected function guardarIneOpcional(int $userId): void
    {
        $guardado = [];

        foreach (['ine_frente', 'ine_reverso'] as $campo) {
            $archivo = $this->request->getFile($campo);
            if ($archivo === null || ! $archivo->isValid()) {
                continue;
            }

            $ext = strtolower($archivo->getExtension()) === 'jpeg' ? 'jpg' : strtolower($archivo->getExtension());
            $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $ext;
            $contenido = @file_get_contents($archivo->getTempName());
            if ($contenido === false) {
                continue;
            }

            $destino = $userId . '/' . $nombreArchivo;
            try {
                $storage = new \App\Libraries\SupabaseStorage();
                $storage->subir('ine', $destino, $contenido, $archivo->getMimeType() ?: 'image/jpeg');
                $guardado[$campo] = $destino;
            } catch (\Throwable $e) {
                log_message('error', 'No se pudo guardar la INE durante el registro: ' . $e->getMessage());
            }
        }

        if ($guardado !== []) {
            $this->userModel->update($userId, $guardado);
        }
    }

    public function logout()
    {
        $userId = (int) Services::session()->get('user_id');
        if ($userId > 0) {
            $this->auditoriaModel->registrar('users', $userId, 'logout', $userId, null);
        }
        Services::session()->destroy();
        return redirect()->to('/auth/login');
    }

    public function forgot()
    {
        return view('auth/forgot');
    }

    public function attemptForgot()
    {
        $email   = strtolower(trim((string) $this->request->getPost('email')));
        $ip      = $this->request->getIPAddress();
        $mensaje = 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.';

        // Auditoría B5: límite por IP y por correo, contado en la tabla de
        // auditoría (Vercel no tiene caché persistente). Del correo se guarda
        // solo su hash. Al rebasar el límite se responde igual que siempre,
        // para no delatar si la cuenta existe, pero no se envía nada.
        $correoHash = $email === '' ? '' : hash('sha256', $email);
        if ($this->auditoriaModel->contarRecientes('recuperacion_pedida', 'ip', $ip, 60) >= 5
            || $this->auditoriaModel->contarRecientes('recuperacion_pedida', 'correo', $correoHash, 60) >= 3) {
            $this->auditoriaModel->registrar('users', null, 'recuperacion_limitada', null, ['ip' => $ip, 'correo' => $correoHash]);

            return redirect()->back()->with('message', $mensaje);
        }
        $this->auditoriaModel->registrar('users', null, 'recuperacion_pedida', null, ['ip' => $ip, 'correo' => $correoHash]);

        $user = $email === '' ? null : $this->userModel->where('email', $email)->first();

        if ($user !== null) {
            $token = bin2hex(random_bytes(20));
            // El enlace vale 1 hora (antes 24 h).
            $expira = date('Y-m-d H:i:s', time() + 3600);

            // Se guarda solo el hash: una fuga de la BD no revelaría enlaces válidos.
            $this->userModel->update($user->id, [
                'reset_token'   => hash('sha256', $token),
                'reset_expira'  => $expira,
            ]);

            $this->auditoriaModel->registrar('users', (int) $user->id, 'recuperacion_solicitada', null, [
                'canal' => 'correo',
            ]);

            $resetUrl = site_url('/auth/reset/' . $token);

            $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">';
            $html .= '<div style="background:#0d6efd;padding:20px;text-align:center;border-radius:8px 8px 0 0;">';
            $html .= '<h1 style="color:#fff;margin:0;font-size:20px;">Ventanilla Digital Uriangato</h1>';
            $html .= '</div>';
            $html .= '<div style="background:#f8f9fa;padding:30px;border:1px solid #dee2e6;">';
            $html .= '<h2 style="color:#333;margin-top:0;">Restablecer tu contraseña</h2>';
            $html .= '<p style="color:#555;">Hola <strong>' . esc(trim((string) ($user->nombre_completo ?? '')) !== '' ? $user->nombre_completo : $user->email) . '</strong>,</p>';
            $html .= '<p style="color:#555;">Recibimos una solicitud para restablecer la contraseña de tu cuenta en la Ventanilla Digital de Uriangato.</p>';
            $html .= '<p style="color:#555;">Haz clic en el siguiente botón para crear una nueva contraseña:</p>';
            $html .= '<div style="text-align:center;margin:30px 0;">';
            $html .= '<a href="' . $resetUrl . '" style="background:#0d6efd;color:#fff;padding:14px 32px;text-decoration:none;border-radius:6px;font-weight:bold;display:inline-block;">Restablecer contraseña</a>';
            $html .= '</div>';
            $html .= '<p style="color:#999;font-size:12px;">Si no solicitaste este cambio, puedes ignorar este mensaje. El enlace expirará en 1 hora.</p>';
            $html .= '<p style="color:#999;font-size:12px;">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>';
            $html .= '<p style="color:#999;font-size:11px;word-break:break-all;">' . $resetUrl . '</p>';
            $html .= '</div>';
            $html .= '<div style="text-align:center;padding:15px;color:#999;font-size:11px;">Ventanilla Digital de Uriangato, Guanajuato</div>';
            $html .= '</div>';

            $emailService = \Config\Services::email();
            $emailService->setTo($user->email);
            $emailService->setSubject('Restablecer tu contraseña - Ventanilla Digital Uriangato');
            $emailService->setMessage($html);

            // Si el correo no sale, la persona ve el mismo aviso de siempre (no se
            // delata si la cuenta existe), pero queda registro para que sistemas
            // lo detecte: en la bitácora de auditoría y en writable/logs.
            if (! $emailService->send(false)) {
                log_message('error', 'Recuperación de contraseña: no se pudo enviar el correo. ' . strip_tags((string) $emailService->printDebugger(['headers'])));
                $this->auditoriaModel->registrar('users', (int) $user->id, 'recuperacion_correo_fallido', null, ['ip' => $ip]);
            }
        }

        return redirect()->back()->with('message', $mensaje);
    }

    public function reset(?string $token = null)
    {
        $user = null;
        if ($token !== null) {
            $user = $this->userModel
                ->where('reset_token', hash('sha256', $token))
                ->where('reset_expira >=', date('Y-m-d H:i:s'))
                ->first();
        }

        // CodeIgniter 4.5+ entrega el método en mayúsculas ('POST').
        if (strtoupper($this->request->getMethod()) === 'POST') {
            if ($user === null) {
                return redirect()->back()->with('errors', ['token' => 'Token inválido o expirado.']);
            }

            $rules = [
                'password'         => 'required|min_length[8]|max_length[100]|regex_match[/^(?=.*[A-Za-z])(?=.*\d).+$/]',
                'password_confirm' => 'required|matches[password]',
            ];

            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $actualizado = $this->userModel->update($user->id, [
                'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
                'reset_token'   => null,
                'reset_expira'  => null,
            ]);

            if ($actualizado) {
                $this->auditoriaModel->registrar('users', (int) $user->id, 'contrasena_restablecida', null, [
                    'origen' => 'recuperacion',
                ]);
            }

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
