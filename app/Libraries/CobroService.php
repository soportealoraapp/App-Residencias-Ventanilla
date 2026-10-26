<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\AuditoriaModel;
use App\Models\BoletaInfraccionModel;
use App\Models\CobroModel;
use App\Models\CobroMovimientoModel;
use App\Models\SolicitudDatoModel;
use App\Models\SolicitudModel;
use App\Models\UserModel;
use DateTimeImmutable;

/**
 * Cobros de trámites y de multas (COB-01).
 *
 * Un cobro nace pendiente y termina aprobado (cobrado) o cancelado:
 *
 *     pendiente ──cobrar──► aprobado
 *         │ └─mandar a completar (sigue pendiente, con motivo)
 *         └──cancelar────► cancelado
 *
 * Trámites: el cobro se crea solo cuando la solicitud llega a "Pago pendiente"
 * (EstadoSolicitudService llama a sincronizarConSolicitud). Al cobrarlo, la
 * solicitud pasa a "Pagado" (o "Concluido" en UR-TT-T-06); si la solicitud se
 * rechaza, el cobro pendiente se cancela.
 *
 * Multas: el juez califica la boleta y llama a crearDesdeBoleta con el monto
 * que fijó. Descuento por pronto pago y recargo por mora se calculan con
 * ResolverMontoInfraccionService y se recalculan al momento de cobrar.
 */
class CobroService
{
    public const PENDIENTE = 'pendiente';
    public const APROBADO  = 'aprobado';
    public const CANCELADO = 'cancelado';

    public const LUGARES_PAGO = [
        'juez_calificador' => 'Juez calificador',
        'banbajio'         => 'BanBajío',
        'ventanilla'       => 'Ventanilla',
        'en_linea'         => 'Pago en línea',
    ];

    protected CobroModel $cobros;
    protected CobroMovimientoModel $movimientos;
    protected ResolverMontoInfraccionService $montos;

    public function __construct(?ResolverMontoInfraccionService $montos = null)
    {
        $this->cobros      = new CobroModel();
        $this->movimientos = new CobroMovimientoModel();
        $this->montos      = $montos ?? new ResolverMontoInfraccionService();
    }

    // ─── Creación ─────────────────────────────────────────────────────────────

    /**
     * Crea el cobro pendiente de una solicitud. Si ya tiene uno vigente
     * (pendiente o aprobado), devuelve ese en lugar de duplicarlo.
     */
    public function crearDesdeSolicitud(int $solicitudId, ?int $usuarioId = null): int
    {
        $solicitud = (new SolicitudModel())->find($solicitudId);
        if ($solicitud === null) {
            throw new CobroException('La solicitud no existe.');
        }

        $vigente = $this->cobros->vigenteDeSolicitud($solicitudId);
        if ($vigente !== null) {
            return (int) $vigente->id;
        }

        $monto = round((float) ($solicitud->monto ?? 0), 2);

        return $this->insertar([
            'origen'         => 'solicitud',
            'solicitud_id'   => $solicitudId,
            'ciudadano_id'   => $solicitud->ciudadano_id ?: null,
            'nombre_pagador' => $this->nombreDeSolicitud($solicitud),
            'concepto'       => tramite_nombre((string) $solicitud->tramite) . ' · folio ' . $solicitud->folio,
            'monto_base'     => $monto,
            'total'          => $monto,
        ], $usuarioId);
    }

