<?php declare(strict_types=1);

namespace App\Libraries;

use DateTimeImmutable;

/**
 * Corte de caja (COB-03).
 *
 * Suma lo cobrado en un periodo a partir de los cobros aprobados (su
 * fecha_cobro), con totales por concepto, por lugar de pago y por quien cobró.
 * Lo cancelado se cuenta aparte, por la fecha en que se canceló. El total del
 * corte es siempre la suma de los recibos del periodo.
 */
class CorteCajaService
{
    public const PERIODOS = ['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes', 'rango' => 'Rango de fechas'];

    private const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    /**
     * Fechas de inicio y fin (incluidas) del periodo y su nombre legible.
     * La semana va de lunes a domingo.
     *
     * @return array{desde: DateTimeImmutable, hasta: DateTimeImmutable, etiqueta: string}
     */
    public static function rango(string $periodo, string $fecha = '', string $desde = '', string $hasta = ''): array
    {
        $base = self::fecha($fecha) ?? new DateTimeImmutable('today');

        switch ($periodo) {
            case 'semana':
                $inicio = $base->modify('monday this week');
                $fin    = $inicio->modify('+6 days');
                $etiqueta = 'Semana del ' . self::fechaLarga($inicio) . ' al ' . self::fechaLarga($fin);
                break;
            case 'mes':
                $inicio = $base->modify('first day of this month');
                $fin    = $base->modify('last day of this month');
                $etiqueta = ucfirst(self::MESES[(int) $inicio->format('n') - 1]) . ' de ' . $inicio->format('Y');
                break;
            case 'rango':
                $inicio = self::fecha($desde) ?? $base;
                $fin    = self::fecha($hasta) ?? $inicio;
                if ($fin < $inicio) {
                    [$inicio, $fin] = [$fin, $inicio];
                }
                $etiqueta = 'Del ' . self::fechaLarga($inicio) . ' al ' . self::fechaLarga($fin);
                break;
            default:
                $inicio = $fin = $base;
                $etiqueta = ucfirst(self::fechaLarga($base));
        }

        return ['desde' => $inicio, 'hasta' => $fin, 'etiqueta' => $etiqueta];
    }

    /**
     * @return array{
     *   cobros: list<object>, total: float, cantidad: int, monto_base: float,
     *   descuentos: float, recargos: float, condonado: float,
     *   por_concepto: array<string, array{cantidad: int, total: float}>,
     *   por_lugar: array<string, array{cantidad: int, total: float}>,
     *   por_cobrador: array<string, array{cantidad: int, total: float}>,
     *   cancelados: array{cantidad: int, total: float}
     * }
     */
    public function corte(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $cobradorId = null): array
    {
        $db = \Config\Database::connect();
        $inicio = $desde->format('Y-m-d') . ' 00:00:00';
        $fin    = $hasta->format('Y-m-d') . ' 23:59:59';

        $consulta = $db->table('cobros c')
            ->select('c.*, s.tramite AS solicitud_tramite, s.folio AS solicitud_folio, b.folio AS boleta_folio, u.nombre_completo AS cobrador_nombre, u.username AS cobrador_username')
            ->join('solicitudes s', 's.id = c.solicitud_id', 'left')
            ->join('boletas_infracciones b', 'b.id = c.boleta_id', 'left')
            ->join('users u', 'u.id = c.cobrado_por', 'left')
            ->where('c.estatus', CobroService::APROBADO)
            ->where('c.fecha_cobro >=', $inicio)
            ->where('c.fecha_cobro <=', $fin)
            ->orderBy('c.fecha_cobro', 'ASC')
            ->orderBy('c.id', 'ASC');
        if ($cobradorId !== null) {
            $consulta->where('c.cobrado_por', $cobradorId);
        }
        $cobros = $consulta->get()->getResult();

        $resultado = [
            'cobros' => $cobros, 'total' => 0.0, 'cantidad' => count($cobros), 'monto_base' => 0.0,
            'descuentos' => 0.0, 'recargos' => 0.0, 'condonado' => 0.0,
            'por_concepto' => [], 'por_lugar' => [], 'por_cobrador' => [],
        ];
        foreach ($cobros as $c) {
            $total = (float) $c->total;
            $resultado['total']      += $total;
            $resultado['monto_base'] += (float) $c->monto_base;
            $resultado['descuentos'] += (float) $c->descuento;
            $resultado['recargos']   += (float) $c->recargo;
            $resultado['condonado']  += (float) $c->condonado;

            $c->tipo_concepto = $c->origen === 'boleta' ? 'Multas de tránsito' : tramite_nombre((string) $c->solicitud_tramite);
            $c->cobrador      = $c->cobrador_nombre ?: ($c->cobrador_username ?: 'Sin registrar');
            $c->lugar         = CobroService::LUGARES_PAGO[$c->lugar_pago] ?? 'Sin registrar';

            self::sumar($resultado['por_concepto'], $c->tipo_concepto, $total);
            self::sumar($resultado['por_lugar'], $c->lugar, $total);
            self::sumar($resultado['por_cobrador'], $c->cobrador, $total);
        }
        foreach (['total', 'monto_base', 'descuentos', 'recargos', 'condonado'] as $campo) {
            $resultado[$campo] = round($resultado[$campo], 2);
        }
        foreach (['por_concepto', 'por_lugar', 'por_cobrador'] as $grupo) {
            uasort($resultado[$grupo], static fn ($a, $b) => $b['total'] <=> $a['total']);
        }

        // Cancelados: por la fecha del movimiento de cancelación y por quien canceló.
        $cancelados = $db->table('cobro_movimientos m')
            ->select('COUNT(*) AS cantidad, COALESCE(SUM(c.total), 0) AS total', false)
            ->join('cobros c', 'c.id = m.cobro_id')
            ->where('m.accion', 'cancelar')
            ->where('m.fecha >=', $inicio)
            ->where('m.fecha <=', $fin);
        if ($cobradorId !== null) {
            $cancelados->where('m.usuario_id', $cobradorId);
        }
        $fila = $cancelados->get()->getRow();
        $resultado['cancelados'] = ['cantidad' => (int) ($fila->cantidad ?? 0), 'total' => round((float) ($fila->total ?? 0), 2)];

        return $resultado;
    }

