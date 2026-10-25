<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\CatalogoInfraccionModel;
use App\Models\ParametroSistemaModel;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;

class ResolverMontoInfraccionService
{
    protected CatalogoInfraccionModel $infraccionModel;
    protected ParametroSistemaModel $parametroModel;

    public function __construct(
        ?CatalogoInfraccionModel $infraccionModel = null,
        ?ParametroSistemaModel $parametroModel = null
    ) {
        $this->infraccionModel = $infraccionModel ?? new CatalogoInfraccionModel();
        $this->parametroModel  = $parametroModel ?? new ParametroSistemaModel();
    }

    /**
     * Calcula el monto base en pesos (MXN) para una infracción según el catálogo oficial.
     *
     * Regla de negocio actual:
     * Monto automático = monto_min_uma * valor_uma_vigente
     *
     * @param int|string $infraccionId ID del registro en catalogo_infracciones
     * @return float|null Monto calculado en pesos MXN o null si no existe la infracción
     */
    public function calcularMonto($infraccionId): ?float
    {
        $infraccion = $this->infraccionModel->find($infraccionId);

        if ($infraccion === null) {
            return null;
        }

        $valorUmaStr = $this->parametroModel->getValor('valor_uma_vigente');

        if ($valorUmaStr === null || !is_numeric($valorUmaStr)) {
            throw new RuntimeException("El parámetro de sistema 'valor_uma_vigente' no está configurado o no es numérico.");
        }

        $valorUma = (float) $valorUmaStr;
        $montoMinUma = (float) $infraccion->monto_min_uma;

        return round($montoMinUma * $valorUma, 2);
    }

    /**
     * Obtiene el desglose completo del cálculo base de la infracción.
     *
     * @param int|string $infraccionId
     * @return array|null
     */
    public function calcularDesglose($infraccionId): ?array
    {
        $infraccion = $this->infraccionModel->find($infraccionId);

        if ($infraccion === null) {
            return null;
        }

        $valorUmaStr = $this->parametroModel->getValor('valor_uma_vigente');

        if ($valorUmaStr === null || !is_numeric($valorUmaStr)) {
            throw new RuntimeException("El parámetro de sistema 'valor_uma_vigente' no está configurado o no es numérico.");
        }

        $valorUma = (float) $valorUmaStr;
        $montoMinUma = (float) $infraccion->monto_min_uma;
        $montoMaxUma = (float) $infraccion->monto_max_uma;

        return [
            'infraccion_id'     => (int) $infraccion->id,
            'fundamento_legal'  => $infraccion->fundamento_legal,
            'descripcion'       => $infraccion->descripcion,
            'categoria_actor'   => $infraccion->categoria_actor,
            'monto_min_uma'     => $montoMinUma,
            'monto_max_uma'     => $montoMaxUma,
            'valor_uma_vigente' => $valorUma,
            'monto_calculado'   => round($montoMinUma * $valorUma, 2),
            'monto_max_pesos'   => round($montoMaxUma * $valorUma, 2),
        ];
    }

