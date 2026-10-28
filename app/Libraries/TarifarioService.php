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

        // Ficha UR-TT-T-07 (29-ene-26): la tarifa particular es "por 1 camión",
        // así que se multiplica por los camiones; la de empresa establecida en el
        // municipio es una cuota fija que cubre hasta 15 camiones.
        if (strtolower($tipoSolicitante) === 'particular') {
            return floatval($tarifa->monto) * max(1, $numCamiones);
        }

        return floatval($tarifa->monto);
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

    public function calcularMonto(string $tramite, string $criterio = 'base'): ?float
    {
        $tarifa = (new TarifaModel())->vigente($tramite, $criterio);

        return $tarifa === null ? null : (float) $tarifa->monto;
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

    public function esPlaceholderT07(string $tipoSolicitante, string $periodo, int $numCamiones = 1): bool
    {
        $criterio = strtolower($tipoSolicitante) . '_' . $periodo;
        return $this->esPlaceholder('UR-TT-T-07', $criterio);
    }

    public function esPlaceholderT06(): bool
    {
        return $this->esPlaceholder('UR-TT-T-06', 'cesion_concesion_base');
    }
}
