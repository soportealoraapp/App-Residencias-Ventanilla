<?php declare(strict_types=1);

namespace App\Interfaces;

interface PaymentGatewayInterface
{
    public function crearCargo(string $folio, float $monto, string $descripcion, array $metadata = []): array;

    public function confirmarPago(string $referenciaPago): array;

    public function reembolsar(string $referenciaPago, float $monto, string $motivo = ''): bool;
}
