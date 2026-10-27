<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\BoletaCalificacionModel;
use App\Models\BoletaInfraccionModel;
use App\Models\CobroModel;
use App\Models\ParametroSistemaModel;
use App\Models\SolicitudDatoModel;
use App\Models\SolicitudModel;
use App\Models\UserModel;
use DateTimeImmutable;

/**
 * Hoja de ayuda para el pago (COB-05).
 *
 * Arma el desglose que se entrega impreso al ciudadano para que sepa cuánto
 * pagar, hasta cuándo conserva el descuento y dónde pagar. No cobra nada ni
 * cambia estatus: consume CobroService y ResolverMontoInfraccionService, que
 * siguen siendo los únicos dueños de las reglas de dinero.
 *
 * La hoja se imprime al registrar el trámite —cuando todavía no existe un
 * cobro— y también después, cuando el cobro ya está pendiente. En el primer
 * caso `folio_cobro` viene nulo y la hoja lo dice.
 */
class HojaPagoService
{
    /** Conceptos de la solicitud que sirven como nombre del pagador. */
    private const CLAVES_NOMBRE = [
        'nombre_solicitante',
        'nombre_razon_social',
        'razon_social_o_nombre',
        'nombre_titular',
        'nombre_concesionario',
        'solicitante_nombre',
    ];

    protected CobroModel $cobros;
    protected ResolverMontoInfraccionService $montos;
    protected ParametroSistemaModel $parametros;

    public function __construct(
        ?CobroModel $cobros = null,
        ?ResolverMontoInfraccionService $montos = null,
        ?ParametroSistemaModel $parametros = null
    ) {
        $this->cobros     = $cobros ?? new CobroModel();
        $this->montos     = $montos ?? new ResolverMontoInfraccionService();
        $this->parametros = $parametros ?? new ParametroSistemaModel();

        // tramite_nombre() y formatear_dinero() viven en el helper del proyecto.
        helper('url_helper_custom');
    }

    /**
     * Desglose de una solicitud de trámite.
     *
     * Los trámites no tienen descuento ni recargo: el total es el monto de la
     * solicitud, con el cobro vigente como fuente cuando ya existe.
     *
     * @return array<string, mixed>|null
     */
    public function paraSolicitud(int $solicitudId): ?array
    {
        $solicitud = (new SolicitudModel())->find($solicitudId);
        if ($solicitud === null) {
            return null;
        }

        $datos = (new SolicitudDatoModel())->porSolicitudAgrupado($solicitudId);
        $cobro = $this->cobros->vigenteDeSolicitud($solicitudId);
        $monto = round((float) ($cobro->monto_base ?? $solicitud->monto ?? 0), 2);

        return [
            'origen'                 => 'solicitud',
            'titulo'                 => 'Hoja de ayuda para el pago',
            'folio'                  => (string) $solicitud->folio,
            'folio_cobro'            => $cobro?->folio,
            'folio_recibo'           => $cobro?->folio_recibo,
            'fecha'                  => $this->fecha((string) ($solicitud->fecha_solicitud ?? $solicitud->created_at ?? '')),
            'nombre'                 => $this->nombreDeSolicitud($solicitud, $datos),
            'concepto'               => $this->conceptoDeSolicitud($solicitud),
            'conceptos'              => [[
                'etiqueta' => $this->conceptoDeSolicitud($solicitud),
                'monto'    => $monto,
            ]],
            'monto_base'             => $monto,
            'descuento'              => 0.0,
            'recargo'                => 0.0,
            'condonado'              => round((float) ($cobro->condonado ?? 0), 2),
            'total'                  => round((float) ($cobro->total ?? $monto), 2),
            'estatus'                => $cobro?->estatus ?? 'sin cobro',
            'lugar_pago'             => $cobro?->lugar_pago,
            'referencia'             => $this->referencia($datos, $cobro),
            'fecha_limite_descuento' => null,
            'aplica_descuento'       => false,
            'meses_mora'             => 0,
            'porcentaje_descuento'   => 0.0,
            'porcentaje_recargo'     => 0.0,
            'notas'                  => [
                'El monto puede cambiar si el trámite se rechaza o si cambia la tarifa antes del pago.',
                'Conserva esta hoja y el folio: son tu comprobante para cualquier aclaración.',
            ],
        ];
    }

