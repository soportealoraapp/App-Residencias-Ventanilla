<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Libraries\SupabaseStorage;
use App\Models\AuditoriaModel;
use App\Models\UserModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Alta, edición y baja de cuentas del portal desde la ventanilla.
 *
 * El ciudadano puede autorregistrarse en /auth/register, y la ventanilla
 * (operador o administrador) también puede crear o editar su cuenta aquí para
 * la atención presencial. El operador de ventanilla solo administra cuentas de
 * ciudadano; el administrador puede además asignar roles internos y eliminar
 * cuentas. Las cuentas de AGENTES DE TRÁNSITO no se autorregistran: se dan de
 * alta exclusivamente desde el panel (App Tránsito) por el administrador.
 */
class UsuariosController extends Controller
{
    private const ROLES = [
        'ciudadano'           => 'Ciudadano',
        'operador_ventanilla' => 'Operador de ventanilla',
        'administrador'       => 'Administrador',
    ];

    protected UserModel $userModel;
    protected AuditoriaModel $auditoriaModel;

    public function __construct()
    {
        helper(['form', 'url_helper_custom']);
        $this->userModel      = new UserModel();
        $this->auditoriaModel = new AuditoriaModel();
    }

    public function index()
    {
        $q      = trim((string) ($this->request->getGet('q') ?? ''));
        $rol    = (string) ($this->request->getGet('rol') ?? '');
        $estado = (string) ($this->request->getGet('estado') ?? '');

        $model = $this->userModel->select('users.*');

        if ($q !== '') {
            $model->groupStart()
                ->like('nombre_completo', $q)
                ->orLike('username', $q)
                ->orLike('email', $q)
                ->orLike('curp', $q)
                ->groupEnd();
        }
        if (array_key_exists($rol, self::ROLES)) {
            $model->whereIn('users.id', static function ($builder) use ($rol) {
                return $builder->select('ur.user_id')
                    ->from('user_roles ur')
                    ->join('roles r', 'r.id = ur.role_id')
                    ->where('r.nombre', $rol);
            });
        }
        if ($estado === 'activo' || $estado === 'inactivo') {
            $model->where('activo', $estado === 'activo' ? 1 : 0);
        }

        $usuarios = $model->orderBy('users.id', 'DESC')->paginate(20);

        return view('admin/usuarios/index', [
            'usuarios'      => $usuarios,
            'rolesPorId'    => $this->rolesDe(array_map(static fn ($u) => (int) $u->id, $usuarios)),
            'pager'         => $this->userModel->pager,
            'filtros'       => ['q' => $q, 'rol' => $rol, 'estado' => $estado],
            'roles'         => self::ROLES,
            'conteos'       => $this->conteosCuentas(),
            'esAdmin'       => $this->esAdministrador(),
        ]);
    }

    /**
     * Totales globales para los filtros rápidos de cuentas.
     *
     * Son conteos de todo el padrón, no del resultado filtrado: los chips deben
     * decir cuántas hay antes de tocarlas, igual que en el desglose.
     *
     * @return array{total: int, activos: int, inactivos: int, roles: array<string, int>}
     */
    private function conteosCuentas(): array
    {
        $db = \Config\Database::connect();

        $conteos = [
            'total'     => $db->table('users')->countAllResults(),
            'activos'   => $db->table('users')->where('activo', 1)->countAllResults(),
            'inactivos' => $db->table('users')->where('activo', 0)->countAllResults(),
            'roles'     => [],
        ];

        $porRol = $db->table('user_roles ur')
            ->select('r.nombre AS rol, COUNT(*) AS total')
            ->join('roles r', 'r.id = ur.role_id')
            ->groupBy('r.nombre')
            ->get()
            ->getResultArray();

        foreach ($porRol as $fila) {
            $conteos['roles'][(string) $fila['rol']] = (int) $fila['total'];
        }

        return $conteos;
    }

    public function formNuevo()
    {
        return view('admin/usuarios/form', [
            'usuario'   => (object) [
                'id' => null, 'nombre' => '', 'apellido' => '', 'curp' => '', 'rfc' => '',
                'email' => '', 'username' => '', 'telefono' => '', 'estado' => 'Guanajuato',
                'ciudad' => 'Uriangato', 'domicilio' => '', 'activo' => 1,
                'ine_frente' => null, 'ine_reverso' => null,
            ],
            'rolActual' => 'ciudadano',
            'roles'     => $this->rolesAsignables(),
            'modo'      => 'nuevo',
        ]);
    }