    /**
     * Crea el cobro pendiente de una multa con el monto que fijó el juez.
     * Aplica el descuento o recargo que corresponda al día de hoy.
     */
    public function crearDesdeBoleta(int $boletaId, float $montoBase, ?int $usuarioId = null, ?string $nombrePagador = null): int
    {
        $boleta = (new BoletaInfraccionModel())->find($boletaId);
        if ($boleta === null) {
            throw new CobroException('La boleta no existe.');
        }
        if ($montoBase <= 0) {
            throw new CobroException('El monto de la multa debe ser mayor que cero.');
        }

        $vigente = $this->cobros->vigenteDeBoleta($boletaId);
        if ($vigente !== null) {
            return (int) $vigente->id;
        }

        $nombre = trim((string) ($nombrePagador ?? ''));
        if ($nombre === '') {
            $nombre = trim((string) ($boleta->infractor_nombre ?? ''));
        }
        if ($nombre === '' || strcasecmp($nombre, 'Conductor Ausente') === 0) {
            $nombre = 'Propietario del vehículo ' . ($boleta->vehiculo_placas ?: 'sin placas');
        }

        $calculo = $this->montos->aplicarDescuentoRecargo(round($montoBase, 2), $boleta->fecha_infraccion);

        return $this->insertar([
            'origen'          => 'boleta',
            'boleta_id'       => $boletaId,
            'nombre_pagador'  => $nombre,
            'concepto'        => 'Multa de tránsito · boleta ' . $boleta->folio,
            'monto_base'      => $calculo['monto_base'],
            'descuento'       => $calculo['monto_descuento'],
            'recargo'         => $calculo['monto_recargo'],
            'total'           => $calculo['monto_final'],
            'detalle_calculo' => json_encode($calculo, JSON_UNESCAPED_UNICODE),
        ], $usuarioId);
    }

    // ─── Cambios de estatus ───────────────────────────────────────────────────

