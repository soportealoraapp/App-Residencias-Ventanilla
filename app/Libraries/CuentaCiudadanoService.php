<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\AuditoriaModel;
use App\Models\UserModel;

/**
 * Cuentas de ciudadano identificadas por CURP, para la atención presencial.
 *
 * La CURP es el identificador principal: antes de crear una cuenta en
 * ventanilla se busca por CURP y, si ya existe, se reutiliza en lugar de
 * duplicar al ciudadano.
 */
class CuentaCiudadanoService
{
    /** Formato oficial de CURP (18 caracteres). */
    public const REGEX_CURP = '/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z\d]\d$/';

    public static function normalizarCurp(string $curp): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', $curp) ?? '', 'UTF-8');
    }

    public static function curpValida(string $curp): bool
    {
        return preg_match(self::REGEX_CURP, $curp) === 1;
    }

    /**
     * Cuentas con esa CURP (sin distinguir mayúsculas). Normalmente 0 o 1; puede
     * haber más si se registraron antes de exigir CURP única.
     *
     * @return list<object>
     */
    public function buscarPorCurp(string $curp): array
    {
        $curp = self::normalizarCurp($curp);
        if ($curp === '') {
            return [];
        }

        return (new UserModel())
            ->where('curp', $curp)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Crea una cuenta de ciudadano. El usuario de acceso es la CURP en
     * minúsculas y se genera una contraseña temporal para entregarla en ventanilla.
     *
     * @param array{curp:string,nombre:string,apellido:string,telefono?:?string,email?:?string,domicilio?:?string,rfc?:?string,estado?:?string,ciudad?:?string} $datos
     *
     * @return array{id:int,username:string,password:string}
     */
    public function crear(array $datos, ?int $creadoPor = null): array
    {
        $userModel = new UserModel();
        $curp      = self::normalizarCurp($datos['curp']);
        $nombre    = trim($datos['nombre']);
        $apellido  = trim($datos['apellido']);
        $username  = $this->usernameDisponible(strtolower($curp));
        $password  = $this->generarPassword();
        $vacioANull = static fn (?string $v): ?string => ($v = trim((string) $v)) === '' ? null : $v;

        $userModel->insert([
            'username'        => $username,
            'email'           => $vacioANull($datos['email'] ?? null),
            'password_hash'   => password_hash($password, PASSWORD_DEFAULT),
            'nombre_completo' => trim($nombre . ' ' . $apellido),
            'apellido'        => $apellido,
            'curp'            => $curp,
            'rfc'             => ($rfc = $vacioANull($datos['rfc'] ?? null)) !== null ? mb_strtoupper($rfc, 'UTF-8') : null,
            'telefono'        => $vacioANull($datos['telefono'] ?? null),
            'domicilio'       => $vacioANull($datos['domicilio'] ?? null),
            'estado'          => $vacioANull($datos['estado'] ?? null) ?? 'Guanajuato',
            'ciudad'          => $vacioANull($datos['ciudad'] ?? null) ?? 'Uriangato',
            'activo'          => 1,
        ]);
        $id = (int) $userModel->getInsertID();
        if ($id <= 0) {
            throw new \RuntimeException('No fue posible crear la cuenta del ciudadano.');
        }

        $userModel->asignarRol($id, 'ciudadano');
        (new AuditoriaModel())->registrar('users', $id, 'alta_presencial_curp', $creadoPor, [
            'username' => $username,
            'curp'     => $curp,
        ]);

        return ['id' => $id, 'username' => $username, 'password' => $password];
    }

    /**
     * Registra a un ciudadano sin cuenta de acceso (COB-08).
     *
     * La persona queda como dueña de sus trámites y de su historial, pero no
     * puede entrar al portal: no tiene usuario ni contraseña. Cuando ella misma
     * se registre en línea con la misma CURP, la cuenta se habilita con
     * habilitarAcceso() en lugar de duplicarla.
     *
     * @param array{nombre:string,apellido?:?string,curp?:?string,telefono?:?string,email?:?string,domicilio?:?string,rfc?:?string,estado?:?string,ciudad?:?string} $datos
     */
    public function crearSinAcceso(array $datos, ?int $creadoPor = null): int
    {
        $nombre   = trim((string) ($datos['nombre'] ?? ''));
        $apellido = trim((string) ($datos['apellido'] ?? ''));
        if ($nombre === '') {
            throw new \RuntimeException('El nombre del ciudadano es obligatorio.');
        }

        // La CURP es opcional; si no viene se guarda NULL y nunca cadena vacía,
        // porque el registro público compara ese campo para detectar duplicados.
        $curp = self::normalizarCurp((string) ($datos['curp'] ?? ''));
        if ($curp !== '' && ! self::curpValida($curp)) {
            throw new \RuntimeException('La CURP no tiene el formato oficial de 18 caracteres.');
        }

        $vacioANull = static fn (?string $v): ?string => ($v = trim((string) $v)) === '' ? null : $v;
        $userModel  = new UserModel();

        $userModel->insert([
            'username'        => null,
            'email'           => $vacioANull($datos['email'] ?? null),
            'password_hash'   => null,
            'nombre_completo' => trim($nombre . ' ' . $apellido),
            'apellido'        => $vacioANull($apellido),
            'curp'            => $curp !== '' ? $curp : null,
            'rfc'             => ($rfc = $vacioANull($datos['rfc'] ?? null)) !== null ? mb_strtoupper($rfc, 'UTF-8') : null,
            'telefono'        => $vacioANull($datos['telefono'] ?? null),
            'domicilio'       => $vacioANull($datos['domicilio'] ?? null),
            'estado'          => $vacioANull($datos['estado'] ?? null) ?? 'Guanajuato',
            'ciudad'          => $vacioANull($datos['ciudad'] ?? null) ?? 'Uriangato',
            'activo'          => 1,
        ]);

        $id = (int) $userModel->getInsertID();
        if ($id <= 0) {
            throw new \RuntimeException('No fue posible registrar al ciudadano.');
        }

        $userModel->asignarRol($id, 'ciudadano');
        (new AuditoriaModel())->registrar('users', $id, 'alta_presencial_sin_acceso', $creadoPor, [
            'curp' => $curp !== '' ? $curp : null,
        ]);

        return $id;
    }

    /**
     * Da acceso al portal a una cuenta creada sin contraseña.
     *
     * Se usa cuando el ciudadano se registra en línea con la misma CURP que se
     * capturó en ventanilla: la cuenta existente adopta usuario, contraseña y
     * datos de contacto, y conserva todos sus trámites. Nunca se habilita una
     * cuenta que ya tenga contraseña: esas se rechazan como duplicado.
     *
     * @param array<string, mixed> $datos
     */
    public function habilitarAcceso(int $userId, array $datos): bool
    {
        $userModel = new UserModel();
        $usuario   = $userModel->find($userId);
        if ($usuario === null) {
            return false;
        }
        if (trim((string) ($usuario->password_hash ?? '')) !== '') {
            return false;
        }

        $permitidos = ['username', 'password_hash', 'email', 'telefono', 'domicilio', 'estado', 'ciudad', 'rfc', 'nombre_completo', 'apellido', 'curp'];
        $cambios    = array_intersect_key($datos, array_flip($permitidos));
        if ($cambios === [] || ! $userModel->update($userId, $cambios)) {
            return false;
        }

        (new AuditoriaModel())->registrar('users', $userId, 'reclamar_cuenta', $userId, [
            'username' => $cambios['username'] ?? null,
        ]);

        return true;
    }

    /**
     * Cuentas activas cuyo nombre contiene lo escrito, para que ventanilla
     * elija una existente en lugar de duplicarla.
     *
     * @return list<object>
     */
    public function buscarPorNombre(string $nombre, int $limite = 10): array
    {
        $nombre = trim((string) (preg_replace('/\s+/', ' ', $nombre) ?? ''));
        if (mb_strlen($nombre) < 2) {
            return [];
        }

        return (new UserModel())
            ->like('nombre_completo', $nombre)
            ->where('activo', 1)
            ->orderBy('nombre_completo', 'ASC')
            ->limit(max(1, $limite))
            ->findAll();
    }

    /**
     * Primer registro con el mismo nombre, sin distinguir mayúsculas, acentos ni
     * espacios repetidos. Es el aviso de "ya existe alguien así" de COB-08.
     *
     * La comparación se hace en PHP porque LIKE no ignora acentos ni en SQLite
     * ni en Postgres sin la extensión unaccent; el padrón de un municipio cabe
     * de sobra en el tope de filas que se revisa.
     */
    public function nombreSimilar(string $nombreCompleto, int $revisar = 2000): ?object
    {
        $buscado = self::normalizarNombre($nombreCompleto);
        if ($buscado === '') {
            return null;
        }

        $candidatos = (new UserModel())
            ->select('id, nombre_completo, curp, telefono, activo')
            ->where('activo', 1)
            ->orderBy('id', 'DESC')
            ->limit(max(1, $revisar))
            ->findAll();

        foreach ($candidatos as $candidato) {
            if (self::normalizarNombre((string) ($candidato->nombre_completo ?? '')) === $buscado) {
                return $candidato;
            }
        }

        return null;
    }

    /** Minúsculas, sin acentos y con un solo espacio entre palabras. */
    public static function normalizarNombre(string $nombre): string
    {
        $nombre = trim((string) (preg_replace('/\s+/', ' ', $nombre) ?? ''));
        if ($nombre === '') {
            return '';
        }

        $sinAcentos = strtr(mb_strtolower($nombre, 'UTF-8'), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ]);

        return $sinAcentos;
    }

    public function generarPassword(): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }
        return $password;
    }

    private function usernameDisponible(string $base): string
    {
        $userModel = new UserModel();
        $username  = $base;
        $sufijo    = 2;
        while ($userModel->where('username', $username)->countAllResults() > 0) {
            $username = $base . $sufijo++;
        }
        return $username;
    }
}
