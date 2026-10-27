<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\UserModel;

/**
 * Reglas compartidas de la condonación de recargos (COB-11).
 *
 * Es el único lugar con la fórmula y con la lista de quién puede autorizar:
 * el servicio de cobros, la hoja de pago, las pantallas y las rutas la usan.
 * Los importes se comparan en centavos enteros para no acumular errores de
 * precisión.
 */
final class CondonacionRecargo
{
    /**
     * Configuración inicial prevista en el plan del proyecto; está pendiente de
     * validación operativa con el encargado. Para ajustarla basta con cambiar
     * esta lista.
     *
     * @var list<string>
     */
    public const ROLES_AUTORIZADOS = ['juez_calificador', 'administrador'];

    /** Quién autoriza, según los roles reales guardados para el usuario. */
    public static function puedeAutorizar(?int $usuarioId): bool
    {
        if ($usuarioId === null || $usuarioId <= 0) {
            return false;
        }

        return self::rolesPermitidos((new UserModel())->conRoles($usuarioId));
    }

    /** @param list<string> $roles */
    public static function rolesPermitidos(array $roles): bool
    {
        return array_intersect(self::ROLES_AUTORIZADOS, $roles) !== [];
    }

    /**
     * Importe capturado: dígitos con punto decimal opcional y máximo dos
     * decimales. No acepta signos, comas, notación científica ni vacíos.
     *
     * @throws CobroException
     */
    public static function importeACentavos(string $texto): int
    {
        $texto = trim($texto);
        if ($texto === '') {
            throw new CobroException('Escriba el importe a condonar.');
        }
        if (preg_match('/^\d{1,9}(?:\.(\d{1,2}))?$/D', $texto) !== 1) {
            throw new CobroException('El importe debe ser un número positivo en pesos, con punto decimal y máximo dos decimales (por ejemplo 150 o 150.50).');
        }

        [$pesos, $decimales] = array_pad(explode('.', $texto, 2), 2, '');
        $centavos = ((int) $pesos) * 100 + (int) str_pad($decimales, 2, '0');
        if ($centavos <= 0) {
            throw new CobroException('El importe a condonar debe ser mayor que cero.');
        }

        return $centavos;
    }

    public static function aCentavos(float|string|null $pesos): int
    {
        return (int) round(((float) $pesos) * 100);
    }

    public static function aPesos(int $centavos): float
    {
        return round($centavos / 100, 2);
    }

    /** Parte de lo autorizado que el recargo vigente puede absorber. */
    public static function aplicado(float|string|null $autorizado, float|string|null $recargo): float
    {
        return self::aPesos(max(0, min(self::aCentavos($autorizado), self::aCentavos($recargo))));
    }

    /**
     * Total a pagar de una multa: el cálculo de descuento/recargo menos la
     * condonación aplicada. Nunca baja del monto base ni de cero, porque la
     * condonación solo absorbe recargo.
     *
     * @param array<string, mixed> $calculo Resultado de ResolverMontoInfraccionService::aplicarDescuentoRecargo()
     */
    public static function total(array $calculo, float|string|null $autorizado): float
    {
        $aplicado = self::aCentavos(self::aplicado($autorizado, $calculo['monto_recargo'] ?? 0));

        return self::aPesos(max(0, self::aCentavos($calculo['monto_final'] ?? 0) - $aplicado));
    }
}
