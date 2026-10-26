<?php declare(strict_types=1);

namespace App\Database\Seeds;

use App\Libraries\EstadoSolicitudService;
use App\Libraries\TarifarioService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Seeder;

/**
 * Datos de demostración "en funcionamiento" para presentaciones y revisiones.
 *
 * Crea ciudadanos, solicitudes de los trámites en distintos estatus (con su
 * historial), una convocatoria abierta y boletas de infracción de las últimas
 * semanas. Las fechas son relativas al día en que se ejecuta.
 *
 * - Idempotente: si se vuelve a correr no duplica nada.
 * - Reversible: `php spark demo:limpiar` borra exactamente lo que crea, porque
 *   folios, CURP y título de la convocatoria son deterministas.
 * - Funciona en SQLite (local) y Postgres (Supabase).
 *
 * Requiere haber corrido antes DatabaseSeeder (roles, concesiones, catálogo,
 * agentes) y las migraciones.
 *
 *     php spark db:seed DemoFuncionamientoSeeder
 */
class DemoFuncionamientoSeeder extends Seeder
{
    /** Contraseña de todas las cuentas demo de ciudadano. */
    public const PASSWORD_DEMO = 'Demo2026';

    public const TITULO_CONVOCATORIA = 'Convocatoria 2026 · Transporte público colectivo';

    /** [CURP, nombre, apellido, teléfono, domicilio, RFC] — personas ficticias. */
    public const CIUDADANOS = [
        ['RAGL880214MGTMRR03', 'Laura', 'Ramírez Gómez', '4451203344', 'Calle Hidalgo 118, Col. Centro, Uriangato', 'RAGL880214QK3'],
        ['MOSJ790611HGTRNN08', 'Juan Carlos', 'Morales Sánchez', '4451557890', 'Av. Morelos 402, Col. Juárez, Uriangato', 'MOSJ790611B21'],
        ['TOVA920830MGTRLN05', 'Ana Karen', 'Torres Valdez', '4451889012', 'Calle Allende 35, Col. San José, Uriangato', null],
        ['GUPE850107HGTTRD01', 'Pedro', 'Gutiérrez Pérez', '4451324567', 'Calle Guerrero 77, Col. Lindavista, Uriangato', 'GUPE850107H54'],
        ['HEMM950322MGTRRR09', 'María Fernanda', 'Hernández Murillo', '4452017788', 'Privada Juárez 12, Col. Las Fuentes, Uriangato', null],
        ['LOCR810915HGTPHB02', 'Roberto', 'López Chávez', '4451446655', 'Calle Abasolo 201, Col. Centro, Uriangato', 'LOCR810915AA7'],
        ['VEGS900428MGTLRF06', 'Sofía', 'Vega García', '4451998877', 'Av. Lázaro Cárdenas 510, Col. El Cerrito, Uriangato', null],
        ['CAMD870703HGTSRV04', 'David', 'Castro Martínez', '4451775533', 'Calle Zaragoza 64, Col. Juárez, Uriangato', 'CAMD870703PL9'],
        ['ROJP930119MGTDMT07', 'Patricia', 'Rodríguez Jiménez', '4452113344', 'Calle Mina 15, Col. San Isidro, Uriangato', null],
        ['BASM760925HGTTTG03', 'Miguel Ángel', 'Bautista Soto', '4451660011', 'Carretera Uriangato–Moroleón km 2, Uriangato', 'BASM760925TB4'],
    ];