    /**
     * Registra el pago. Asigna folio de recibo y lleva la solicitud o la boleta
     * a pagada. En multas recalcula descuento y recargo a la fecha de pago
     * (hoy, o la del comprobante bancario si se indica).
     */
    public function marcarCobrado(
        int $cobroId,
        string $lugarPago,
        ?string $referencia = null,
        ?int $usuarioId = null,
        ?DateTimeImmutable $fechaPagoBanco = null,
        ?float $importePagado = null
    ): void {
        if (! array_key_exists($lugarPago, self::LUGARES_PAGO)) {
            throw new CobroException('Lugar de pago no válido.');
        }
        $cobro = $this->pendiente($cobroId);
        $ahora = new DateTimeImmutable();

        $estadoSolicitud = null;
        if ($cobro->origen === 'solicitud') {
            $estadoSolicitud = $this->estatusPagadoDe((int) $cobro->solicitud_id);
            if ($estadoSolicitud === null) {
                throw new CobroException('La solicitud ya no está en "Pago pendiente"; revise su estatus antes de cobrar.');
            }
        }

        $cambios = [
            'estatus'              => self::APROBADO,
            'pendiente_documentos' => 0,
            'lugar_pago'           => $lugarPago,
            'referencia_pago'      => $referencia !== null && trim($referencia) !== '' ? trim($referencia) : null,
            'cobrado_por'          => $usuarioId ?: null,
            'fecha_cobro'          => $ahora->format('Y-m-d H:i:s'),
            'fecha_pago_banco'     => $fechaPagoBanco?->format('Y-m-d'),
            'importe_pagado'       => $importePagado !== null ? round($importePagado, 2) : null,
        ];
        if ($cobro->origen === 'boleta') {
            $cambios += $this->recalculoBoleta($cobro, $fechaPagoBanco ?? $ahora);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $cambios['folio_recibo'] = $this->siguienteFolioRecibo($ahora);
        $this->cobros->update($cobroId, $cambios);
        $this->movimiento($cobro, 'cobrar', self::APROBADO, null, $usuarioId, array_filter([
            'lugar_pago'       => $lugarPago,
            'referencia'       => $cambios['referencia_pago'],
            'total'            => (float) ($cambios['total'] ?? $cobro->total),
            'recibo'           => $cambios['folio_recibo'],
            'fecha_pago_banco' => $cambios['fecha_pago_banco'],
            'importe_pagado'   => $cambios['importe_pagado'],
        ], static fn ($v) => $v !== null));

        if ($cobro->origen === 'solicitud') {
            (new EstadoSolicitudService())->cambiarEstatus(
                (int) $cobro->solicitud_id,
                $estadoSolicitud,
                $usuarioId,
                'Pago registrado (' . self::LUGARES_PAGO[$lugarPago] . '). Recibo ' . $cambios['folio_recibo'] . '.'
            );
        } else {
            (new BoletaInfraccionModel())->update((int) $cobro->boleta_id, ['estado' => 'pagada']);
        }

        $db->transComplete();
        if (! $db->transStatus()) {
            throw new CobroException('No se pudo registrar el pago. Intente de nuevo.');
        }
    }

    /**
     * Registra un pago hecho en BanBajío a partir de su comprobante (COB-04).
     * El importe del comprobante debe coincidir con el total a pagar en la
     * fecha del pago; en multas ese total depende del día (pronto pago o mora).
     */
    public function registrarPagoBancario(int $cobroId, string $comprobante, string $fechaPago, float $importe, ?int $usuarioId = null): void
    {
        $comprobante = trim($comprobante);
        if ($comprobante === '') {
            throw new CobroException('Escriba el folio o número de operación del comprobante.');
        }
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', trim($fechaPago));
        if ($fecha === false || $fecha->format('Y-m-d') !== trim($fechaPago)) {
            throw new CobroException('Escriba la fecha del pago que aparece en el comprobante.');
        }
        if ($fecha > new DateTimeImmutable('today')) {
            throw new CobroException('La fecha del pago no puede ser posterior a hoy.');
        }

        $cobro = $this->pendiente($cobroId);
        if ($fecha < new DateTimeImmutable(substr((string) $cobro->created_at, 0, 10))) {
            throw new CobroException('La fecha del pago es anterior a la emisión de la referencia; revise el comprobante.');
        }

        $esperado = $cobro->origen === 'boleta'
            ? (float) $this->recalculoBoleta($cobro, $fecha)['total']
            : round((float) $cobro->total, 2);
        if (abs(round($importe, 2) - $esperado) > 0.009) {
            throw new CobroException(sprintf(
                'El importe del comprobante ($%s) no coincide con el total a pagar ($%s) para el %s.',
                number_format($importe, 2), number_format($esperado, 2), $fecha->format('d/m/Y')
            ));
        }

        $this->marcarCobrado($cobroId, 'banbajio', $comprobante, $usuarioId, $fecha, $importe);
    }

    /** Cobro de una referencia bancaria (acepta espacios y guiones). */
    public function buscarPorReferencia(string $referencia): ?object
    {
        $formato    = service('referenciaBancaria');
        $referencia = $formato->normalizar($referencia);
        if (! $formato->esValida($referencia)) {
            return null;
        }

        return $this->cobros->where('referencia_bancaria', $referencia)->first();
    }

    /** El cobro sigue pendiente, pero marcado como incompleto con el motivo. */
    public function mandarACompletar(int $cobroId, string $motivo, ?int $usuarioId = null): void
    {
        $motivo = $this->motivoObligatorio($motivo);
        $cobro  = $this->pendiente($cobroId);

        $this->cobros->update($cobroId, ['pendiente_documentos' => 1, 'motivo' => $motivo]);
        $this->movimiento($cobro, 'mandar_a_completar', self::PENDIENTE, $motivo, $usuarioId);
    }

    /** Solo se cancelan cobros pendientes; un cobro aprobado ya tiene recibo. */
    public function cancelar(int $cobroId, string $motivo, ?int $usuarioId = null): void
    {
        $motivo = $this->motivoObligatorio($motivo);
        $cobro  = $this->pendiente($cobroId);

        $this->cobros->update($cobroId, ['estatus' => self::CANCELADO, 'motivo' => $motivo, 'pendiente_documentos' => 0]);
        $this->movimiento($cobro, 'cancelar', self::CANCELADO, $motivo, $usuarioId);
    }

    /**
     * Mantiene el cobro al día con el estatus de su solicitud. La llama
     * EstadoSolicitudService después de cada cambio de estatus.
     */
    public function sincronizarConSolicitud(int $solicitudId, string $nuevoEstatus, ?int $usuarioId = null, ?string $comentario = null): void
    {
        if ($nuevoEstatus === 'Pago pendiente') {
            $this->crearDesdeSolicitud($solicitudId, $usuarioId);
            return;
        }

        $pendiente = $this->cobros->where('solicitud_id', $solicitudId)->where('estatus', self::PENDIENTE)->first();
        if ($pendiente === null) {
            return;
        }

        if ($nuevoEstatus === 'Rechazado') {
            $this->cancelar((int) $pendiente->id, 'Solicitud rechazada: ' . ($comentario ?: 'sin comentario'), $usuarioId);
        } elseif ($nuevoEstatus === 'Pagado' || $nuevoEstatus === 'Concluido') {
            // Pago registrado por otra vía (pago en línea o cambio manual en el panel).
            $ahora = new DateTimeImmutable();
            $this->cobros->update((int) $pendiente->id, [
                'estatus'              => self::APROBADO,
                'pendiente_documentos' => 0,
                'cobrado_por'          => $usuarioId ?: null,
                'fecha_cobro'          => $ahora->format('Y-m-d H:i:s'),
                'folio_recibo'         => $this->siguienteFolioRecibo($ahora),
            ]);
            $this->movimiento($pendiente, 'cobrar', self::APROBADO, $comentario, $usuarioId, ['origen' => 'cambio de estatus de la solicitud']);
        }
    }

    /**
     * Crea el cobro de las solicitudes que ya estaban en "Pago pendiente" antes
     * de que existiera el módulo de cobros (o que se cargaron directo a la base,
     * como los datos demo). Es idempotente. Devuelve cuántos cobros creó.
     */
    public function crearPendientesFaltantes(?int $usuarioId = null): int
    {
        $sinCobro = \Config\Database::connect()->table('solicitudes s')
            ->select('s.id')
            ->join('cobros c', "c.solicitud_id = s.id AND c.estatus IN ('pendiente', 'aprobado')", 'left', false)
            ->where('s.estatus', 'Pago pendiente')
            ->where('c.id', null)
            ->orderBy('s.id', 'ASC')
            ->get()->getResult();

        foreach ($sinCobro as $fila) {
            $this->crearDesdeSolicitud((int) $fila->id, $usuarioId);
        }

        return count($sinCobro);
    }

    // ─── Consultas ────────────────────────────────────────────────────────────

    /** @return list<object> */
    public function historial(int $cobroId): array
    {
        return $this->movimientos->porCobro($cobroId);
    }

    // ─── Internos ─────────────────────────────────────────────────────────────

    private function insertar(array $datos, ?int $usuarioId): int
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $id = (int) $this->cobros->insert($datos + [
            'estatus'    => self::PENDIENTE,
            'created_by' => $usuarioId ?: null,
        ]);
        $folios = ['folio' => sprintf('COB-%s-%06d', date('Y'), $id)];
        // La columna llega con la migración 000032; las migraciones anteriores que
        // crean cobros corren antes de ella, y 000032 les asigna su referencia.
        if ($db->fieldExists('referencia_bancaria', 'cobros')) {
            $folios['referencia_bancaria'] = service('referenciaBancaria')->generar((string) $datos['origen'], $id);
        }
        $this->cobros->update($id, $folios);
        $this->movimiento((object) ['id' => $id, 'estatus' => null], 'crear', self::PENDIENTE, null, $usuarioId, ['total' => (float) ($datos['total'] ?? 0)]);

        $db->transComplete();
        if (! $db->transStatus()) {
            throw new CobroException('No se pudo crear el cobro.');
        }

        return $id;
    }

