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
}
