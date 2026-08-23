<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\TarifaModel;
use DateTime;

class TarifarioService
{
    public function calcularMontoUrTtT07(string $tipoSolicitante, string $periodo, int $numCamiones = 1): ?float
    {
        $criterio = strtolower($tipoSolicitante) . '_' . $periodo;

        $tarifaModel = new TarifaModel();
        $tarifa = $tarifaModel->vigente('UR-TT-T-07', $criterio);

        if ($tarifa === null) {
            return null;
        }

        $montoBase = $tarifa->monto;

        if (strtolower($tipoSolicitante) === 'empresa') {
            $numCamiones = max(1, min(15, $numCamiones));
            $montoBase = $montoBase * $numCamiones;
        }

        return floatval($montoBase);
    }

    public function calcularMontoUrTtT06(): ?float
    {
        $tarifaModel = new TarifaModel();
        $tarifa = $tarifaModel->vigente('UR-TT-T-06', 'cesion_concesion_base');

        if ($tarifa === null) {
            return null;
        }

        return floatval($tarifa->monto);
    }

    public function esPlaceholder(string $tramite, string $criterio): bool
    {
        $tarifaModel = new TarifaModel();
        $tarifa = $tarifaModel->vigente($tramite, $criterio);

        if ($tarifa === null) {
            return false;
        }

        return (int)$tarifa->placeholder_oficial === 1;
    }
}