    private function pendiente(int $cobroId): object
    {
        $cobro = $this->cobros->find($cobroId);
        if ($cobro === null) {
            throw new CobroException('El cobro no existe.');
        }
        if ($cobro->estatus !== self::PENDIENTE) {
            throw new CobroException($cobro->estatus === self::APROBADO ? 'El cobro ya fue cobrado.' : 'El cobro ya fue cancelado.');
        }
        return $cobro;
    }

    private function motivoObligatorio(string $motivo): string
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new CobroException('Escriba el motivo.');
        }
        return $motivo;
    }

    private function movimiento(object $cobro, string $accion, string $estatusNuevo, ?string $motivo, ?int $usuarioId, array $detalle = []): void
    {
        $this->movimientos->insert([
            'cobro_id'         => (int) $cobro->id,
            'accion'           => $accion,
            'estatus_anterior' => $cobro->estatus ?? null,
            'estatus_nuevo'    => $estatusNuevo,
            'motivo'           => $motivo,
            'detalle'          => $detalle === [] ? null : json_encode($detalle, JSON_UNESCAPED_UNICODE),
            'usuario_id'       => $usuarioId ?: null,
            'fecha'            => date('Y-m-d H:i:s'),
        ]);
        model(AuditoriaModel::class)->registrar('cobros', (int) $cobro->id, $accion, $usuarioId ?: null, ['motivo' => $motivo] + $detalle);
    }

    /** Estatus al que pasa la solicitud al pagarse, o null si ya no está por pagar. */
    private function estatusPagadoDe(int $solicitudId): ?string
    {
        $solicitud = (new SolicitudModel())->find($solicitudId);
        if ($solicitud === null) {
            return null;
        }
        $estado = new EstadoSolicitudService();
        foreach (['Pagado', 'Concluido'] as $destino) {
            if ($estado->transicionValida((string) $solicitud->tramite, (string) $solicitud->estatus, $destino)) {
                return $destino;
            }
        }
        return null;
    }

    /** Descuento y recargo de una multa a la fecha de cobro; respeta lo condonado. */
    private function recalculoBoleta(object $cobro, DateTimeImmutable $fechaPago): array
    {
        $boleta  = (new BoletaInfraccionModel())->find((int) $cobro->boleta_id);
        $calculo = $this->montos->aplicarDescuentoRecargo((float) $cobro->monto_base, $boleta->fecha_infraccion ?? null, $fechaPago);
        $total   = max(0.0, round($calculo['monto_final'] - (float) $cobro->condonado, 2));

        return [
            'descuento'       => $calculo['monto_descuento'],
            'recargo'         => $calculo['monto_recargo'],
            'total'           => $total,
            'detalle_calculo' => json_encode($calculo, JSON_UNESCAPED_UNICODE),
        ];
    }

    /** REC-AAAA-NNNNNN, consecutivo por año. */
    private function siguienteFolioRecibo(DateTimeImmutable $fecha): string
    {
        $prefijo = 'REC-' . $fecha->format('Y') . '-';
        $ultimo  = $this->cobros->builder()->selectMax('folio_recibo', 'ultimo')
            ->like('folio_recibo', $prefijo, 'after')->get()->getRow();
        $n = $ultimo !== null && $ultimo->ultimo ? (int) substr((string) $ultimo->ultimo, strlen($prefijo)) : 0;

        return $prefijo . str_pad((string) ($n + 1), 6, '0', STR_PAD_LEFT);
    }

    private function nombreDeSolicitud(object $solicitud): string
    {
        if (! empty($solicitud->ciudadano_id)) {
            $usuario = (new UserModel())->find((int) $solicitud->ciudadano_id);
            $nombre  = trim((string) ($usuario->nombre_completo ?? ''));
            if ($nombre !== '') {
                return $nombre;
            }
        }

        $datos = (new SolicitudDatoModel())->where('solicitud_id', (int) $solicitud->id)
            ->whereIn('clave', ['nombre_solicitante', 'nombre_razon_social', 'razon_social_o_nombre', 'nombre_titular', 'nombre_concesionario', 'solicitante_nombre'])
            ->first();
        $nombre = trim((string) ($datos->valor ?? ''));

        return $nombre !== '' ? $nombre : 'Solicitante del folio ' . $solicitud->folio;
    }
}
