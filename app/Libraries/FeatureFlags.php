<?php declare(strict_types=1);

namespace App\Libraries;

class FeatureFlags
{
    public static function habilitado(string $flag, bool $default = false): bool
    {
        $valor = getenv("APP_ENABLE_{$flag}");
        if ($valor === false || $valor === '') {
            return $default;
        }
        $valor = strtolower(trim((string)$valor));
        return in_array($valor, ['true', '1', 'yes'], true);
    }

    public static function habilitarUrTtT06(): bool
    {
        return self::habilitado('UR_TT_T_06');
    }

    /**
     * Botones de acceso rápido con cuentas demo en el login (APP_ENABLE_DEMO_LOGIN).
     * Apagado por defecto: solo se enciende en ambientes de demostración o locales.
     */
    public static function loginDemo(): bool
    {
        return self::habilitado('DEMO_LOGIN');
    }

    /**
     * Pago en línea de T-04, T-05 y T-07 (APP_ENABLE_PAGO_EN_LINEA).
     * Apagado por defecto: mientras no exista el convenio con BanBajío la pasarela
     * es simulada, así que en producción solo queda la hoja de pago para caja.
     */
    public static function pagoEnLinea(): bool
    {
        return self::habilitado('PAGO_EN_LINEA');
    }
}