    /**
     * Desglose de una multa de tránsito.
     *
     * Descuento y recargo se recalculan a la fecha de consulta, porque el cobro
     * los vuelve a calcular al momento de pagar: si la hoja dijera el importe
     * del día en que se generó el cobro, el ciudadano pagaría otra cosa.
     *
     * @return array<string, mixed>|null
     */
    public function paraBoleta(int $boletaId): ?array
    {
        $boleta = (new BoletaInfraccionModel())->find($boletaId);
        if ($boleta === null) {
            return null;
        }

        $cobro        = $this->cobros->vigenteDeBoleta($boletaId);
        $calificacion = (new BoletaCalificacionModel())
            ->where('boleta_id', $boletaId)
            ->orderBy('id', 'DESC')
            ->first();

        $montoBase = round((float) ($cobro->monto_base ?? $calificacion->monto_base ?? 0), 2);
        $autorizado = round((float) ($cobro->condonado ?? 0), 2);
        $aprobado   = ($cobro->estatus ?? null) === CobroService::APROBADO;

        if ($aprobado) {
            // Un pago registrado no cambia por imprimir otro día: se usan los importes guardados al cobrar.
            $guardado  = json_decode((string) ($cobro->detalle_calculo ?? ''), true);
            $calculo   = is_array($guardado) ? $guardado : [];
            $descuento = round((float) $cobro->descuento, 2);
            $recargo   = round((float) $cobro->recargo, 2);
            $condonado = $autorizado;
            $total     = round((float) $cobro->total, 2);
        } else {
            $calculo = $montoBase > 0
                ? $this->montos->aplicarDescuentoRecargo($montoBase, $boleta->fecha_infraccion ?? null)
                : null;

            $descuento = round((float) ($calculo['monto_descuento'] ?? 0), 2);
            $recargo   = round((float) ($calculo['monto_recargo'] ?? 0), 2);
            $condonado = CondonacionRecargo::aplicado($autorizado, $recargo);
            $total     = $calculo !== null ? CondonacionRecargo::total($calculo, $autorizado) : 0.0;
        }

        return [
            'origen'                 => 'boleta',
            'titulo'                 => 'Hoja de ayuda para el pago de la multa',
            'folio'                  => (string) $boleta->folio,
            'folio_cobro'            => $cobro?->folio,
            'folio_recibo'           => $cobro?->folio_recibo,
            'fecha'                  => $this->fecha((string) ($boleta->fecha_infraccion ?? '')),
            'nombre'                 => $this->nombreDeBoleta($boleta, $cobro),
            'concepto'               => 'Multa de tránsito · boleta ' . $boleta->folio,
            'conceptos'              => [[
                'etiqueta' => 'Multa de tránsito · boleta ' . $boleta->folio,
                'monto'    => $montoBase,
            ]],
            'monto_base'             => $montoBase,
            'descuento'              => $descuento,
            'recargo'                => $recargo,
            'condonado'              => $condonado,
            'total'                  => $total,
            'estatus'                => $cobro?->estatus ?? 'pendiente',
            'lugar_pago'             => $cobro?->lugar_pago,
            'referencia'             => $cobro?->referencia_bancaria,
            'fecha_limite_descuento' => $this->fechaLimiteDescuento((string) ($boleta->fecha_infraccion ?? '')),
            'aplica_descuento'       => (bool) ($calculo['aplica_descuento'] ?? false),
            'meses_mora'             => (int) ($calculo['meses_mora'] ?? 0),
            'porcentaje_descuento'   => (float) ($calculo['porcentaje_descuento'] ?? 0),
            'porcentaje_recargo'     => (float) ($calculo['porcentaje_recargo_mensual'] ?? 0),
            'importes_registrados'   => $aprobado,
            'fecha_cobro'            => $aprobado ? $this->fecha((string) ($cobro->fecha_cobro ?? '')) : null,
                        'notas'                  => $montoBase > 0
                            ? [
                    'El descuento por pronto pago aplica solo dentro de los días hábiles indicados; después se paga el monto completo.',
                    'Pasado un mes de la infracción se suma el recargo mensual por mora (Art. 180 del Reglamento de Tránsito).',
                ]
                : [
                    'La multa aún no tiene monto fijado: el Juez Calificador debe calificar la boleta.',
                ],
        ];
    }

