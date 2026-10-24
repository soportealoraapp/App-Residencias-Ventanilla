<?php declare(strict_types=1);

namespace App\Libraries;

use RuntimeException;

/**
 * Operación de cobro no permitida. El mensaje está pensado para mostrarse tal
 * cual a quien opera el panel (por ejemplo, "El cobro ya fue cancelado.").
 */
class CobroException extends RuntimeException
{
}
