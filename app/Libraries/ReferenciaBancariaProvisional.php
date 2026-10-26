<?php declare(strict_types=1);

namespace App\Libraries;

use App\Interfaces\ReferenciaBancariaInterface;

/**
 * Referencia provisional mientras no exista el formato del convenio con
 * BanBajío (COB-04).
 *
 * 12 dígitos: tipo (1 trámite, 2 multa) + id del cobro en 9 dígitos + 2 dígitos
 * verificadores MOD 97-10 (el mismo cálculo de la CLABE internacional/IBAN).
 * Un dígito mal capturado o dos dígitos intercambiados se detectan.
 *
 *     cobro 1 de trámite → 1 000000001 + 98 - (1000000001·100 mod 97)
 *
 * supabase/migrations/0017_referencia_bancaria.sql usa el mismo cálculo para
 * los cobros que ya existían.
 */
class ReferenciaBancariaProvisional implements ReferenciaBancariaInterface
{
    private const TIPOS = ['solicitud' => 1, 'boleta' => 2];

    public function generar(string $origen, int $cobroId): string
    {
        if (! isset(self::TIPOS[$origen]) || $cobroId <= 0 || $cobroId > 999_999_999) {
            throw new CobroException('No se puede generar la referencia bancaria de este cobro.');
        }
        $base = (string) self::TIPOS[$origen] . str_pad((string) $cobroId, 9, '0', STR_PAD_LEFT);

        return $base . self::verificador($base);
    }

    public function esValida(string $referencia): bool
    {
        $referencia = $this->normalizar($referencia);
        if (preg_match('/^[12]\d{11}$/', $referencia) !== 1) {
            return false;
        }

        return substr($referencia, 10) === self::verificador(substr($referencia, 0, 10));
    }

    public function normalizar(string $referencia): string
    {
        return preg_replace('/[\s\-.]+/', '', trim($referencia)) ?? '';
    }

    private static function verificador(string $base): string
    {
        // Se calcula por partes para no depender de enteros de 64 bits.
        $resto = 0;
        foreach (str_split($base . '00') as $digito) {
            $resto = ($resto * 10 + (int) $digito) % 97;
        }

        return str_pad((string) (98 - $resto), 2, '0', STR_PAD_LEFT);
    }
}