    public function guardar()
    {
        normalizar_campos_mayusculas($this->request, ['curp', 'rfc']);

        if (! $this->validate($this->reglas(null))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $rol = $this->rolSolicitado();
        if ($rol === null) {
            return redirect()->back()->withInput()->with('errors', ['rol' => 'No tienes permiso para asignar ese rol.']);
        }

        $password = (string) $this->request->getPost('password');
        $passwordGenerada = $password === '';
        if ($passwordGenerada) {
            $password = $this->generarPassword();
        }

        $data = $this->datosFormulario();
        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);

        $this->userModel->insert($data);
        $userId = (int) $this->userModel->getInsertID();
        if ($userId <= 0) {
            return redirect()->back()->withInput()->with('error', 'No fue posible crear la cuenta.');
        }

        $this->userModel->asignarRol($userId, $rol);
        $this->guardarIne($userId);

        $this->auditoriaModel->registrar('users', $userId, 'alta_ventanilla', $this->usuarioSesion(), [
            'username' => $data['username'],
            'rol'      => $rol,
        ]);

        $mensaje = "Cuenta de {$data['nombre_completo']} creada. Usuario: {$data['username']}.";
        if ($passwordGenerada) {
            $mensaje .= " Contraseña temporal: {$password} (entrégala al ciudadano; no se volverá a mostrar).";
        }

        return redirect()->to('/admin/usuarios')->with('message', $mensaje);
    }

    public function formEditar(int $id)
    {
        $usuario = $this->userModel->find($id);
        if ($usuario === null) {
            return redirect()->to('/admin/usuarios')->with('error', 'Usuario no encontrado.');
        }
        if (! $this->puedeAdministrar($id)) {
            return redirect()->to('/admin/usuarios')->with('error', 'Solo un administrador puede editar cuentas internas.');
        }

        $usuario->nombre = $this->nombreSinApellido($usuario);
        $roles = $this->userModel->conRoles($id);

        return view('admin/usuarios/form', [
            'usuario'   => $usuario,
            'rolActual' => $roles[0] ?? 'ciudadano',
            'roles'     => $this->rolesAsignables(),
            'modo'      => 'editar',
        ]);
    }