    /**
     * Solicitudes: [clave, trámite, índice de ciudadano, estatus final, días atrás, origen].
     * El historial se reconstruye recorriendo las transiciones válidas.
     */
    public const SOLICITUDES = [
        ['s01', 'UR-TT-T-01', 9, 'En revisión', 9, 'portal'],
        ['s02', 'UR-TT-T-01', 1, 'Evaluación comparativa', 14, 'portal'],
        ['s03', 'UR-TT-T-01', 3, 'Recibido', 1, 'presencial'],
        ['s04', 'UR-TT-T-02', 5, 'Recibido', 2, 'portal'],
        ['s05', 'UR-TT-T-02', 7, 'Cita agendada', 6, 'portal'],
        ['s06', 'UR-TT-T-02', 1, 'Verificado', 20, 'presencial'],
        ['s07', 'UR-TT-T-03', 3, 'Recibido', 0, 'portal'],
        ['s08', 'UR-TT-T-03', 5, 'En revisión', 5, 'presencial'],
        ['s09', 'UR-TT-T-03', 7, 'Aprobado', 18, 'portal'],
        ['s10', 'UR-TT-T-03', 9, 'Rechazado', 12, 'portal'],
        ['s11', 'UR-TT-T-04', 9, 'Recibido', 1, 'portal'],
        ['s12', 'UR-TT-T-04', 3, 'En revisión documental', 4, 'presencial'],
        ['s13', 'UR-TT-T-04', 1, 'Prevención', 8, 'portal'],
        ['s14', 'UR-TT-T-04', 5, 'Autorizado para pago', 25, 'portal'],
        ['s15', 'UR-TT-T-05', 0, 'Recibido', 0, 'portal'],
        ['s16', 'UR-TT-T-05', 2, 'Pago pendiente', 3, 'presencial'],
        ['s17', 'UR-TT-T-05', 4, 'Pagado', 7, 'portal'],
        ['s18', 'UR-TT-T-05', 6, 'Permiso emitido', 15, 'portal'],
        ['s19', 'UR-TT-T-05', 8, 'Rechazado', 10, 'portal'],
        ['s20', 'UR-TT-T-07', 0, 'Recibido', 1, 'presencial'],
        ['s21', 'UR-TT-T-07', 9, 'Pago pendiente', 2, 'portal'],
        ['s22', 'UR-TT-T-07', 6, 'Pagado', 6, 'portal'],
        ['s23', 'UR-TT-T-07', 2, 'Vigente', 22, 'portal'],
        ['s24', 'UR-TT-T-07', 4, 'Vigente', 35, 'presencial'],
    ];

    public const TOTAL_BOLETAS = 26;

    public function run(): void
    {
        mt_srand(2026);
        $hoy = new \DateTimeImmutable('today');

        $operadorId    = $this->usuarioConRol(['operador_ventanilla', 'administrador']);
        $ciudadanos    = $this->crearCiudadanos();
        $convocatoriaId = $this->crearConvocatoria($hoy);
        $concesionId   = (int) ($this->db->table('concesiones')->select('id')->orderBy('id', 'ASC')->get()->getRow()->id ?? 0) ?: null;

        $creadas = 0;
        foreach (self::SOLICITUDES as $spec) {
            $creadas += $this->crearSolicitud($spec, $ciudadanos, $operadorId, $convocatoriaId, $concesionId, $hoy) ? 1 : 0;
        }

        $boletas = $this->crearBoletas($hoy);

        // Las solicitudes demo en "Pago pendiente" se insertan directo, sin pasar
        // por EstadoSolicitudService: se les crea su cobro para el listado.
        if ($this->db->tableExists('cobros')) {
            (new \App\Libraries\CobroService())->crearPendientesFaltantes($operadorId);
        }

        echo sprintf(
            "Datos demo listos: %d ciudadanos, %d solicitudes nuevas, %d boletas nuevas. Contraseña de ciudadanos demo: %s\n",
            count($ciudadanos), $creadas, $boletas, self::PASSWORD_DEMO
        );
    }

    // ─── Identificadores deterministas (también los usa demo:limpiar) ─────────

    public static function folioSolicitud(string $clave): string
    {
        return strtoupper(substr(md5('demo-funcionamiento-' . $clave), 0, 20));
    }

