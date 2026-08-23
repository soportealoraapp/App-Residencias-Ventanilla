<?php declare(strict_types=1);

namespace App\Libraries;

use App\Interfaces\PaymentGatewayInterface;

class BanbajioMockGateway implements PaymentGatewayInterface
{
    public function crearCargo(string $folio, float $monto, string $descripcion, array $metadata = []): array
    {
        return [
            'success'   => true,
            'referencia' => 'MOCK-' . uniqid(),
            'url_pago'  => '#mock-pago',
            'mensaje'   => 'Gateway Mock - Pago simulado',
        ];
    }

    public function confirmarPago(string $referenciaPago): array
    {
        return [
            'success'      => true,
            'pagado'       => true,
            'fecha_pago'   => date('Y-m-d H:i:s'),
            'monto_pagado' => 0.0,
        ];
    }

    public function reembolsar(string $referenciaPago, float $monto, string $motivo = ''): bool
    {
        return true;
    }
}