    public function actualizar(int $id)
    {
        $actual = $this->userModel->find($id);
        if ($actual === null) {
            return redirect()->to('/admin/usuarios')->with('error', 'Usuario no encontrado.');
        }
        if (! $this->puedeAdministrar($id)) {
            return redirect()->to('/admin/usuarios')->with('error', 'Solo un administrador puede editar cuentas internas.');
        }

        normalizar_campos_mayusculas($this->request, ['curp', 'rfc']);

        if (! $this->validate($this->reglas($id))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $rol = $this->rolSolicitado();
        if ($rol === null) {
            return redirect()->back()->withInput()->with('errors', ['rol' => 'No tienes permiso para asignar ese rol.']);
        }
        if ($id === $this->usuarioSesion() && ($rol !== 'administrador' && $this->esAdministrador())) {
            return redirect()->back()->withInput()->with('errors', ['rol' => 'No puedes quitarte el rol de administrador a ti mismo.']);
        }

        $data = $this->datosFormulario();
        $password = (string) $this->request->getPost('password');
        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->userModel->update($id, $data);
        $this->reemplazarRol($id, $rol);
        $this->guardarIne($id);

        unset($data['password_hash']);
        $this->auditoriaModel->registrar('users', $id, 'editar_ventanilla', $this->usuarioSesion(), $data + [
            'rol'                 => $rol,
            'password_cambiada'   => $password !== '',
        ]);

        return redirect()->to('/admin/usuarios')->with('message', "Cuenta de {$data['nombre_completo']} actualizada.");
    }

    public function toggleEstado(int $id)
    {
        $usuario = $this->userModel->find($id);
        if ($usuario === null) {
            return redirect()->to('/admin/usuarios')->with('error', 'Usuario no encontrado.');
        }
        if ($id === $this->usuarioSesion()) {
            return redirect()->to('/admin/usuarios')->with('error', 'No puedes desactivar tu propia cuenta.');
        }
        if (! $this->puedeAdministrar($id)) {
            return redirect()->to('/admin/usuarios')->with('error', 'Solo un administrador puede modificar cuentas internas.');
        }

        $nuevo = (int) $usuario->activo === 1 ? 0 : 1;
        $this->userModel->update($id, ['activo' => $nuevo]);
        $this->auditoriaModel->registrar('users', $id, 'toggle_estado', $this->usuarioSesion(), [
            'activo_anterior' => (int) $usuario->activo,
            'activo_nuevo'    => $nuevo,
        ]);

        return redirect()->to('/admin/usuarios')
            ->with('message', "La cuenta {$usuario->username} fue " . ($nuevo === 1 ? 'activada' : 'desactivada') . '.');
    }

    public function eliminar(int $id)
    {
        if (! $this->esAdministrador()) {
            return redirect()->to('/admin/usuarios')->with('error', 'Solo un administrador puede eliminar cuentas.');
        }
        if ($id === $this->usuarioSesion()) {
            return redirect()->to('/admin/usuarios')->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $usuario = $this->userModel->find($id);
        if ($usuario === null) {
            return redirect()->to('/admin/usuarios')->with('error', 'Usuario no encontrado.');
        }

        $db = \Config\Database::connect();
        $solicitudes = $db->table('solicitudes')->where('ciudadano_id', $id)->countAllResults();
        if ($solicitudes > 0) {
            return redirect()->to('/admin/usuarios')->with(
                'error',
                "La cuenta {$usuario->username} tiene {$solicitudes} solicitud(es) registradas y no puede eliminarse; desactívala en su lugar."
            );
        }

        $db->transStart();
        $db->table('user_roles')->where('user_id', $id)->delete();
        $this->userModel->delete($id);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to('/admin/usuarios')->with('error', 'No fue posible eliminar la cuenta.');
        }

        $registro = (array) $usuario;
        unset($registro['password_hash'], $registro['reset_token']);
        $this->auditoriaModel->registrar('users', $id, 'eliminar', $this->usuarioSesion(), [
            'username' => $usuario->username,
        ], $registro);

        $paths = array_values(array_filter([$usuario->ine_frente ?? null, $usuario->ine_reverso ?? null]));
        if ($paths !== []) {
            try {
                (new SupabaseStorage())->eliminar('ine', $paths);
            } catch (\Throwable $e) {
                log_message('warning', 'No se pudo eliminar la INE del usuario {id}: {msg}', ['id' => $id, 'msg' => $e->getMessage()]);
            }
        }

        return redirect()->to('/admin/usuarios')->with('message', "Cuenta {$usuario->username} eliminada.");
    }

    /** @return array<string, array<string, string>> */
    private function reglas(?int $id): array
    {
        $unico = static fn (string $campo) => $id === null
            ? "is_unique[users.{$campo}]"
            : "is_unique[users.{$campo},id,{$id}]";

        $reglas = [
            'nombre'    => ['label' => 'Nombre(s)', 'rules' => 'required|min_length[2]|max_length[90]'],
            'apellido'  => ['label' => 'Apellido(s)', 'rules' => 'required|min_length[2]|max_length[120]'],
            'curp'      => ['label' => 'CURP', 'rules' => 'permit_empty|exact_length[18]|regex_match[/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z\d]\d$/]|' . $unico('curp'), 'errors' => ['is_unique' => 'Ya existe una cuenta con esta CURP; búscala en el listado.']],
            'rfc'       => ['label' => 'RFC', 'rules' => 'permit_empty|min_length[12]|max_length[13]|regex_match[/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/]'],
            'email'     => ['label' => 'Correo electrónico', 'rules' => 'permit_empty|valid_email|max_length[255]|' . $unico('email')],
            'username'  => ['label' => 'Nombre de usuario', 'rules' => 'required|alpha_numeric|min_length[5]|max_length[100]|' . $unico('username')],
            'password'  => ['label' => 'Contraseña', 'rules' => 'permit_empty|min_length[8]|max_length[100]'],
            'telefono'  => ['label' => 'Teléfono', 'rules' => 'permit_empty|min_length[7]|max_length[20]'],
            'estado'    => ['label' => 'Estado', 'rules' => 'permit_empty|max_length[100]'],
            'ciudad'    => ['label' => 'Ciudad / Municipio', 'rules' => 'permit_empty|max_length[150]'],
            'domicilio' => ['label' => 'Domicilio', 'rules' => 'permit_empty|max_length[250]'],
            'rol'       => ['label' => 'Rol', 'rules' => 'required|in_list[' . implode(',', array_keys(self::ROLES)) . ']'],
            'ine_frente'  => ['label' => 'INE (frente)', 'rules' => 'mime_in[ine_frente,image/jpeg,image/png]|max_size[ine_frente,5120]'],
            'ine_reverso' => ['label' => 'INE (reverso)', 'rules' => 'mime_in[ine_reverso,image/jpeg,image/png]|max_size[ine_reverso,5120]'],
        ];

        // La INE es opcional: solo se valida si realmente se adjuntó un archivo.
        foreach (['ine_frente', 'ine_reverso'] as $campo) {
            $archivo = $this->request->getFile($campo);
            if ($archivo === null || $archivo->getError() === UPLOAD_ERR_NO_FILE) {
                unset($reglas[$campo]);
            }
        }

        return $reglas;
    }

    /** @return array<string, mixed> */
    private function datosFormulario(): array
    {
        $post = fn (string $campo): ?string => ($v = trim((string) $this->request->getPost($campo))) === '' ? null : $v;

        $nombre   = (string) $post('nombre');
        $apellido = (string) $post('apellido');

        return [
            'nombre_completo' => trim($nombre . ' ' . $apellido),
            'apellido'        => $apellido,
            'curp'            => $post('curp'),
            'rfc'             => $post('rfc'),
            'email'           => $post('email'),
            'username'        => (string) $post('username'),
            'telefono'        => $post('telefono'),
            'estado'          => $post('estado'),
            'ciudad'          => $post('ciudad'),
            'domicilio'       => $post('domicilio'),
            'activo'          => $this->request->getPost('activo') === '0' ? 0 : 1,
        ];
    }

    private function guardarIne(int $userId): void
    {
        $cambios = [];
        foreach (['ine_frente', 'ine_reverso'] as $campo) {
            $archivo = $this->request->getFile($campo);
            if (! $archivo instanceof UploadedFile || ! $archivo->isValid()) {
                continue;
            }
            $ext  = $archivo->getExtension() === 'jpeg' ? 'jpg' : $archivo->getExtension();
            $ruta = $userId . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
            try {
                $ok = (new SupabaseStorage())->subir('ine', $ruta, (string) file_get_contents($archivo->getTempName()), (string) $archivo->getMimeType());
                if ($ok) {
                    $cambios[$campo] = $ruta;
                }
            } catch (\Throwable $e) {
                log_message('error', 'No se pudo subir {campo} del usuario {id}: {msg}', ['campo' => $campo, 'id' => $userId, 'msg' => $e->getMessage()]);
            }
        }
        if ($cambios !== []) {
            $this->userModel->update($userId, $cambios);
        }
    }

    private function reemplazarRol(int $userId, string $rol): void
    {
        $db = \Config\Database::connect();
        $db->table('user_roles')->where('user_id', $userId)->delete();
        $this->userModel->asignarRol($userId, $rol);
    }

    /** @param list<int> $ids @return array<int, list<string>> */
    private function rolesDe(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $filas = \Config\Database::connect()->table('user_roles ur')
            ->select('ur.user_id, r.nombre')
            ->join('roles r', 'r.id = ur.role_id')
            ->whereIn('ur.user_id', $ids)
            ->get()->getResult();

        $mapa = [];
        foreach ($filas as $fila) {
            $mapa[(int) $fila->user_id][] = $fila->nombre;
        }
        return $mapa;
    }

    /** Rol enviado en el formulario, o null si el usuario en sesión no puede asignarlo. */
    private function rolSolicitado(): ?string
    {
        $rol = (string) $this->request->getPost('rol');
        return array_key_exists($rol, $this->rolesAsignables()) ? $rol : null;
    }

    /** @return array<string, string> */
    private function rolesAsignables(): array
    {
        return $this->esAdministrador() ? self::ROLES : ['ciudadano' => self::ROLES['ciudadano']];
    }

    /** El operador de ventanilla solo puede administrar cuentas de ciudadano. */
    private function puedeAdministrar(int $userId): bool
    {
        if ($this->esAdministrador()) {
            return true;
        }
        $roles = $this->userModel->conRoles($userId);
        return $roles === [] || $roles === ['ciudadano'];
    }

    private function esAdministrador(): bool
    {
        return in_array('administrador', (array) (session('roles') ?? []), true);
    }

    private function usuarioSesion(): ?int
    {
        $id = (int) session('user_id');
        return $id > 0 ? $id : null;
    }

    private function nombreSinApellido(object $usuario): string
    {
        $completo = (string) ($usuario->nombre_completo ?? '');
        $apellido = (string) ($usuario->apellido ?? '');
        if ($apellido !== '' && str_ends_with($completo, ' ' . $apellido)) {
            return substr($completo, 0, -strlen(' ' . $apellido));
        }
        return $completo;
    }

    private function generarPassword(): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }
        return $password;
    }
}