    public static function folioBoleta(int $n, string $placa): string
    {
        $placa = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $placa) ?? '');
        return 'BOLETA-URI-2026-' . $placa . '-' . strtoupper(substr(md5('demo-boleta-' . $n), 0, 8)) . '-' . base_convert((string) $n, 10, 36);
    }

    /** @return list<string> */
    public static function curps(): array
    {
        return array_map(static fn (array $c) => $c[0], self::CIUDADANOS);
    }

    /**
     * Borra todo lo que crea este seeder. Devuelve un resumen por tabla.
     *
     * @return array<string, int>
     */
    public static function limpiar(BaseConnection $db): array
    {
        $resumen = [];
        $folios = array_map(static fn (array $s) => self::folioSolicitud($s[0]), self::SOLICITUDES);
        $ids = array_map(static fn ($r) => (int) $r->id, $db->table('solicitudes')->select('id')->whereIn('folio', $folios)->get()->getResult());

        $db->transStart();
        if ($ids !== []) {
            // Los cobros no se borran en cascada con su solicitud; los demo sí se quitan.
            if ($db->tableExists('cobros')) {
                $db->table('cobros')->whereIn('solicitud_id', $ids)->delete();
                $resumen['cobros'] = $db->affectedRows();
            }
            foreach (['historial_estatus', 'solicitud_datos', 'documentos', 'verificaciones_fisicas'] as $tabla) {
                $db->table($tabla)->whereIn('solicitud_id', $ids)->delete();
                $resumen[$tabla] = $db->affectedRows();
            }
            $db->table('solicitudes')->whereIn('id', $ids)->delete();
        }
        $resumen['solicitudes'] = count($ids);

        // Ciudadanos demo, solo si ya no tienen otras solicitudes (por si alguien
        // registró algo real con esa cuenta durante la presentación).
        $usuarios = $db->table('users')->select('id')->whereIn('curp', self::curps())->get()->getResult();
        $borrados = 0;
        foreach ($usuarios as $u) {
            if ($db->table('solicitudes')->where('ciudadano_id', $u->id)->countAllResults() > 0) {
                continue;
            }
            $db->table('user_roles')->where('user_id', $u->id)->delete();
            $db->table('users')->where('id', $u->id)->delete();
            $borrados++;
        }
        $resumen['users'] = $borrados;

        $foliosBoletas = [];
        foreach (self::agentesDemo() as $placa) {
            for ($n = 1; $n <= self::TOTAL_BOLETAS; $n++) {
                $foliosBoletas[] = self::folioBoleta($n, $placa);
            }
        }
        if ($db->tableExists('cobros')) {
            $boletaIds = array_map(static fn ($r) => (int) $r->id, $db->table('boletas_infracciones')->select('id')->whereIn('folio', $foliosBoletas)->get()->getResult());
            if ($boletaIds !== []) {
                $db->table('cobros')->whereIn('boleta_id', $boletaIds)->delete();
            }
        }
        $db->table('boletas_infracciones')->whereIn('folio', $foliosBoletas)->delete();
        $resumen['boletas_infracciones'] = $db->affectedRows();

        $conv = $db->table('convocatorias')->where('titulo', self::TITULO_CONVOCATORIA)->get()->getRow();
        if ($conv !== null && $db->table('solicitudes')->where('convocatoria_id', $conv->id)->countAllResults() === 0) {
            $db->table('convocatorias')->where('id', $conv->id)->delete();
            $resumen['convocatorias'] = 1;
        }
        $db->transComplete();

        return $resumen;
    }

    /** @return list<string> */
    private static function agentesDemo(): array
    {
        return ['AGT-204', 'AGT-107', 'JEF-001'];
    }

    // ─── Ciudadanos y convocatoria ────────────────────────────────────────────

    /** @return list<int> ids en el mismo orden que CIUDADANOS */
    private function crearCiudadanos(): array
    {
        $rolCiudadano = $this->db->table('roles')->where('nombre', 'ciudadano')->get()->getRow();
        $ids = [];
        foreach (self::CIUDADANOS as [$curp, $nombre, $apellido, $telefono, $domicilio, $rfc]) {
            $existente = $this->db->table('users')->where('curp', $curp)->get()->getRow();
            if ($existente !== null) {
                $ids[] = (int) $existente->id;
                continue;
            }
            $email = $this->slug($nombre) . '.' . $this->slug(explode(' ', $apellido)[0]) . '@example.com';
            $ahora = date('Y-m-d H:i:s');
            $this->db->table('users')->insert([
                'username'        => strtolower($curp),
                'email'           => $email,
                'password_hash'   => password_hash(self::PASSWORD_DEMO, PASSWORD_DEFAULT),
                'nombre_completo' => $nombre . ' ' . $apellido,
                'apellido'        => $apellido,
                'curp'            => $curp,
                'rfc'             => $rfc,
                'telefono'        => $telefono,
                'domicilio'       => $domicilio,
                'estado'          => 'Guanajuato',
                'ciudad'          => 'Uriangato',
                'activo'          => 1,
                'created_at'      => $ahora,
                'updated_at'      => $ahora,
            ]);
            $id = (int) $this->db->insertID();
            if ($rolCiudadano !== null) {
                $this->db->table('user_roles')->insert(['user_id' => $id, 'role_id' => $rolCiudadano->id, 'created_at' => $ahora, 'updated_at' => $ahora]);
            }
            $ids[] = $id;
        }
        return $ids;
    }

    private function crearConvocatoria(\DateTimeImmutable $hoy): ?int
    {
        $existente = $this->db->table('convocatorias')->where('titulo', self::TITULO_CONVOCATORIA)->get()->getRow();
        if ($existente !== null) {
            return (int) $existente->id;
        }
        $ahora = date('Y-m-d H:i:s');
        $this->db->table('convocatorias')->insert([
            'titulo'                  => self::TITULO_CONVOCATORIA,
            'fecha_publicacion'       => $hoy->modify('-20 days')->format('Y-m-d'),
            'periodo_registro_inicio' => $hoy->modify('-15 days')->format('Y-m-d'),
            'periodo_registro_fin'    => $hoy->modify('+30 days')->format('Y-m-d'),
            'bases'                   => 'Se convoca a personas físicas y morales interesadas en prestar el servicio de transporte público colectivo en la ruta Centro – Presa de Uriangato. Requisitos: acta, constancia de situación fiscal, capacidad financiera, residencia, no antecedentes penales, proyecto técnico de rutas y documentación de vehículos.',
            'estatus'                 => 'Vigente',
            'created_at'              => $ahora,
            'updated_at'              => $ahora,
        ]);
        return (int) $this->db->insertID();
    }

    // ─── Solicitudes ──────────────────────────────────────────────────────────

    /**
     * @param array{0:string,1:string,2:int,3:string,4:int,5:string} $spec
     * @param list<int> $ciudadanos
     */
    private function crearSolicitud(array $spec, array $ciudadanos, ?int $operadorId, ?int $convocatoriaId, ?int $concesionId, \DateTimeImmutable $hoy): bool
    {
        [$clave, $tramite, $idx, $estatusFinal, $diasAtras, $origen] = $spec;
        $folio = self::folioSolicitud($clave);
        if ($this->db->table('solicitudes')->where('folio', $folio)->countAllResults() > 0) {
            return false;
        }

        $ciudadanoId = $ciudadanos[$idx];
        $ciudadano   = self::CIUDADANOS[$idx];
        $camino      = $this->caminoEstatus($tramite, $estatusFinal);
        $inicio      = $hoy->modify("-{$diasAtras} days")->setTime(9 + ($diasAtras % 6), 10 + ($diasAtras * 7) % 50);
        // Las transiciones se reparten entre la fecha de solicitud y hoy.
        $pasos = max(1, count($camino) - 1);
        $fechas = [];
        foreach ($camino as $i => $_) {
            $horas = (int) floor(($diasAtras * 24 * 0.8) * ($i / $pasos));
            $fechas[] = $inicio->modify("+{$horas} hours");
        }
        $ultima = end($fechas);

        $pagado = in_array($estatusFinal, ['Pagado', 'Permiso emitido', 'Vigente'], true);
        $fechaPago = null;
        foreach ($camino as $i => $est) {
            if ($est === 'Pagado') {
                $fechaPago = $fechas[$i];
            }
        }

        $row = [
            'folio'           => $folio,
            'tramite'         => $tramite,
            'ciudadano_id'    => $ciudadanoId,
            'concesion_id'    => in_array($tramite, ['UR-TT-T-02', 'UR-TT-T-03'], true) ? $concesionId : null,
            'convocatoria_id' => $tramite === 'UR-TT-T-01' ? $convocatoriaId : null,
            'estatus'         => $estatusFinal,
            'monto'           => $this->monto($tramite),
            'fecha_solicitud' => $inicio->format('Y-m-d H:i:s'),
            'created_at'      => $inicio->format('Y-m-d H:i:s'),
            'updated_at'      => $ultima->format('Y-m-d H:i:s'),
        ];
        if (in_array($estatusFinal, ['Aprobado', 'Verificado', 'Rechazado', 'Permiso emitido', 'Vigente'], true)) {
            $row['fecha_resolucion'] = $ultima->format('Y-m-d H:i:s');
        }
        if ($pagado && $fechaPago !== null) {
            $row['fecha_pago'] = $fechaPago->format('Y-m-d H:i:s');
            $row['fecha_vigencia_inicio'] = $fechaPago->format('Y-m-d');
            $row['fecha_vigencia_fin'] = $fechaPago->modify($tramite === 'UR-TT-T-07' ? '+1 month' : '+1 day')->format('Y-m-d');
        }
        if ($estatusFinal === 'Rechazado') {
            $row['comentario_rechazo'] = $this->motivoRechazo($tramite);
        }

        $this->db->transStart();
        $this->db->table('solicitudes')->insert($row);
        $solicitudId = (int) $this->db->insertID();

        $datos = ['origen' => $origen] + $this->datosTramite($tramite, $ciudadano, $hoy, $diasAtras);
        $ahora = date('Y-m-d H:i:s');
        foreach ($datos as $k => $v) {
            $this->db->table('solicitud_datos')->insert(['solicitud_id' => $solicitudId, 'clave' => $k, 'valor' => (string) $v, 'created_at' => $ahora, 'updated_at' => $ahora]);
        }

        $anterior = null;
        foreach ($camino as $i => $est) {
            $this->db->table('historial_estatus')->insert([
                'solicitud_id'     => $solicitudId,
                'estatus_anterior' => $anterior,
                'estatus_nuevo'    => $est,
                'usuario_id'       => $i === 0 && $origen === 'portal' ? $ciudadanoId : $operadorId,
                'fecha'            => $fechas[$i]->format('Y-m-d H:i:s'),
                'comentario'       => $this->comentarioEstatus($est, $origen, $tramite),
                'created_at'       => $fechas[$i]->format('Y-m-d H:i:s'),
                'updated_at'       => $fechas[$i]->format('Y-m-d H:i:s'),
            ]);
            $anterior = $est;
        }

        if ($tramite === 'UR-TT-T-02' && in_array($estatusFinal, ['Cita agendada', 'Verificado'], true)) {
            $cita = $estatusFinal === 'Verificado' ? $ultima->modify('-1 day') : $hoy->modify('+3 days')->setTime(10, 0);
            $this->db->table('verificaciones_fisicas')->insert([
                'solicitud_id'  => $solicitudId,
                'fecha_cita'    => $cita->format('Y-m-d H:i:s'),
                'resultado'     => $estatusFinal === 'Verificado' ? 'aprobado' : null,
                'observaciones' => $estatusFinal === 'Verificado' ? 'Unidad sin cromática del servicio público. Se verifica despintado completo.' : null,
                'created_at'    => $ahora,
                'updated_at'    => $ahora,
            ]);
        }
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Camino más corto de "Recibido" al estatus final según las transiciones válidas.
     *
     * @return list<string>
     */
    private function caminoEstatus(string $tramite, string $final): array
    {
        $grafo = EstadoSolicitudService::TRANSICIONES_VALIDAS[$tramite] ?? [];
        $cola = [['Recibido']];
        $visto = ['Recibido' => true];
        while ($cola !== []) {
            $camino = array_shift($cola);
            $actual = end($camino);
            if ($actual === $final) {
                return $camino;
            }
            foreach ($grafo[$actual] ?? [] as $sig) {
                if (! isset($visto[$sig])) {
                    $visto[$sig] = true;
                    $cola[] = [...$camino, $sig];
                }
            }
        }
        return ['Recibido'];
    }

    private function monto(string $tramite): float
    {
        $tarifario = new TarifarioService();
        $monto = $tramite === 'UR-TT-T-07'
            ? $tarifario->calcularMontoUrTtT07('particular', 'mes', 1)
            : $tarifario->calcularMonto($tramite, 'base');
        $respaldo = ['UR-TT-T-01' => 9055.20, 'UR-TT-T-02' => 64.90, 'UR-TT-T-03' => 50.00, 'UR-TT-T-04' => 156.94, 'UR-TT-T-05' => 287.00, 'UR-TT-T-07' => 400.00];
        return (float) ($monto ?? $respaldo[$tramite]);
    }

    /** @param array{0:string,1:string,2:string,3:string,4:string,5:?string} $c */
    private function datosTramite(string $tramite, array $c, \DateTimeImmutable $hoy, int $diasAtras): array
    {
        [$curp, $nombre, $apellido, $telefono, $domicilio, $rfc] = $c;
        $nombreCompleto = $nombre . ' ' . $apellido;
        $rfc ??= substr($curp, 0, 10) . 'XX1';
        $placas = ['GTO-412-A', 'GTO-873-B', 'GTB-219-C', 'GTO-556-D', 'GTC-308-E'][$diasAtras % 5];
        $vin = '3N1CN7AD' . str_pad((string) (100000 + $diasAtras * 4571 % 899999), 9, '0', STR_PAD_LEFT);

        return match ($tramite) {
            'UR-TT-T-01' => [
                'tramite_concepto' => 'Otorgamiento de Concesión de Transporte Público',
                'solicitante_nombre' => $nombreCompleto, 'tipo_persona' => 'fisica',
                'rfc' => $rfc, 'domicilio' => $domicilio, 'tipo_servicio' => 'Colectivo urbano, ruta Centro – Presa', 'num_vehiculos' => (string) (2 + $diasAtras % 4),
            ],
            'UR-TT-T-02' => [
                'tramite_concepto' => 'Constancia de Despintado', 'numero_titulo_concesion' => 'CONC-URI-2024-0001',
                'nombre_titular' => $nombreCompleto, 'vehiculo_placas' => $placas, 'vehiculo_num_serie' => $vin,
                'motivo_despintado' => 'Sustitución de la unidad por una de modelo reciente.',
            ],
            'UR-TT-T-03' => [
                'tramite_concepto' => 'Orden de Plaqueo', 'numero_titulo_concesion' => 'CONC-URI-2024-0002',
                'nombre_concesionario' => $nombreCompleto, 'tipo_persona' => 'fisica', 'numero_factura' => 'FAC-' . (8800 + $diasAtras * 37),
                'vehiculo_placas' => $placas, 'vehiculo_num_serie' => $vin,
            ],
            'UR-TT-T-04' => [
                'tramite_concepto' => 'Permiso Eventual de Transporte', 'nombre_razon_social' => $nombreCompleto,
                'tipo_persona' => 'fisica', 'rfc' => $rfc, 'domicilio' => $domicilio,
                'tipo_servicio' => 'Pasajeros', 'tipo_unidad' => 'Microbús', 'cantidad_unidades' => '2',
                'lugar_servicio' => 'Centro – Col. El Cerrito', 'zona_servicio' => 'Ruta 3', 'motivo_necesidad' => 'descompostura',
                'descripcion_necesidad' => 'La unidad titular está en reparación mayor de motor; se requiere cubrir el servicio mientras regresa.',
                'vigencia_observacion' => 'Durante el tiempo que subsista la contingencia justificada',
            ],
            'UR-TT-T-05' => [
                'tramite_concepto' => 'Permiso para Cierre de Calle', 'nombre_solicitante' => $nombreCompleto, 'domicilio' => $domicilio,
                'fecha_cierre' => $hoy->modify('+' . (5 + $diasAtras % 10) . ' days')->format('Y-m-d'), 'hora_inicio' => '16:00', 'hora_fin' => '23:00',
                'calle_tramo' => ['Calle Hidalgo entre Juárez y Morelos', 'Calle Allende entre Mina y Abasolo', 'Privada Juárez completa'][$diasAtras % 3],
                'colonia' => 'Centro', 'tipo_cierre' => $diasAtras % 2 === 0 ? 'parcial' : 'total',
                'motivo_evento' => ['Fiesta familiar', 'Posada vecinal', 'Kermés de la parroquia'][$diasAtras % 3],
                'descripcion_evento' => 'Colocación de toldo, mesas y sillas; se deja paso libre a vehículos de emergencia.',
                'afluencia_baja' => '1', 'sin_transporte_publico' => '1', 'horario_no_entorpece' => '1',
            ],
            'UR-TT-T-07' => [
                'tramite_concepto' => 'Permiso de Carga y Descarga', 'tipo_solicitante' => 'particular', 'razon_social_o_nombre' => $nombreCompleto,
                'rfc' => $rfc, 'domicilio_negocio' => $domicilio, 'direccion_carga_descarga' => 'Frente al local comercial, Calle Hidalgo',
                'periodo' => 'mes', 'horario_inicio' => '06:00', 'horario_fin' => '09:00', 'es_mudanza' => '0',
            ],
            default => [],
        };
    }

    private function comentarioEstatus(string $estatus, string $origen, string $tramite): string
    {
        return match ($estatus) {
            'Recibido' => $origen === 'presencial' ? 'Solicitud registrada presencialmente por ventanilla.' : 'Solicitud registrada por el ciudadano en el portal.',
            'En revisión', 'En revisión documental' => 'Expediente turnado a revisión documental.',
            'Prevención' => 'Falta la póliza de seguro vigente de la unidad. Se previene al solicitante.',
            'Evaluación comparativa' => 'Expediente completo; pasa a evaluación comparativa de la convocatoria.',
            'Cita agendada' => 'Cita de verificación física agendada.',
            'Verificado' => 'Verificación física aprobada.',
            'Aprobado' => 'Documentación correcta. Se emite orden de plaqueo.',
            'En validación' => 'Solicitud recibida para validación inmediata.',
            'Pago pendiente' => 'Criterios validados. Pago autorizado.',
            'Pagado' => 'Pago confirmado en línea.',
            'Permiso emitido' => 'Permiso emitido y disponible para descarga.',
            'Vigente' => 'Permiso vigente.',
            'Rechazado' => $this->motivoRechazo($tramite),
            default => 'Cambio de estatus.',
        };
    }

    private function motivoRechazo(string $tramite): string
    {
        return $tramite === 'UR-TT-T-05'
            ? 'La calle solicitada es ruta de transporte público; no procede el cierre.'
            : 'El título de concesión no coincide con el titular registrado en el padrón.';
    }

    // ─── Boletas de infracción ────────────────────────────────────────────────

    private function crearBoletas(\DateTimeImmutable $hoy): int
    {
        $agentes = [];
        foreach ($this->db->table('agentes_transito')->whereIn('placa', self::agentesDemo())->get()->getResult() as $a) {
            $agentes[] = $a;
        }
        $faltas = $this->db->table('catalogo_infracciones')->select('fundamento_legal, descripcion, categoria_actor, monto_min_uma, monto_max_uma')
            ->orderBy('id', 'ASC')->limit(40)->get()->getResult();
        if ($agentes === [] || $faltas === []) {
            echo "Sin agentes o catálogo de infracciones: se omiten las boletas demo (corre antes DatabaseSeeder).\n";
            return 0;
        }

        $lugares = [
            ['Av. Hidalgo esq. Morelos, Col. Centro', 20.1419, -101.1764],
            ['Calle Allende frente al Mercado Municipal', 20.1428, -101.1751],
            ['Blvd. Lázaro Cárdenas a la altura de la Central', 20.1385, -101.1802],
            ['Calle Juárez esq. Guerrero', 20.1437, -101.1779],
            ['Carretera Uriangato – Moroleón km 1.5', 20.1503, -101.1838],
            ['Calle 16 de Septiembre frente a la escuela primaria', 20.1432, -101.1788],
        ];
        $vehiculos = [
            ['Nissan', 'Versa 2021', 'Plata', 'particular'], ['Chevrolet', 'Aveo 2018', 'Rojo', 'particular'],
            ['Volkswagen', 'Jetta 2016', 'Blanco', 'particular'], ['Italika', 'FT150 2022', 'Negro', 'motocicleta'],
            ['Ford', 'Ranger 2019', 'Gris', 'carga'], ['Toyota', 'Hiace 2017', 'Blanco', 'transporte_publico'],
            ['Kia', 'Rio 2023', 'Azul', 'particular'], ['Honda', 'CR-V 2015', 'Vino', 'particular'],
        ];
        $infractores = ['Martín Aguilar Rosas', 'Gabriela Núñez Ortiz', 'Ricardo Salinas Mora', 'Elena Paredes Luna', 'Jorge Ibarra Cano', 'Verónica Campos Ríos', 'Héctor Delgado Ruiz', 'Lucía Fuentes Vargas'];
        $garantias = [['licencia'], ['placa'], ['tarjeta_circulacion'], [], ['licencia', 'tarjeta_circulacion'], ['vehiculo']];

        $creadas = 0;
        for ($n = 1; $n <= self::TOTAL_BOLETAS; $n++) {
            $agente = $agentes[$n % count($agentes)];
            $folio  = self::folioBoleta($n, $agente->placa);
            if ($this->db->table('boletas_infracciones')->where('folio', $folio)->countAllResults() > 0) {
                continue;
            }

            $diasAtras = (int) floor(($n - 1) * 28 / self::TOTAL_BOLETAS);
            $fecha = $hoy->modify("-{$diasAtras} days")->setTime(8 + ($n * 5) % 12, ($n * 13) % 60);
            [$lugar, $lat, $lng] = $lugares[$n % count($lugares)];
            [$marca, $linea, $color, $tipo] = $vehiculos[$n % count($vehiculos)];
            $ausente = $n % 7 === 0;

            $seleccion = [$faltas[($n * 3) % count($faltas)]];
            if ($n % 3 === 0) {
                $seleccion[] = $faltas[($n * 5 + 1) % count($faltas)];
            }
            $faltasJson = array_map(static fn ($f, $i) => [
                'id' => 'falta-' . $n . '-' . $i, 'fundamentoLegal' => $f->fundamento_legal, 'descripcion' => $f->descripcion,
                'categoria' => strtoupper(str_replace('_', ' ', (string) $f->categoria_actor)),
                'montoMinUma' => (float) $f->monto_min_uma, 'montoMaxUma' => (float) $f->monto_max_uma,
            ], $seleccion, array_keys($seleccion));
            $principal = $faltasJson[0];
            $garantia  = $garantias[$n % count($garantias)];

            $estado = $diasAtras > 14 ? ($n % 2 === 0 ? 'pagada' : 'procesada') : ($diasAtras > 5 ? 'procesada' : 'recibida');
            $sincronizada = $fecha->modify('+' . (1 + $n % 3) . ' hours');

            $this->db->table('boletas_infracciones')->insert([
                'folio' => $folio, 'agente_placa' => $agente->placa, 'agente_nombre' => $agente->nombre_completo,
                'fecha_infraccion' => $fecha->format('Y-m-d'), 'hora_infraccion' => $fecha->format('H:i:s'),
                'lugar' => $lugar . ', Uriangato, Gto.', 'latitud' => $lat, 'longitud' => $lng,
                'conductor_ausente' => $ausente,
                'infractor_nombre' => $ausente ? 'Conductor Ausente' : $infractores[$n % count($infractores)],
                'infractor_domicilio' => $ausente ? 'No disponible' : 'Uriangato, Gto.',
                'infractor_licencia' => $ausente ? null : 'GTO-LIC-' . (40000 + $n * 731),
                'vehiculo_placas' => sprintf('GT%s-%03d-%s', chr(65 + $n % 5), 100 + $n * 17 % 900, chr(65 + $n % 26)),
                'sin_placas' => false,
                'vehiculo_marca' => $marca, 'vehiculo_linea' => $linea, 'vehiculo_color' => $color, 'vehiculo_tipo' => $tipo,
                'falta_fundamento_legal' => $principal['fundamentoLegal'], 'falta_descripcion' => $principal['descripcion'],
                'falta_categoria' => $principal['categoria'],
                'falta_monto_min_uma' => array_sum(array_column($faltasJson, 'montoMinUma')),
                'falta_monto_max_uma' => array_sum(array_column($faltasJson, 'montoMaxUma')),
                'faltas_json' => json_encode($faltasJson, JSON_UNESCAPED_UNICODE),
                'hechos' => implode('. ', array_map(static fn ($f) => 'Infracción ' . $f['fundamentoLegal'] . ': ' . $f['descripcion'], $faltasJson)),
                'garantias_retenidas' => json_encode($garantia),
                'inventario_grua' => in_array('vehiculo', $garantia, true) ? 'Grúas Uriangato – Inventario #' . (100 + $n) : null,
                'estado' => $estado,
                'sincronizado_en' => $sincronizada->format('Y-m-d H:i:s'),
                'creado_en_dispositivo' => $fecha->format('Y-m-d H:i:s'),
                'created_at' => $sincronizada->format('Y-m-d H:i:s'),
                'updated_at' => $sincronizada->format('Y-m-d H:i:s'),
            ]);
            $creadas++;
        }
        return $creadas;
    }

    // ─── Utilidades ───────────────────────────────────────────────────────────

    private function usuarioConRol(array $roles): ?int
    {
        foreach ($roles as $rol) {
            $fila = $this->db->table('user_roles ur')->select('ur.user_id')
                ->join('roles r', 'r.id = ur.role_id')->where('r.nombre', $rol)
                ->orderBy('ur.user_id', 'ASC')->get()->getRow();
            if ($fila !== null) {
                return (int) $fila->user_id;
            }
        }
        return null;
    }

    private function slug(string $texto): string
    {
        $texto = strtr(mb_strtolower($texto, 'UTF-8'), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);
        return preg_replace('/[^a-z0-9]+/', '', $texto) ?? '';
    }
}