    /**
     * Calcula una selección judicial usando los rangos históricos de la boleta.
     * Cada subtotal se redondea a centavos antes de sumarse al monto base.
     *
     * @param list<array<string,mixed>> $faltas
     * @param array<int,int|float|string> $seleccionesUma
     */
    public function calcularCalificacion(array $faltas, array $seleccionesUma): array
    {
        if ($faltas === []) {
            throw new RuntimeException('La boleta no contiene faltas válidas para calificar.');
        }
        $indicesEsperados = range(0, count($faltas) - 1);
        $indicesRecibidos = array_map('intval', array_keys($seleccionesUma));
        sort($indicesRecibidos);
        if ($indicesRecibidos !== $indicesEsperados || count($seleccionesUma) !== count($faltas)) {
            throw new RuntimeException('Debe asignar exactamente un valor UMA a cada falta.');
        }

        $valorUmaStr = $this->parametroModel->getValor('valor_uma_vigente');
        if ($valorUmaStr === null || ! is_numeric($valorUmaStr) || (float) $valorUmaStr <= 0) {
            throw new RuntimeException("El parámetro de sistema 'valor_uma_vigente' no está configurado o no es numérico.");
        }
        $valorUma = round((float) $valorUmaStr, 2);
        $detalles = [];
        $totalUma = 0.0;
        $montoBase = 0.0;

        foreach ($faltas as $indice => $falta) {
            if (! is_array($falta)) {
                throw new RuntimeException('La información histórica de una falta está dañada.');
            }
            $fundamento = trim((string) ($falta['fundamentoLegal'] ?? $falta['fundamento_legal'] ?? ''));
            $descripcion = trim((string) ($falta['descripcion'] ?? $falta['descripcion_oficial'] ?? ''));
            $categoria = trim((string) ($falta['categoria'] ?? $falta['categoria_actor'] ?? ''));
            $minimo = $falta['montoMinUma'] ?? $falta['monto_min_uma'] ?? null;
            $maximo = $falta['montoMaxUma'] ?? $falta['monto_max_uma'] ?? null;
            if ($fundamento === '' || ! is_numeric($minimo) || ! is_numeric($maximo)) {
                throw new RuntimeException('La información histórica de una falta está incompleta.');
            }
            $minimo = round((float) $minimo, 2);
            $maximo = round((float) $maximo, 2);
            if ($minimo < 0 || $maximo < $minimo) {
                throw new RuntimeException('La información histórica contiene un rango UMA inválido.');
            }
            $seleccion = $seleccionesUma[$indice] ?? null;
            $seleccionTexto = is_scalar($seleccion) ? trim((string) $seleccion) : '';
            if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $seleccionTexto)) {
                throw new RuntimeException('Cada falta requiere un valor UMA con máximo dos decimales.');
            }
            $uma = (float) $seleccionTexto;
            if ($uma < $minimo || $uma > $maximo) {
                throw new RuntimeException(sprintf('La UMA asignada a %s debe estar entre %.2f y %.2f.', $fundamento, $minimo, $maximo));
            }
            $subtotal = round($uma * $valorUma, 2);
            $totalUma = round($totalUma + $uma, 2);
            $montoBase = round($montoBase + $subtotal, 2);
            $detalles[] = [
                'orden' => $indice + 1,
                'falta_id_origen' => isset($falta['id']) ? (string) $falta['id'] : null,
                'fundamento_legal' => $fundamento,
                'descripcion' => $descripcion,
                'categoria' => $categoria,
                'monto_min_uma' => $minimo,
                'monto_max_uma' => $maximo,
                'uma_asignada' => $uma,
                'subtotal_pesos' => $subtotal,
            ];
        }
        return ['valor_uma' => $valorUma, 'total_uma' => $totalUma, 'monto_base' => $montoBase, 'detalles' => $detalles];
    }
    /**
     * Calcula el monto final a pagar aplicando descuento por pronto pago o recargo por mora.
     *
     * REGLAS DE NEGOCIO:
     * 1. Descuento pronto pago (40%): Aplicable si el pago se realiza dentro de los primeros
     *    10 días hábiles (Lunes a Viernes) a partir del día hábil siguiente a la infracción.
     * 2. Monto regular (100%): Si transcurrieron más de 10 días hábiles y hasta 1 mes natural.
     * 3. Recargo por mora (5% mensual): Si transcurrió más de 1 mes desde la infracción.
     *    - Fundamento: Art. 180 del Reglamento de Tránsito de Uriangato.
     *    - NOTA DE ORIGEN: En conversación previa el Juez Calificador mencionó verbalmente "0.05%",
     *      pero en la boleta oficial impresa aparece "5% mensual". Se toma 5% por mayor certeza documental,
     *      pendiente de ratificación formal con Movilidad.
     *    - LÓGICA DE ACUMULACIÓN: Interés simple mensual (5% por cada mes completo transcurrido de mora).
     *
     * @param int|string $infraccionId
     * @param DateTimeInterface|string|null $fechaInfraccion Fecha en que se levantó la infracción
     * @param DateTimeInterface|string|null $fechaPago Fecha de pago (opcional, defaults to server current time)
     * @return float|null Monto final a pagar
     */
    public function calcularMontoConDescuentoRecargo(
        $infraccionId,
        $fechaInfraccion = null,
        $fechaPago = null
    ): ?float {
        $detalle = $this->calcularMontoConDescuentoRecargoDetalle($infraccionId, $fechaInfraccion, $fechaPago);
        return $detalle !== null ? $detalle['monto_final'] : null;
    }

    /**
     * Retorna el detalle exhaustivo del cálculo de descuento o recargo.
     *
     * @param int|string $infraccionId
     * @param DateTimeInterface|string|null $fechaInfraccion
     * @param DateTimeInterface|string|null $fechaPago
     * @return array|null
     */
    public function calcularMontoConDescuentoRecargoDetalle(
        $infraccionId,
        $fechaInfraccion = null,
        $fechaPago = null
    ): ?array {
        $montoBase = $this->calcularMonto($infraccionId);

        if ($montoBase === null) {
            return null;
        }

        return ['infraccion_id' => (int) $infraccionId] + $this->aplicarDescuentoRecargo($montoBase, $fechaInfraccion, $fechaPago);
    }

    /**
     * Aplica las reglas de pronto pago y recargo por mora a un monto ya fijado
     * (por ejemplo, el que califica el juez dentro del rango en UMA de la falta).
     *
     * @param DateTimeInterface|string|null $fechaInfraccion
     * @param DateTimeInterface|string|null $fechaPago
     */
    public function aplicarDescuentoRecargo(float $montoBase, $fechaInfraccion = null, $fechaPago = null): array
    {
        // 1. Normalizar fechas: fechaPago SIEMPRE se resuelve contra el servidor si es nula
        $pagoObj = $this->normalizarFecha($fechaPago) ?? new DateTimeImmutable('now');
        $infraccionObj = $this->normalizarFecha($fechaInfraccion) ?? $pagoObj;

        // 2. Obtener parámetros configurables de la BD
        $porcentajeDesc = (float) ($this->parametroModel->getValor('porcentaje_descuento_pronto_pago', '40.00'));
        $diasLimiteDesc = (int) ($this->parametroModel->getValor('dias_habiles_descuento', '10'));
        $porcentajeRecargo = (float) ($this->parametroModel->getValor('porcentaje_recargo_mensual', '5.00'));

        // 3. Contar días hábiles (Lunes a Viernes) entre fecha de infracción y fecha de pago
        $diasHabiles = $this->contarDiasHabiles($infraccionObj, $pagoObj);

        // 4. Calcular meses transcurridos para evaluar mora (> 1 mes)
        $limiteUnMes = (clone $infraccionObj)->modify('+1 month');
        $esPosteriorAUnMes = $pagoObj > $limiteUnMes;

        $tipoAjuste = 'regular';
        $descuentoMonto = 0.0;
        $recargoMonto = 0.0;
        $mesesMora = 0;
        $montoFinal = $montoBase;

        if (!$esPosteriorAUnMes && $diasHabiles <= $diasLimiteDesc) {
            // CASO A: Pronto Pago (<= 10 días hábiles)
            $tipoAjuste = 'descuento_pronto_pago';
            $descuentoMonto = round($montoBase * ($porcentajeDesc / 100.0), 2);
            $montoFinal = round($montoBase - $descuentoMonto, 2);
        } elseif ($esPosteriorAUnMes) {
            // CASO C: Recargo por Mora (> 1 mes)
            // Lógica de acumulación: Interés simple mensual acumulado por cada mes transcurrido
            $tipoAjuste = 'recargo_mora';
            $interval = $infraccionObj->diff($pagoObj);
            $mesesTranscurridos = ($interval->y * 12) + $interval->m;
            $mesesMora = max(1, $mesesTranscurridos);

            $recargoMonto = round($montoBase * ($porcentajeRecargo / 100.0) * $mesesMora, 2);
            $montoFinal = round($montoBase + $recargoMonto, 2);
        } else {
            // CASO B: Monto regular (entre día hábil 11 y 1 mes)
            $tipoAjuste = 'monto_regular';
            $montoFinal = $montoBase;
        }

        return [
            'monto_base'                  => $montoBase,
            'fecha_infraccion'            => $infraccionObj->format('Y-m-d'),
            'fecha_pago'                  => $pagoObj->format('Y-m-d H:i:s'),
            'dias_habiles_transcurridos'  => $diasHabiles,
            'dias_habiles_limite'         => $diasLimiteDesc,
            'tipo_ajuste'                 => $tipoAjuste,
            'aplica_descuento'            => $tipoAjuste === 'descuento_pronto_pago',
            'porcentaje_descuento'        => $tipoAjuste === 'descuento_pronto_pago' ? $porcentajeDesc : 0.0,
            'monto_descuento'             => $descuentoMonto,
            'aplica_recargo'              => $tipoAjuste === 'recargo_mora',
            'meses_mora'                  => $mesesMora,
            'porcentaje_recargo_mensual'  => $porcentajeRecargo,
            'monto_recargo'               => $recargoMonto,
            'monto_final'                 => $montoFinal,
        ];
    }

    /**
     * Cuenta los días hábiles (Lunes a Viernes) entre dos fechas.
     * Se comienza a contar a partir del día hábil siguiente a la fecha inicial.
     */
    public function contarDiasHabiles(DateTimeInterface $fechaInicio, DateTimeInterface $fechaFin): int
    {
        $inicio = new DateTime($fechaInicio->format('Y-m-d'));
        $fin    = new DateTime($fechaFin->format('Y-m-d'));

        if ($fin < $inicio) {
            return 0;
        }

        $diasHabiles = 0;
        $current = clone $inicio;
        $current->modify('+1 day'); // Día hábil 1 comienza al día siguiente de la infracción

        while ($current <= $fin) {
            $diaSemana = (int) $current->format('N'); // 1 (Lun) a 7 (Dom)
            if ($diaSemana >= 1 && $diaSemana <= 5) {
                $diasHabiles++;
            }
            $current->modify('+1 day');
        }

        return $diasHabiles;
    }

    /**
     * Normaliza un input a DateTimeImmutable.
     */
    private function normalizarFecha($fecha): ?DateTimeImmutable
    {
        if ($fecha === null) {
            return null;
        }

        if ($fecha instanceof DateTimeImmutable) {
            return $fecha;
        }

        if ($fecha instanceof DateTime) {
            return DateTimeImmutable::createFromMutable($fecha);
        }

        if (is_string($fecha) && $fecha !== '') {
            return new DateTimeImmutable($fecha);
        }

        return null;
    }
}
