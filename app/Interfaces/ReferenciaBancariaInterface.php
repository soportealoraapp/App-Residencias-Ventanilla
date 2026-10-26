<?php declare(strict_types=1);

namespace App\Interfaces;

/**
 * Formato de la referencia con la que el ciudadano paga en el banco (COB-04).
 *
 * Cuando llegue el formato oficial del convenio con BanBajío, se escribe una
 * clase nueva que implemente esta interfaz y se cambia en
 * Config\Services::referenciaBancaria(). Nada más del módulo de cobros cambia.
 */
interface ReferenciaBancariaInterface
{
    /** Referencia única de un cobro. `$origen` es 'solicitud' o 'boleta'. */
    public function generar(string $origen, int $cobroId): string;

    /** Si la referencia tiene el formato correcto (incluido su dígito verificador). */
    public function esValida(string $referencia): bool;

    /** Quita espacios y guiones que la gente escribe al copiarla del comprobante. */
    public function normalizar(string $referencia): string;
}
