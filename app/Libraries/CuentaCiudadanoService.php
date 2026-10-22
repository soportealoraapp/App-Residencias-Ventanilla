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
