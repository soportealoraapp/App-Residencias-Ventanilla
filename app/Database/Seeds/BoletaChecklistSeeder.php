<?php declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

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

        // 2. Definir las 67 casillas de la boleta física
        $casillas = [
            // --- DOCUMENTACIÓN ---
            ['categoria' => 'DOCUMENTACIÓN', 'etiqueta' => 'Licencia vencida', 'fundamento' => 'Art. 87 Frac. III'],
            ['categoria' => 'DOCUMENTACIÓN', 'etiqueta' => 'Falta de licencia', 'fundamento' => 'Art. 87 Frac. III'],
            ['categoria' => 'DOCUMENTACIÓN', 'etiqueta' => 'Falta de tarjeta de circulación', 'fundamento' => 'Art. 87 Frac. IV'],
            ['categoria' => 'DOCUMENTACIÓN', 'etiqueta' => 'Permiso para el conductor (TTE. Público)', 'fundamento' => 'Art. 382 Frac. VIII'],
            ['categoria' => 'DOCUMENTACIÓN', 'etiqueta' => 'Falta de placa(s)', 'fundamento' => 'Art. 87 Frac. I a)'],
            ['categoria' => 'DOCUMENTACIÓN', 'etiqueta' => 'Ambos', 'fundamento' => null], // Sin mapeo claro
            ['categoria' => 'DOCUMENTACIÓN', 'etiqueta' => 'Falta de medallón', 'fundamento' => null], // Sin mapeo claro

            // --- PLACAS / EQUIPAMIENTO ---
            ['categoria' => 'PLACAS', 'etiqueta' => 'Agrietado', 'fundamento' => null], // Sin mapeo claro
            ['categoria' => 'PLACAS', 'etiqueta' => 'Falta de una', 'fundamento' => 'Art. 87 Frac. I a)'],
            ['categoria' => 'PLACAS', 'etiqueta' => 'Falta de parabrisas', 'fundamento' => null], // Sin mapeo claro
            ['categoria' => 'PLACAS', 'etiqueta' => 'Faro izquierdo', 'fundamento' => 'Art. 39 Frac. VII'],
            ['categoria' => 'PLACAS', 'etiqueta' => 'Faro derecho', 'fundamento' => 'Art. 39 Frac. VII'],
            ['categoria' => 'PLACAS', 'etiqueta' => 'Partes integrales del vehículo', 'fundamento' => 'Art. 89 Frac. II'],
            ['categoria' => 'PLACAS', 'etiqueta' => 'Falta de cinturón de seguridad [?]', 'fundamento' => 'Art. 39 Frac. V'],

            // --- SEÑALAMIENTOS ---
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'En motocicleta o motoneta', 'fundamento' => 'Art. 92 Frac. II'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'Por el lado derecho / por el lado izquierdo', 'fundamento' => 'Art. 39 Frac. XX'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'Semáforo en luz roja', 'fundamento' => 'Art. 39 Frac. IX'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'No obedecer señal de alto', 'fundamento' => 'Art. 39 Frac. X'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'Señales del oficial de TTO.', 'fundamento' => 'Art. 39 Frac. II'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'No obedecer señalamientos direccionales', 'fundamento' => 'Art. 39 Frac. I'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'Circular por lugar prohibido', 'fundamento' => 'Art. 89 Frac. V'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'Crucero', 'fundamento' => 'Art. 40 Frac. III'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'Zona prohibida', 'fundamento' => 'Art. 89 Frac. VII'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'No usar cinturón de seguridad', 'fundamento' => 'Art. 39 Frac. V'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'No conceder el paso a peatones', 'fundamento' => 'Art. 39 Frac. XI'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'No dar preferencia a vehículos que circulan en glorieta [?]', 'fundamento' => 'Art. 40 Frac. VI'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'Preferencia', 'fundamento' => 'Art. 40 Frac. I'],
            ['categoria' => 'SEÑALAMIENTOS', 'etiqueta' => 'Rebasar', 'fundamento' => 'Art. 89 Frac. XI'],

            // --- ESTACIONAMIENTOS ---
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Doble fila', 'fundamento' => 'Art. 89 Frac. XVIII'],
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Sobre aceras', 'fundamento' => 'Art. 89 Frac. VII'],
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Frente a la guarnición [?]', 'fundamento' => null], // Sin mapeo claro
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Lugar donde esté pintado de amarillo', 'fundamento' => 'Art. 39 Frac. I'],
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Superior de acotamiento', 'fundamento' => 'Art. 89 Frac. VIII'],
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Salida de vehículos de emergencia', 'fundamento' => 'Art. 39 Frac. XXII'],
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'En zonas de autobuses / parada de autobuses', 'fundamento' => 'Art. 89 Frac. XIX'],
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'En vía pública', 'fundamento' => 'Art. 389 Frac. XXV'],
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'En accesos para discapacitados', 'fundamento' => 'Art. 93 Frac. V'],
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Fuera del límite', 'fundamento' => null], // Sin mapeo claro
            ['categoria' => 'ESTACIONAMIENTOS', 'etiqueta' => 'Ascenso y/o descenso de pasaje por más de 5 [días naturales [?]] no permitido', 'fundamento' => 'Art. 389 Frac. XV'],

            // --- ACCIDENTES ---
            ['categoria' => 'ACCIDENTES', 'etiqueta' => 'Muerto', 'fundamento' => null], // Materia Penal
            ['categoria' => 'ACCIDENTES', 'etiqueta' => 'Víctimas', 'fundamento' => null], // Materia Penal/Civil
            ['categoria' => 'ACCIDENTES', 'etiqueta' => 'Daños materiales', 'fundamento' => null], // Materia Civil/Pericial
            ['categoria' => 'ACCIDENTES', 'etiqueta' => 'Provocando o causando accidente', 'fundamento' => 'Art. 87 Frac. IX'],
            ['categoria' => 'ACCIDENTES', 'etiqueta' => 'Abandono de vehículo ocasionando accidente/daño', 'fundamento' => 'Art. 87 Frac. XVIII'],
            ['categoria' => 'ACCIDENTES', 'etiqueta' => 'Vehículos por el acotamiento', 'fundamento' => 'Art. 89 Frac. VIII'],

            // --- FALTAS A LA AUTORIDAD ---
            ['categoria' => 'FALTAS A LA AUTORIDAD', 'etiqueta' => 'Agresiones físicas o verbales a los usuarios', 'fundamento' => 'Art. 389 Frac. I'],
            ['categoria' => 'FALTAS A LA AUTORIDAD', 'etiqueta' => 'Agresiones físicas y verbales a oficiales de TTO.', 'fundamento' => 'Art. 390 Frac. XIV'],

            // --- OTROS ---
            ['categoria' => 'OTROS', 'etiqueta' => 'Exceso de velocidad', 'fundamento' => 'Art. 39 Frac. III'],
            ['categoria' => 'OTROS', 'etiqueta' => 'Sentido contrario', 'fundamento' => 'Art. 39 Frac. IV'],
            ['categoria' => 'OTROS', 'etiqueta' => 'Circular en sentido contrario a la vía pública', 'fundamento' => 'Art. 39 Frac. IV'],
            ['categoria' => 'OTROS', 'etiqueta' => 'Obstruir la circulación', 'fundamento' => 'Art. 389 Frac. XVIII'],
            ['categoria' => 'OTROS', 'etiqueta' => 'Efectuar vuelta prohibida', 'fundamento' => 'Art. 39 Frac. XXIV'],
            ['categoria' => 'OTROS', 'etiqueta' => 'Remolcar vehículos pesados', 'fundamento' => null], // Sin artículo en catálogo
            ['categoria' => 'OTROS', 'etiqueta' => 'Cargar/descargar en horas no permitidas', 'fundamento' => null], // Gestionado por trámite UR-07
            ['categoria' => 'OTROS', 'etiqueta' => 'Exceso o dimensiones en la carga', 'fundamento' => 'Art. 93 Frac. II'],

            // --- MANEJO ---
            ['categoria' => 'MANEJO', 'etiqueta' => 'No utilizar casco protector', 'fundamento' => 'Art. 92 Frac. V'],
            ['categoria' => 'MANEJO', 'etiqueta' => 'Manejo con exceso de pasaje (particular)', 'fundamento' => 'Art. 39 Frac. XXVI'],
            ['categoria' => 'MANEJO', 'etiqueta' => 'Falta de permiso para transporte público', 'fundamento' => 'Art. 382 Frac. XXII'],
            ['categoria' => 'MANEJO', 'etiqueta' => 'Falta de combustible con pasaje a bordo', 'fundamento' => 'Art. 390 Frac. VII'],
            ['categoria' => 'MANEJO', 'etiqueta' => 'No abastecer combustible con TTE. público', 'fundamento' => 'Art. 390 Frac. VII'],
            ['categoria' => 'MANEJO', 'etiqueta' => 'Manejar en estado visible y notorio estado de ebriedad', 'fundamento' => 'Art. 89 Frac. I'],
            ['categoria' => 'MANEJO', 'etiqueta' => 'Estado físico del conductor', 'fundamento' => 'Art. 389 Frac. XII'],
            ['categoria' => 'MANEJO', 'etiqueta' => 'Aliento alcohólico', 'fundamento' => 'Art. 390 Frac. III'],
            ['categoria' => 'MANEJO', 'etiqueta' => 'Guiar drogado', 'fundamento' => 'Art. 390 Frac. IV'],

            // --- MEDIO AMBIENTE ---
            ['categoria' => 'MEDIO AMBIENTE', 'etiqueta' => 'Utilizar vehículo con exceso de volumen', 'fundamento' => 'Art. 93 Frac. X'],
            ['categoria' => 'MEDIO AMBIENTE', 'etiqueta' => 'Emisión excesiva de humo en vehículo', 'fundamento' => 'Art. 87 Frac. XXIV'],
            ['categoria' => 'MEDIO AMBIENTE', 'etiqueta' => 'Hacer mal uso del claxon', 'fundamento' => 'Art. 87 Frac. VIII'],
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
