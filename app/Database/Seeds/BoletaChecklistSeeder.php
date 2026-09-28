<?php declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Casillas de la boleta física de infracción, transcritas tal como aparecen impresas
 * en el documento real, agrupadas en sus 15 categorías originales (sin reagrupar).
 *
 * Notas de la transcripción:
 * - Algunas etiquetas se repiten en categorías distintas (p. ej. "Falta de licencia" y
 *   "Permiso para conducir" aparecen tanto en DOCUMENTACIÓN como en MANEJO). Así está
 *   impresa la boleta física: son registros separados (misma etiqueta, categoría distinta)
 *   y está bien que ambas mapeen al mismo fundamento_legal.
 * - El ítem compuesto "Luces falta de: faro izquierdo, derecho, ambos, posteriores
 *   direccionales izq., der." se separó en 5 casillas individuales porque corresponden
 *   a fracciones distintas del Reglamento (luces principales vs. direccionales).
 * - Mapeo 1 a 1 contra catalogo_infracciones: NO se fuerza ninguna coincidencia dudosa.
 *   Cuando no hay una fracción clara, fundamento = null (queda catalogo_infraccion_id = null).
 */
class BoletaChecklistSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Obtener mapeo de fundamento_legal => id en catalogo_infracciones
        $catalogoRows = $this->db->table('catalogo_infracciones')
            ->select('id, fundamento_legal')
            ->get()
            ->getResultArray();

        $fundamentoToId = [];
        foreach ($catalogoRows as $row) {
            $fundamentoToId[$row['fundamento_legal']] = (int) $row['id'];
        }

        // 2. Casillas de la boleta física (transcripción completa y corregida)
        $casillas = [
            // --- DOCUMENTACIÓN ---
            ['categoria' => 'DOCUMENTACIÓN', 'etiqueta' => 'Falta de tarjeta de circulación', 'fundamento' => 'Art. 87 Frac. IV'],
            ['categoria' => 'DOCUMENTACIÓN', 'etiqueta' => 'Falta de tarjeta de identificación del conductor (TTE Público)', 'fundamento' => 'Art. 382 Frac. VIII'],
            ['categoria' => 'DOCUMENTACIÓN', 'etiqueta' => 'Falta de licencia', 'fundamento' => 'Art. 87 Frac. III'],
            ['categoria' => 'DOCUMENTACIÓN', 'etiqueta' => 'Permiso para conducir', 'fundamento' => 'Art. 87 Frac. III'],
            ['categoria' => 'DOCUMENTACIÓN', 'etiqueta' => 'Licencia vencida', 'fundamento' => 'Art. 87 Frac. III'],

            // --- PLACAS ---
            ['categoria' => 'PLACAS', 'etiqueta' => 'Falta de placa(s) en vehículo(s)', 'fundamento' => 'Art. 87 Frac. I a)'],
            ['categoria' => 'PLACAS', 'etiqueta' => 'Usar placas vencidas', 'fundamento' => null], // Sin fracción clara para "vencidas" (solo hay ocultas/alteradas/soldadas)
            ['categoria' => 'PLACAS', 'etiqueta' => 'Falta de una', 'fundamento' => 'Art. 87 Frac. I a)'],
            ['categoria' => 'PLACAS', 'etiqueta' => 'Falta de ambas', 'fundamento' => 'Art. 87 Frac. I a)'], // Misma fracción que "Falta de una" (transitar sin placas)

            // --- PARTES INTEGRALES DEL VEHÍCULO ---
            ['categoria' => 'PARTES INTEGRALES DEL VEHÍCULO', 'etiqueta' => 'No usar cinturón de seguridad', 'fundamento' => 'Art. 39 Frac. V'],
            ['categoria' => 'PARTES INTEGRALES DEL VEHÍCULO', 'etiqueta' => 'Falta de cinturón de seguridad', 'fundamento' => 'Art. 39 Frac. V'],
            ['categoria' => 'PARTES INTEGRALES DEL VEHÍCULO', 'etiqueta' => 'Falta de parabrisas', 'fundamento' => null], // Sin fracción en catálogo
            ['categoria' => 'PARTES INTEGRALES DEL VEHÍCULO', 'etiqueta' => 'Agrietado (Parabrisas)', 'fundamento' => null], // Sin fracción en catálogo
            ['categoria' => 'PARTES INTEGRALES DEL VEHÍCULO', 'etiqueta' => 'Falta de medallón', 'fundamento' => null], // Sin fracción en catálogo
            ['categoria' => 'PARTES INTEGRALES DEL VEHÍCULO', 'etiqueta' => 'Agrietado (Medallón)', 'fundamento' => null], // Sin fracción en catálogo
            ['categoria' => 'PARTES INTEGRALES DEL VEHÍCULO', 'etiqueta' => 'Falta de faro izquierdo', 'fundamento' => 'Art. 39 Frac. VII'],
            ['categoria' => 'PARTES INTEGRALES DEL VEHÍCULO', 'etiqueta' => 'Falta de faro derecho', 'fundamento' => 'Art. 39 Frac. VII'],
            ['categoria' => 'PARTES INTEGRALES DEL VEHÍCULO', 'etiqueta' => 'Falta de ambos faros', 'fundamento' => 'Art. 39 Frac. VII'],
            ['categoria' => 'PARTES INTEGRALES DEL VEHÍCULO', 'etiqueta' => 'Falta de direccional izquierda', 'fundamento' => 'Art. 39 Frac. XVII'],
            ['categoria' => 'PARTES INTEGRALES DEL VEHÍCULO', 'etiqueta' => 'Falta de direccional derecha', 'fundamento' => 'Art. 39 Frac. XVII'],
            ['categoria' => 'PARTES INTEGRALES DEL VEHÍCULO', 'etiqueta' => 'En motocicleta', 'fundamento' => 'Art. 93 Frac. VI'],
            ['categoria' => 'PARTES INTEGRALES DEL VEHÍCULO', 'etiqueta' => 'En motoneta', 'fundamento' => 'Art. 93 Frac. VI'],

            // --- SEÑALAMIENTOS ---
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'Circular por lugar prohibido', 'fundamento' => 'Art. 89 Frac. VII'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'No obedecer señalamientos de alto', 'fundamento' => 'Art. 39 Frac. I'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'No obedecer señal de prohibido el paso a vehículos pesados', 'fundamento' => null], // Sin fracción clara en catálogo
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'Señales del oficial de TTO.', 'fundamento' => 'Art. 39 Frac. X'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'Semáforo en luz roja', 'fundamento' => 'Art. 39 Frac. IX'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'Dañar, destruir u obstruir la visibilidad de señales de tránsito', 'fundamento' => null], // Sin fracción clara en catálogo
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'Exceso de velocidad', 'fundamento' => 'Art. 39 Frac. III'],

            // --- ESTACIONAMIENTOS ---
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Lugar prohibido', 'fundamento' => null], // Sin fracción genérica de "lugar prohibido"
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Doble fila', 'fundamento' => 'Art. 89 Frac. XVIII'],
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Zona donde la guarnición esté pintada de amarillo', 'fundamento' => null], // Sin fracción en catálogo
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Sentido contrario', 'fundamento' => null], // Distinto de "circular en sentido contrario"; sin fracción propia de estacionamiento
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Fuera del límite', 'fundamento' => null], // Sin fracción clara en catálogo
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Frente a cochera', 'fundamento' => null], // Sin fracción en catálogo
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Salida de veh. emergencias', 'fundamento' => null], // Sin fracción en catálogo
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Sobre aceras', 'fundamento' => 'Art. 89 Frac. VII'],
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Sup. de acotamiento', 'fundamento' => 'Art. 89 Frac. VIII'],
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'En parada de autobuses', 'fundamento' => 'Art. 89 Frac. XIX'],
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'En accesos para discapacitados', 'fundamento' => 'Art. 93 Frac. V'],
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'En zonas de carga y descarga', 'fundamento' => null], // Sin fracción en catálogo
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'En vía pública por más de 5 días naturales', 'fundamento' => null], // "Estacionarse en vía pública" del catálogo es para concesionarios; no es una coincidencia clara
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Ascenso y descenso de pasaje en lugar no permitido', 'fundamento' => 'Art. 389 Frac. XIV'],

            // --- MANEJO ---
            ['categoria' => 'MANEJO', 'etiqueta' => 'No utilizar casco protector', 'fundamento' => 'Art. 92 Frac. V'],
            ['categoria' => 'MANEJO', 'etiqueta' => 'Placas ocultas', 'fundamento' => 'Art. 87 Frac. I b)'],
            ['categoria' => 'MANEJO', 'etiqueta' => 'Abastecer combustible con pasaje a bordo en trans. público', 'fundamento' => 'Art. 390 Frac. VII'],
            ['categoria' => 'MANEJO', 'etiqueta' => 'Falta de licencia', 'fundamento' => 'Art. 87 Frac. III'],
            ['categoria' => 'MANEJO', 'etiqueta' => 'Permiso para conducir', 'fundamento' => 'Art. 87 Frac. III'],
            ['categoria' => 'MANEJO', 'etiqueta' => 'Falta de permiso carga y descarga', 'fundamento' => null], // Sin fracción en catálogo

            // --- ESTADO FÍSICO DEL CONDUCTOR ---
            ['categoria' => 'ESTADO FÍSICO DEL CONDUCTOR', 'etiqueta' => 'Aliento alcohólico', 'fundamento' => 'Art. 390 Frac. III'],
            ['categoria' => 'ESTADO FÍSICO DEL CONDUCTOR', 'etiqueta' => 'Manejar en visible y notorio estado de ebriedad', 'fundamento' => 'Art. 89 Frac. I'],
            ['categoria' => 'ESTADO FÍSICO DEL CONDUCTOR', 'etiqueta' => 'Guiar drogado', 'fundamento' => 'Art. 390 Frac. IV'],

            // --- PREFERENCIA ---
            ['categoria' => 'PREFERENCIA', 'etiqueta' => 'No conceder el paso a peatones', 'fundamento' => 'Art. 40 Frac. I'],
            ['categoria' => 'PREFERENCIA', 'etiqueta' => 'No darle a vehículos que circulen en glorieta', 'fundamento' => 'Art. 40 Frac. VI'],
            ['categoria' => 'PREFERENCIA', 'etiqueta' => 'No darle a vehículos de emergencia', 'fundamento' => 'Art. 39 Frac. XXII'],

            // --- REBASAR ---
            ['categoria' => 'REBASAR', 'etiqueta' => 'En zona prohibida', 'fundamento' => null], // Sin fracción específica de zona prohibida para rebasar
            ['categoria' => 'REBASAR', 'etiqueta' => 'Por el lado derecho', 'fundamento' => 'Art. 39 Frac. XX'],
            ['categoria' => 'REBASAR', 'etiqueta' => 'Estando a punto de causar accidentes', 'fundamento' => null], // Sin fracción clara en catálogo
            ['categoria' => 'REBASAR', 'etiqueta' => 'Vehículos por el acotamiento', 'fundamento' => 'Art. 89 Frac. VIII'],
            ['categoria' => 'REBASAR', 'etiqueta' => 'Crucero', 'fundamento' => null], // "No detenerse en cruceros" (Art. 40 Frac. III) no es lo mismo que "rebasar en crucero"

            // --- ACCIDENTES ---
            ['categoria' => 'ACCIDENTES', 'etiqueta' => 'Por causar daños a bienes del municipio', 'fundamento' => null], // Sin fracción en catálogo
            ['categoria' => 'ACCIDENTES', 'etiqueta' => 'Provocar accidente causando herido', 'fundamento' => null], // Sin fracción en catálogo
            ['categoria' => 'ACCIDENTES', 'etiqueta' => 'Muerto', 'fundamento' => null], // Materia penal, sin fracción en catálogo
            ['categoria' => 'ACCIDENTES', 'etiqueta' => 'Abandono de vehículo ocasionando accidente', 'fundamento' => 'Art. 87 Frac. XVIII'],
            ['categoria' => 'ACCIDENTES', 'etiqueta' => 'Provocando o causando daños materiales', 'fundamento' => null], // Sin fracción en catálogo
            ['categoria' => 'ACCIDENTES', 'etiqueta' => 'Abandono de vehículo', 'fundamento' => 'Art. 87 Frac. XVIII'],
            ['categoria' => 'ACCIDENTES', 'etiqueta' => 'Víctimas', 'fundamento' => null], // Materia civil/penal, sin fracción en catálogo

            // --- FALTAS A LA AUTORIDAD ---
            ['categoria' => 'FALTAS A LA AUTORIDAD', 'etiqueta' => 'Agresiones físicas o verbales a los usuarios', 'fundamento' => null], // Ambiguo entre Art. 389 Frac. I (verbal/moral) y Art. 390 Frac. XVI (física); no hay una sola fracción que cubra ambas
            ['categoria' => 'FALTAS A LA AUTORIDAD', 'etiqueta' => 'Agresiones físicas o verbales a oficiales de TTO.', 'fundamento' => 'Art. 390 Frac. XIV'],

            // --- CARGA ---
            ['categoria' => 'CARGA', 'etiqueta' => 'Cargar o descargar en horas no permitidas', 'fundamento' => null], // Sin fracción en catálogo
            ['categoria' => 'CARGA', 'etiqueta' => 'Exceso de dimensiones en la carga', 'fundamento' => null], // Sin fracción en catálogo (Art. 93 Frac. II es de motociclistas, no aplica)

            // --- REMOLCAR ---
            ['categoria' => 'REMOLCAR', 'etiqueta' => 'Remolcar vehículos sin aditamentos requeridos', 'fundamento' => null], // Sin fracción en catálogo

            // --- CIRCULACIÓN ---
            ['categoria' => 'CIRCULACIÓN', 'etiqueta' => 'Circular en sentido contrario', 'fundamento' => 'Art. 39 Frac. IV'],
            ['categoria' => 'CIRCULACIÓN', 'etiqueta' => 'Vehículos pesados en zona restringida', 'fundamento' => null], // Sin fracción en catálogo
            ['categoria' => 'CIRCULACIÓN', 'etiqueta' => 'Efectuar vuelta en U', 'fundamento' => null], // Sin fracción en catálogo
            ['categoria' => 'CIRCULACIÓN', 'etiqueta' => 'Obstruir la circulación a vehículos en vía pública', 'fundamento' => 'Art. 389 Frac. XVIII'],

            // --- MEDIO AMBIENTE ---
            ['categoria' => 'MEDIO AMBIENTE', 'etiqueta' => 'Utilizar vehículo con exceso de volumen', 'fundamento' => 'Art. 93 Frac. X'],
            ['categoria' => 'MEDIO AMBIENTE', 'etiqueta' => 'Emisión excesiva de humo en vehículo', 'fundamento' => null], // Distinto de "falta de verificación"; sin fracción propia de emisión de humo
            ['categoria' => 'MEDIO AMBIENTE', 'etiqueta' => 'Falta de verificación vehicular', 'fundamento' => 'Art. 87 Frac. XXIV'],
            ['categoria' => 'MEDIO AMBIENTE', 'etiqueta' => 'Hacer mal uso del claxon', 'fundamento' => null], // Art. 87 Frac. VIII es sobre paso peatonal/claxon en esquinas, no "mal uso" genérico
        ];

        // 3. Limpiar tabla boleta_checklist_item para re-siembra limpia
        $this->db->table('boleta_checklist_item')->truncate();

        // 4. Insertar cada casilla
        $insertData = [];
        foreach ($casillas as $c) {
            $infraccionId = null;
            if ($c['fundamento'] !== null && isset($fundamentoToId[$c['fundamento']])) {
                $infraccionId = $fundamentoToId[$c['fundamento']];
            }

            $insertData[] = [
                'categoria_boleta'       => $c['categoria'],
                'etiqueta_casilla'       => $c['etiqueta'],
                'catalogo_infraccion_id' => $infraccionId,
                'created_at'             => date('Y-m-d H:i:s'),
            ];
        }

        $this->db->table('boleta_checklist_item')->insertBatch($insertData);
    }
}
