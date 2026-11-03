<?php declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Llena el catálogo administrable de la app de tránsito (catalogo_infracciones_app,
 * el de Panel → App tránsito → Catálogo) con las 201 faltas oficiales del reglamento
 * (catalogo_infracciones). Desde f870a1d la app toma sus faltas de esta tabla.
 *
 * Reglas, para no pisar lo que capture el administrador:
 * - Una falta oficial que no está en el catálogo de la app se agrega.
 * - Las 14 faltas de ejemplo que sembró supabase/migrations/0005 se reemplazan por
 *   el texto y montos oficiales, o se desactivan si su artículo no está en el
 *   reglamento, pero solo mientras sigan exactamente como se sembraron.
 * - Cualquier otra fila (editada o creada desde el panel) no se toca.
 *
 * Es idempotente. Requiere antes CatalogoInfraccionesSeeder. Lo usa también la
 * migración 2026-11-03-000034 y equivale a supabase/migrations/0019.
 */
class CatalogoInfraccionesAppSeeder extends Seeder
{
    /** Etiqueta de categoría en la app según el actor del reglamento. */
    private const CATEGORIAS = [
        'conductor_general'      => 'CONDUCTORES',
        'motociclista'           => 'MOTOCICLISTAS',
        'ciclista'               => 'CICLISTAS',
        'concesionario_operador' => 'CONCESIONARIOS Y OPERADORES',
    ];

    /** Las 14 faltas de ejemplo de supabase/migrations/0005, tal como se sembraron. */
    private const EJEMPLOS_0005 = [
        ['Art. 39 Frac. IX', 'No obedecer la señal de alto cuando la luz del semáforo esté en rojo', 'MANEJO Y VIALIDAD', 15, 25],
        ['Art. 82', 'Conducir utilizando teléfonos celulares o dispositivos de comunicación móvil en movimiento', 'MANEJO Y VIALIDAD', 10, 20],
        ['Art. 39 Frac. III', 'No respetar los límites de velocidad establecidos en la zona urbana o escolar', 'VELOCIDAD', 30, 50],
        ['Art. 95 / Art. 87 Frac. II', 'Estacionarse en lugar prohibido, doble fila o rampas reservadas para personas con discapacidad', 'ESTACIONAMIENTO', 10, 20],
        ['Art. 102', 'No portar casco protector certificado el conductor y/o acompañante de motocicleta', 'MOTOCICLETAS', 5, 10],
        ['Art. 87 Frac. I a)', 'Circular sin placas de circulación, con placas alteradas, ocultas o no visibles', 'PLACAS Y REGISTRO', 10, 15],
        ['Art. 39 Frac. V', 'No utilizar el cinturón de seguridad el conductor y/o acompañantes', 'SEGURIDAD PASIVA', 5, 10],
        ['Art. 39 Frac. IV', 'Circular en sentido opuesto al que indica la vialidad o señalamiento oficial', 'MANEJO Y VIALIDAD', 3, 5],
        ['Art. 87 Frac. III', 'Falta de licencia de conducir para el tipo de vehículo o presentar licencia vencida', 'DOCUMENTACIÓN', 5, 10],
        ['Art. 87 Frac. IV', 'Falta de tarjeta de circulación vigente del vehículo', 'DOCUMENTACIÓN', 5, 10],
        ['Art. 39 Frac. XI', 'No respetar el derecho de preferencia de paso a peatones y/o ciclistas en cruces marcados', 'PROTECCIÓN PEATONAL', 5, 10],
        ['Art. 115', 'Conducir vehículos bajo el influjo de bebidas alcohólicas, drogas o sustancias tóxicas', 'SEGURIDAD PÚBLICA', 50, 100],
        ['Art. 39 Frac. VII', 'Circular sin faros delanteros o luces posteriores en horario nocturno o condiciones adversas', 'EQUIPAMIENTO', 5, 10],
        ['Art. 39 Frac. XXV', 'Circular con puertas abiertas o permitir que pasajeros viajen en estribos del vehículo', 'TRANSPORTE Y PASAJE', 15, 30],
    ];

    public function run(): void
    {
        $oficiales = $this->db->table('catalogo_infracciones')->orderBy('id', 'ASC')->get()->getResult();
        if ($oficiales === []) {
            return;
        }

        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query("SELECT setval(pg_get_serial_sequence('catalogo_infracciones_app', 'id'), COALESCE((SELECT MAX(id) FROM catalogo_infracciones_app), 0) + 1, false)");
        }

        $ahora = date('Y-m-d H:i:s');
        $enReglamento = [];

        foreach ($oficiales as $oficial) {
            $enReglamento[$oficial->fundamento_legal] = true;
            $datos = [
                'descripcion'   => $oficial->descripcion,
                'categoria'     => self::CATEGORIAS[$oficial->categoria_actor] ?? 'GENERAL',
                'monto_min_uma' => $oficial->monto_min_uma,
                'monto_max_uma' => $oficial->monto_max_uma,
                'updated_at'    => $ahora,
            ];

            $actual = $this->db->table('catalogo_infracciones_app')
                ->where('fundamento_legal', $oficial->fundamento_legal)
                ->get()->getRow();

            if ($actual === null) {
                $this->db->table('catalogo_infracciones_app')->insert($datos + [
                    'fundamento_legal' => $oficial->fundamento_legal,
                    'activo'           => 1,
                    'orden'            => 100 + (int) $oficial->id,
                    'created_at'       => $ahora,
                ]);
            } elseif ($this->esEjemploSinEditar($actual)) {
                $this->db->table('catalogo_infracciones_app')->where('id', $actual->id)->update($datos + [
                    'orden' => 100 + (int) $oficial->id,
                ]);
            }
        }

        // Ejemplos sin editar cuyo artículo no existe en el reglamento: se apagan.
        foreach (self::EJEMPLOS_0005 as [$fundamento]) {
            if (isset($enReglamento[$fundamento])) {
                continue;
            }
            $actual = $this->db->table('catalogo_infracciones_app')->where('fundamento_legal', $fundamento)->get()->getRow();
            if ($actual !== null && (int) $actual->activo === 1 && $this->esEjemploSinEditar($actual)) {
                $this->db->table('catalogo_infracciones_app')->where('id', $actual->id)->update(['activo' => 0, 'updated_at' => $ahora]);
            }
        }
    }

    private function esEjemploSinEditar(object $fila): bool
    {
        foreach (self::EJEMPLOS_0005 as [$fundamento, $descripcion, $categoria, $minUma, $maxUma]) {
            if ($fila->fundamento_legal === $fundamento) {
                return $fila->descripcion === $descripcion
                    && $fila->categoria === $categoria
                    && abs((float) $fila->monto_min_uma - $minUma) < 0.001
                    && abs((float) $fila->monto_max_uma - $maxUma) < 0.001;
            }
        }

        return false;
    }
}