    /**
     * Reporte de condonaciones: un renglón por autorización (movimiento
     * `condonar`), filtrado por la fecha de autorización. El importe es el de
     * cada autorización, no el acumulado del cobro.
     *
     * @return array{filas: list<object>, total: float, cantidad: int, por_estado: array<string,float>, por_autorizador: array<string,array{cantidad:int,total:float}>}
     */
    public function condonaciones(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $autorizadorId = null): array
    {
        $consulta = \Config\Database::connect()->table('cobro_movimientos m')
            ->select('m.id AS movimiento_id, m.cobro_id, m.motivo, m.detalle, m.fecha, m.usuario_id, c.folio AS cobro_folio, c.estatus AS cobro_estatus, c.boleta_id, b.folio AS boleta_folio, u.nombre_completo AS autorizador_nombre, u.username AS autorizador_username')
            ->join('cobros c', 'c.id = m.cobro_id')
            ->join('boletas_infracciones b', 'b.id = c.boleta_id', 'left')
            ->join('users u', 'u.id = m.usuario_id', 'left')
            ->where('m.accion', 'condonar')
            ->where('m.fecha >=', $desde->format('Y-m-d') . ' 00:00:00')
            ->where('m.fecha <=', $hasta->format('Y-m-d') . ' 23:59:59')
            ->orderBy('m.fecha', 'ASC')
            ->orderBy('m.id', 'ASC');
        if ($autorizadorId !== null) {
            $consulta->where('m.usuario_id', $autorizadorId);
        }

        $filas      = $consulta->get()->getResult();
        $total      = 0;
        $porEstado  = [];
        $porAutoriz = [];
        foreach ($filas as $f) {
            $detalle          = json_decode((string) $f->detalle, true);
            $centavos         = CondonacionRecargo::aCentavos($detalle['importe_adicional'] ?? 0);
            $f->importe       = CondonacionRecargo::aPesos($centavos);
            $f->acumulado     = (float) ($detalle['condonado_nuevo'] ?? 0);
            $f->autorizador   = $f->autorizador_nombre ?: ($f->autorizador_username ?: 'Sin registrar');
            $total           += $centavos;
            $porEstado[$f->cobro_estatus]  = ($porEstado[$f->cobro_estatus] ?? 0) + $centavos;
            $porAutoriz[$f->autorizador]['cantidad'] = ($porAutoriz[$f->autorizador]['cantidad'] ?? 0) + 1;
            $porAutoriz[$f->autorizador]['centavos'] = ($porAutoriz[$f->autorizador]['centavos'] ?? 0) + $centavos;
        }
        foreach ($porAutoriz as $k => $v) {
            $porAutoriz[$k] = ['cantidad' => $v['cantidad'], 'total' => CondonacionRecargo::aPesos($v['centavos'])];
        }

        return [
            'filas'           => $filas,
            'total'           => CondonacionRecargo::aPesos($total),
            'cantidad'        => count($filas),
            'por_estado'      => array_map([CondonacionRecargo::class, 'aPesos'], $porEstado),
            'por_autorizador' => $porAutoriz,
        ];
    }

    /** Personas que han autorizado alguna condonación, para el filtro "Autorizó". */
    public function autorizadoresCondonacion(): array
    {
        return \Config\Database::connect()->table('cobro_movimientos m')
            ->select('u.id, u.nombre_completo, u.username')
            ->join('users u', 'u.id = m.usuario_id')
            ->where('m.accion', 'condonar')
            ->groupBy('u.id, u.nombre_completo, u.username')
            ->orderBy('u.nombre_completo', 'ASC')
            ->get()->getResult();
    }

    /** Personas que han cobrado alguna vez, para el filtro "Cobró". */
    public function cobradores(): array
    {
        return \Config\Database::connect()->table('cobros c')
            ->select('u.id, u.nombre_completo, u.username')
            ->join('users u', 'u.id = c.cobrado_por')
            ->groupBy('u.id, u.nombre_completo, u.username')
            ->orderBy('u.nombre_completo', 'ASC')
            ->get()->getResult();
    }

    /** @param array<string, array{cantidad: int, total: float}> $grupo */
    private static function sumar(array &$grupo, string $clave, float $total): void
    {
        $grupo[$clave] ??= ['cantidad' => 0, 'total' => 0.0];
        $grupo[$clave]['cantidad']++;
        $grupo[$clave]['total'] = round($grupo[$clave]['total'] + $total, 2);
    }

    private static function fecha(string $valor): ?DateTimeImmutable
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) !== 1) {
            return null;
        }
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

        return $fecha !== false && $fecha->format('Y-m-d') === $valor ? $fecha : null;
    }

    private static function fechaLarga(DateTimeImmutable $fecha): string
    {
        return $fecha->format('j') . ' de ' . self::MESES[(int) $fecha->format('n') - 1] . ' de ' . $fecha->format('Y');
    }
}