    /**
     * Último día en que aplica el descuento por pronto pago: se cuentan solo
     * lunes a viernes a partir del día hábil siguiente a la infracción, igual
     * que ResolverMontoInfraccionService::contarDiasHabiles().
     */
    public function fechaLimiteDescuento(string $fechaInfraccion): ?string
    {
        $fechaInfraccion = trim($fechaInfraccion);
        if ($fechaInfraccion === '') {
            return null;
        }

        $limite = (int) $this->parametros->getValor('dias_habiles_descuento', '10');
        $limite = $limite > 0 ? $limite : 10;

        $dia      = new DateTimeImmutable($fechaInfraccion);
        $contados = 0;
        while ($contados < $limite) {
            $dia = $dia->modify('+1 day');
            if ((int) $dia->format('N') <= 5) {
                $contados++;
            }
        }

        return $dia->format('Y-m-d');
    }

    /**
     * Dónde puede pagarse. Se arma desde CobroService::LUGARES_PAGO para que no
     * exista un catálogo paralelo de lugares de pago.
     *
     * @return list<array{clave:string,titulo:string,detalle:string}>
     */
    public function lugaresDePago(): array
    {
        $detalles = [
            'juez_calificador' => 'Ventanilla de Tránsito Municipal, de lunes a viernes en horario de oficina.',
            'banbajio'         => 'Ventanilla bancaria o transferencia con la referencia del convenio.',
        ];

        $lugares = [];
        foreach ($detalles as $clave => $detalle) {
            if (! array_key_exists($clave, CobroService::LUGARES_PAGO)) {
                continue;
            }
            $lugares[] = [
                'clave'   => $clave,
                'titulo'  => CobroService::LUGARES_PAGO[$clave],
                'detalle' => $detalle,
            ];
        }

        return $lugares;
    }

    // ─── Internos ─────────────────────────────────────────────────────────────

    private function conceptoDeSolicitud(object $solicitud): string
    {
        return tramite_nombre((string) $solicitud->tramite) . ' · folio ' . $solicitud->folio;
    }

    /** @param array<string, mixed> $datos */
    private function nombreDeSolicitud(object $solicitud, array $datos): string
    {
        if (! empty($solicitud->ciudadano_id)) {
            $usuario = (new UserModel())->find((int) $solicitud->ciudadano_id);
            $nombre  = trim((string) ($usuario->nombre_completo ?? ''));
            if ($nombre !== '') {
                return $nombre;
            }
        }

        foreach (self::CLAVES_NOMBRE as $clave) {
            $nombre = trim((string) ($datos[$clave] ?? ''));
            if ($nombre !== '') {
                return $nombre;
            }
        }

        return 'Solicitante del folio ' . $solicitud->folio;
    }

    private function nombreDeBoleta(object $boleta, ?object $cobro): string
    {
        foreach ([$cobro->nombre_pagador ?? null, $boleta->infractor_nombre ?? null] as $candidato) {
            $nombre = trim((string) $candidato);
            if ($nombre !== '' && strcasecmp($nombre, 'Conductor Ausente') !== 0) {
                return $nombre;
            }
        }

        return 'Propietario del vehículo ' . ($boleta->vehiculo_placas ?: 'sin placas');
    }

    /** @param array<string, mixed> $datos */
    private function referencia(array $datos, ?object $cobro): ?string
    {
        // COB-04: la referencia para pagar en BanBajío es la del cobro. referencia_pago
        // es el comprobante que se captura después de pagar.
        foreach ([$cobro->referencia_bancaria ?? null, $datos['referencia_pago'] ?? null] as $candidato) {
            $referencia = trim((string) $candidato);
            if ($referencia !== '') {
                return $referencia;
            }
        }

        return null;
    }

    private function fecha(string $fecha): ?string
    {
        $fecha = trim($fecha);
        if ($fecha === '') {
            return null;
        }

        return substr($fecha, 0, 10);
    }
}
