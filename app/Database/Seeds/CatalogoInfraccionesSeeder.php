<?php declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Catálogo oficial de infracciones del Reglamento de Movilidad y Transporte
 * del Municipio de Uriangato, Gto. (201 registros, Arts. 39/40/62/63/64/87/89/92/93/365-398).
 *
 * IMPORTANTE:
 * - Los montos están en UMA (Unidad de Medida y Actualización), NUNCA en pesos fijos.
 * - El valor de la UMA vigente debe salir de la tabla de configuración del sistema (parametros_sistema),
 *   nunca hardcodeado (ver ResolverMontoInfraccionService).
 * - Seeder idempotente: no duplica registros si ya existen (verifica por fundamento_legal y clave).
 */
class CatalogoInfraccionesSeeder extends Seeder
{
    public function run(): void
    {
        $infracciones = [
            ['fundamento_legal' => 'Art. 39 Frac. I', 'descripcion' => 'No obedecer los señalamientos viales de Tránsito', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 15.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 39 Frac. II', 'descripcion' => 'No obedecer indicaciones del personal de la Dirección y/o promotores voluntarios', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 39 Frac. III', 'descripcion' => 'No respetar los límites de velocidad establecidos', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 30.0, 'monto_max_uma' => 50.0],
            ['fundamento_legal' => 'Art. 39 Frac. IV', 'descripcion' => 'Circular en sentido opuesto al que indica la vialidad', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 39 Frac. V', 'descripcion' => 'No utilizar el cinturón de seguridad el conductor y/o ocupantes', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 39 Frac. VI', 'descripcion' => 'No utilizar el sistema de retención infantil al llevar acompañantes menores', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 39 Frac. VII', 'descripcion' => 'Circular sin faros delanteros y/o luces posteriores', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 39 Frac. VIII', 'descripcion' => 'No orillarse al carril derecho ni utilizar intermitentes para ascenso/descenso', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 10.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 39 Frac. IX', 'descripcion' => 'No obedecer la señal de alto cuando la luz del semáforo esté en rojo', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 15.0, 'monto_max_uma' => 25.0],
            ['fundamento_legal' => 'Art. 39 Frac. X', 'descripcion' => 'No obedecer la señal de alto indicada por el Agente u Oficial', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 10.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 39 Frac. XI', 'descripcion' => 'No respetar el derecho de preferencia de paso a peatones y/o ciclistas', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 39 Frac. XII', 'descripcion' => 'Al ascender/descender, no cerciorarse de que no exista peligro', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 39 Frac. XIII', 'descripcion' => 'No ceder el paso al incorporarse a una vía rápida o primaria', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 39 Frac. XIV', 'descripcion' => 'Al circular en vía de menor jerarquía, no ceder el paso a vehículos de vía mayor', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 39 Frac. XV', 'descripcion' => 'No ceder el paso en intersección sin señalamientos a vehículo dentro de ella', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 39 Frac. XVI', 'descripcion' => 'No contar con luces indicadoras de frenos', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 39 Frac. XVII', 'descripcion' => 'No contar con luces direccionales o intermitentes', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 39 Frac. XVIII', 'descripcion' => 'No contar con cuartos delanteros y/o traseros', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 39 Frac. XIX', 'descripcion' => 'No contar con luces indicadoras de reversa', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 39 Frac. XX', 'descripcion' => 'Rebasar por la derecha y/o no respetar la distancia lateral mínima de 1 metro', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 39 Frac. XXI', 'descripcion' => 'No dar preferencia o facilidades a la circulación de transporte público', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 10.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 39 Frac. XXII', 'descripcion' => 'No dar preferencia a vehículos de emergencia', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 10.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 39 Frac. XXIII', 'descripcion' => 'Pasar la intersección ante el indicativo de luz ámbar del semáforo', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 39 Frac. XXIV', 'descripcion' => 'Dar vuelta a la derecha o izquierda sin flecha o luz verde del semáforo', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 39 Frac. XXV', 'descripcion' => 'Circular con puertas abiertas', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 15.0, 'monto_max_uma' => 30.0],
            ['fundamento_legal' => 'Art. 39 Frac. XXVI', 'descripcion' => 'Llevar a bordo más personas de las indicadas en la tarjeta de circulación', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 40 Frac. I', 'descripcion' => 'No respetar la preferencia de paso de los peatones', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 40 Frac. II', 'descripcion' => 'No respetar la preferencia de paso de vehículos no motorizados', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 40 Frac. III', 'descripcion' => 'No detenerse en cruceros antes de cruzar', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 40 Frac. IV a)', 'descripcion' => 'Semáforo en rojo: no detenerse en línea de alto o invadir cruce peatonal/espera ciclista', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 40 Frac. IV b)', 'descripcion' => 'Semáforo en verde con congestión: no detenerse y obstruir calle transversal', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 40 Frac. IV c)', 'descripcion' => 'Semáforo destellando en rojo: no detener la marcha en la línea de alto', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 40 Frac. IV d)', 'descripcion' => 'Semáforo destellando en verde: no disminuir la velocidad', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 40 Frac. IV e)', 'descripcion' => 'Semáforos apagados/sin señalamiento: no respetar regla del uno a uno', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 40 Frac. IV f)', 'descripcion' => 'Vuelta continua sin señalamiento: poner en riesgo al peatón', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 40 Frac. V', 'descripcion' => 'No detener la marcha cuando no sea posible cruzar la vía en su totalidad', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 4.0],
            ['fundamento_legal' => 'Art. 40 Frac. VI', 'descripcion' => 'No dar preferencia de paso a vehículos en glorieta o al salir de esta', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 40 Frac. VII', 'descripcion' => 'Intersecciones de misma jerarquía sin señalamiento: no respetar regla uno a uno', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 40 Frac. VIII', 'descripcion' => 'No dar preferencia a peatones/ciclistas en accesos de cocheras, comercios, etc.', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 40 Frac. IX', 'descripcion' => 'No conservar respecto al vehículo precedente la distancia de seguridad oportuna', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 87 Frac. I a)', 'descripcion' => 'Transitar sin ambas placas o sin una de las placas', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. I b)', 'descripcion' => 'Transitar con placas ocultas o en el interior del vehículo', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. I c)', 'descripcion' => 'Utilizar placas con alteraciones o cubiertas que dificulten la visibilidad', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. I d)', 'descripcion' => 'Fijar placas con soldadura, remaches o tornillos modificados', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. II', 'descripcion' => 'No portar permiso provisional de circulación en ausencia de placas', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. III', 'descripcion' => 'No portar licencia o permiso de conducir vigente correspondiente al vehículo', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. IV', 'descripcion' => 'No portar tarjeta de circulación original', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. V', 'descripcion' => 'No portar póliza vigente de seguro por responsabilidad civil', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. VI', 'descripcion' => 'No entregar licencia o tarjeta de circulación a requerimiento de la autoridad', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 87 Frac. VII', 'descripcion' => 'No dar preferencia a peatones, personas con discapacidad o transporte no motorizado', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 87 Frac. VIII', 'descripcion' => 'No respetar paso peatonal en esquinas / no detenerse o usar el claxon', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. IX', 'descripcion' => 'Realizar maniobras imprudentes al manejar', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 8.0],
            ['fundamento_legal' => 'Art. 87 Frac. X', 'descripcion' => 'No transitar por el carril derecho de las vías públicas', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. XI', 'descripcion' => 'Rebasar por la derecha / no dejar 1.50m de separación al rebasar ciclistas/motos', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 87 Frac. XII', 'descripcion' => 'Cambiar de carril o tomar carril extremo de giro de manera irresponsable', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. XIII', 'descripcion' => 'En cruce uno a uno, no detenerse para ceder el paso', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 10.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 87 Frac. XIV', 'descripcion' => 'No esperar la marcha del vehículo detenido para ceder paso a peatones', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. XV', 'descripcion' => 'No indicar mediante luces direccionales el giro o cambio de carril', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. XVI', 'descripcion' => 'Llevar menores en asientos traseros sin cinturón o sistema de retención', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 87 Frac. XVII', 'descripcion' => 'No colaborar en la limpieza o retiro de objetos de la vía pública', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. XVIII', 'descripcion' => 'Ante avería, no retirar el vehículo de la superficie de rodamiento', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 2.0, 'monto_max_uma' => 3.0],
            ['fundamento_legal' => 'Art. 87 Frac. XIX', 'descripcion' => 'Realizar prácticas de manejo en áreas con tráfico vehicular', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. XX', 'descripcion' => 'No dar prioridad a vehículos de emergencia con señales luminosas/audibles', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. XXI', 'descripcion' => 'Transportar animales en vehículos inadecuados o sin medidas de seguridad', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. XXII', 'descripcion' => 'Transportar mascotas sin tomar medidas para evitar distracción/accidente', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 2.0, 'monto_max_uma' => 3.0],
            ['fundamento_legal' => 'Art. 87 Frac. XXIII', 'descripcion' => 'No apagar el vehículo al cargar combustible y/o fumar en gasolineras', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. XXIV', 'descripcion' => 'Circular sin calcomanía/comprobante de verificación anticontaminante', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 87 Frac. XXV', 'descripcion' => 'No respetar disposiciones sanitarias de carácter obligatorio durante emergencias', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 89 Frac. I', 'descripcion' => 'Conducir bajo efectos de alcohol, enervantes o estupefacientes', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 10.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 89 Frac. II', 'descripcion' => 'Conducir un vehículo que no reúna los requisitos legales para circular', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 89 Frac. III', 'descripcion' => 'Conducir llevando entre los brazos personas, animales u objetos', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 89 Frac. IV', 'descripcion' => 'Utilizar equipos de comunicación móvil/portátil al conducir', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 10.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 89 Frac. V', 'descripcion' => 'Circular en áreas destinadas a peatones, personas con discapacidad o ciclistas', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 89 Frac. VI', 'descripcion' => 'Circular sobre las líneas delimitadoras de carriles o zonas de transición', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 2.0, 'monto_max_uma' => 3.0],
            ['fundamento_legal' => 'Art. 89 Frac. VII', 'descripcion' => 'Circular por plazas, aceras, banquetas, camellones, parques y jardines', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 89 Frac. VIII', 'descripcion' => 'Circular de forma continua sobre acotamiento o rebasar por dicho lugar', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 89 Frac. IX', 'descripcion' => 'Circular detrás de vehículos de emergencia (<50m de distancia)', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 89 Frac. X', 'descripcion' => 'Circular a velocidad excesivamente baja sin causa', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 2.0, 'monto_max_uma' => 3.0],
            ['fundamento_legal' => 'Art. 89 Frac. XI', 'descripcion' => 'Adelantar o rebasar a un vehículo que se encuentra realizando la misma maniobra', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 89 Frac. XII', 'descripcion' => 'Permitir que un pasajero tome el control del volante', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 89 Frac. XIII', 'descripcion' => 'Detener el vehículo invadiendo cruces peatonales o áreas ciclistas', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 89 Frac. XIV', 'descripcion' => 'Entorpecer la marcha de cortejos fúnebres, desfiles, competencias, caravanas', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 2.0, 'monto_max_uma' => 3.0],
            ['fundamento_legal' => 'Art. 89 Frac. XV', 'descripcion' => 'Acrobacias, arrancones o competencias en vía pública', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 10.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 89 Frac. XVI', 'descripcion' => 'Transportar personas en carrocería, toldos, cofres, estribos', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 89 Frac. XVII', 'descripcion' => 'Abrir las puertas cuando el vehículo se encuentre en movimiento', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 10.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 89 Frac. XVIII', 'descripcion' => 'Estacionar el vehículo en doble fila', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 89 Frac. XIX', 'descripcion' => 'Estacionar en paradas del transporte público o zonas de ascenso y descenso', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 89 Frac. XX', 'descripcion' => 'Ostentar en vehículo particular colores/números exclusivos de transporte público', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 10.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 89 Frac. XXI', 'descripcion' => 'Transportar combustible o sustancias peligrosas en envases abiertos o de cristal', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 10.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 89 Frac. XXII', 'descripcion' => 'Transportar materiales/sustancias inflamables o explosivas sin autorización', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 10.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 89 Frac. XXIII', 'descripcion' => 'Arrojar objetos o basura desde el interior del vehículo', 'categoria_actor' => 'conductor_general', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 92 Frac. I', 'descripcion' => 'No respetar a peatones, ciclistas o conductores de vehículos no motorizados', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 92 Frac. II', 'descripcion' => 'No respetar los señalamientos viales', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 92 Frac. III', 'descripcion' => 'Circular con más personas que las plazas designadas por el fabricante', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 92 Frac. IV', 'descripcion' => 'No circular por el carril de la extrema derecha', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 92 Frac. V', 'descripcion' => 'Conductor y/o tripulantes sin casco protector reglamentario', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 92 Frac. VI', 'descripcion' => 'No utilizar un solo carril o circular en forma paralela con otras motocicletas', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 8.0],
            ['fundamento_legal' => 'Art. 92 Frac. VII', 'descripcion' => 'Sujetarse a otros vehículos en circulación', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 92 Frac. VIII', 'descripcion' => 'Sin tarjeta de circulación, placa visible o colocada en sitio distinto al fabricante', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 92 Frac. IX', 'descripcion' => 'No portar licencia de conducir vigente modalidad motociclista', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 93 Frac. I', 'descripcion' => 'Transportar pasajero entre la persona que conduce y el manubrio', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 93 Frac. II', 'descripcion' => 'Transportar carga u objeto que impida ambas manos en manubrio o visibilidad', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 93 Frac. III', 'descripcion' => 'Sujetarse a otros vehículos en circulación', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 93 Frac. IV', 'descripcion' => 'Uso de celulares, audífonos o dispositivos de distracción al conducir', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 93 Frac. V', 'descripcion' => 'Estacionarse sobre zonas peatonales, banquetas o rampas de discapacidad', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 3.0],
            ['fundamento_legal' => 'Art. 93 Frac. VI', 'descripcion' => 'Circular sin luces principales/alto/direccionales, espejos o frenos/llantas en mal estado', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 93 Frac. VII', 'descripcion' => 'Circular con fugas de combustible o aceite', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 93 Frac. VIII', 'descripcion' => 'Circular sin escape/silenciador, o ruidoso/contaminante', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 3.0],
            ['fundamento_legal' => 'Art. 93 Frac. IX', 'descripcion' => 'Circular con luces frontales apagadas o de color distinto al de agencia', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 93 Frac. X', 'descripcion' => 'Circular con equipos de audio/bocinas que excedan de 75 dB a 2 metros', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 93 Frac. XI', 'descripcion' => 'Circular con luces traseras apagadas, dañadas o en mal funcionamiento', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 93 Frac. XII', 'descripcion' => 'Realizar competencias de velocidad en vía pública o zigzaguear', 'categoria_actor' => 'motociclista', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 62 Frac. I', 'descripcion' => 'No dar preferencia al peatón', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 62 Frac. II', 'descripcion' => 'No respetar señales de tránsito, indicaciones de agentes o dispositivos viales', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 62 Frac. III', 'descripcion' => 'Circular en sentido opuesto en vialidad compartida o exclusiva (ciclovía)', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 62 Frac. IV', 'descripcion' => 'Circular irresponsablemente en vialidades compartidas', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 62 Frac. V', 'descripcion' => 'Rebasar a otro vehículo por el costado derecho', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 62 Frac. VI', 'descripcion' => 'No transitar por la ciclovía existiendo una disponible en la vialidad', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 3.0],
            ['fundamento_legal' => 'Art. 62 Frac. VII', 'descripcion' => 'No circular por la extrema derecha (a distancia mayor de 1m de la banqueta)', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 62 Frac. VIII', 'descripcion' => 'No portar aditamento/banda reflejante o luminosa para visión nocturna', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 6.0],
            ['fundamento_legal' => 'Art. 62 Frac. IX', 'descripcion' => 'Llevar más personas que las previstas en el diseño de la bicicleta', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 6.0],
            ['fundamento_legal' => 'Art. 62 Frac. X', 'descripcion' => 'No indicar giro o cambio de carril con brazo y mano', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 6.0],
            ['fundamento_legal' => 'Art. 62 Frac. XI', 'descripcion' => 'No compartir irresponsablemente carriles de extrema derecha', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 62 Frac. XII', 'descripcion' => 'Circular sin casco protector y/o sin chaleco reflejante nocturno', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 62 Frac. XIII', 'descripcion' => 'No respetar carriles confinados para otros modos de transporte', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 62 Frac. XIV', 'descripcion' => 'En intersección con alto, no detenerse para ceder el paso', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 3.0],
            ['fundamento_legal' => 'Art. 62 Frac. XV', 'descripcion' => 'Realizar reparaciones mecánicas sobre la superficie de rodamiento/ciclovía', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 1.0, 'monto_max_uma' => 2.0],
            ['fundamento_legal' => 'Art. 63 Frac. I', 'descripcion' => 'Arrojar basura u objetos que obstaculicen o dañen la vía pública', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 63 Frac. II', 'descripcion' => 'Usar audífonos, celulares o dispositivos electrónicos al circular', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 5.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 63 Frac. III', 'descripcion' => 'Conducir bajo efectos de alcohol o drogas / consumirlos durante la marcha', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 10.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 63 Frac. IV', 'descripcion' => 'Sujetarse a vehículos en movimiento', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 63 Frac. V', 'descripcion' => 'Circular sobre banquetas, zonas peatonales o vías restringidas', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 6.0],
            ['fundamento_legal' => 'Art. 63 Frac. VI', 'descripcion' => 'Circular en línea al lado de otro ciclista', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 63 Frac. VII', 'descripcion' => 'Adelantar vehículos por el carril de la izquierda', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 63 Frac. VIII', 'descripcion' => 'Llevar carga que dificulte visibilidad, equilibrio o maniobra', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 63 Frac. IX', 'descripcion' => 'Transitar con animales sin correa o canastilla asegurada', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 3.0],
            ['fundamento_legal' => 'Art. 63 Frac. X', 'descripcion' => 'Zigzaguear, realizar acrobacias o competencias de velocidad', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 6.0],
            ['fundamento_legal' => 'Art. 63 Frac. XI', 'descripcion' => 'Circular por carriles centrales/interiores de acceso controlado', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 6.0],
            ['fundamento_legal' => 'Art. 63 Frac. XII', 'descripcion' => 'Circular entre carriles', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 6.0],
            ['fundamento_legal' => 'Art. 63 Frac. XIII', 'descripcion' => 'Transportar pasajero entre la persona que conduce y el manubrio', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 6.0],
            ['fundamento_legal' => 'Art. 64 Frac. I', 'descripcion' => 'Colocar, abandonar o arrojar objetos/materiales en la ciclovía', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 1.0, 'monto_max_uma' => 3.0],
            ['fundamento_legal' => 'Art. 64 Frac. II', 'descripcion' => 'Ocupar transitoriamente la ciclovía y obstaculizar el libre flujo ciclista', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 1.0, 'monto_max_uma' => 2.0],
            ['fundamento_legal' => 'Art. 64 Frac. III', 'descripcion' => 'Realizar cualquier actividad en la ciclovía que afecte la seguridad', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 2.0, 'monto_max_uma' => 3.0],
            ['fundamento_legal' => 'Art. 64 Frac. IV', 'descripcion' => 'Circular en sentido contrario o invadir el carril de contraflujo', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 1.0, 'monto_max_uma' => 2.0],
            ['fundamento_legal' => 'Art. 64 Frac. V', 'descripcion' => 'Caminar sin justificación y por tiempo prolongado sobre la ciclovía', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 1.0, 'monto_max_uma' => 2.0],
            ['fundamento_legal' => 'Art. 64 Frac. VI', 'descripcion' => 'Destruir, deteriorar o alterar obra/instalación de la ciclovía', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 64 Frac. VII', 'descripcion' => 'Circular con vehículo automotor en ciclovía o estacionarse interrumpiendo flujo', 'categoria_actor' => 'ciclista', 'monto_min_uma' => 3.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 382 Frac. II', 'descripcion' => 'Prestar un servicio distinto al autorizado', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 20.0, 'monto_max_uma' => 25.0],
            ['fundamento_legal' => 'Art. 382 Frac. IV', 'descripcion' => 'Permitir conducir a persona no señalada en la hoja de despacho de la ruta', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 30.0, 'monto_max_uma' => 40.0],
            ['fundamento_legal' => 'Art. 382 Frac. V', 'descripcion' => 'Cobrar tarifa diferente a la autorizada', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 15.0, 'monto_max_uma' => 18.0],
            ['fundamento_legal' => 'Art. 382 Frac. VIII', 'descripcion' => 'No contar el operador con licencia o tarjetón vigente', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 8.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 382 Frac. X', 'descripcion' => 'Prestar el servicio con vehículos no autorizados por la Dirección', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 40.0, 'monto_max_uma' => 50.0],
            ['fundamento_legal' => 'Art. 382 Frac. XI', 'descripcion' => 'Unidades sin aprobación de la revista físico-mecánica', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 40.0, 'monto_max_uma' => 50.0],
            ['fundamento_legal' => 'Art. 382 Frac. XIII', 'descripcion' => 'No colaborar en estado de emergencia o contingencia', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 50.0, 'monto_max_uma' => 55.0],
            ['fundamento_legal' => 'Art. 382 Frac. XIV', 'descripcion' => 'Prestar el servicio con colores o imagen distinta a la autorizada', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 10.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 382 Frac. XVI', 'descripcion' => 'Portar publicidad sin el permiso correspondiente', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 20.0, 'monto_max_uma' => 25.0],
            ['fundamento_legal' => 'Art. 382 Frac. XVII', 'descripcion' => 'No permitir la inspección de vehículos, instalaciones y documentos', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 30.0, 'monto_max_uma' => 35.0],
            ['fundamento_legal' => 'Art. 382 Frac. XVIII', 'descripcion' => 'No proporcionar los documentos requeridos a la autoridad', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 29.0, 'monto_max_uma' => 30.0],
            ['fundamento_legal' => 'Art. 382 Frac. XIX', 'descripcion' => 'Colocar aditamentos que impidan la visibilidad o alteren imagen de agencia', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 10.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 382 Frac. XX', 'descripcion' => 'Negarse a colaborar en campañas y cursos de cultura vial', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 14.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 382 Frac. XXI', 'descripcion' => 'No mantener los vehículos en óptimas condiciones', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 15.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 382 Frac. XXII', 'descripcion' => 'Circular sin placas o sin el permiso respectivo vigente', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 8.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 365 / Art. 368', 'descripcion' => 'Entregar comprobante de pago en efectivo sin los datos obligatorios', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 10.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 389 Frac. I', 'descripcion' => 'Ejercer violencia verbal o moral contra algún usuario', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 20.0, 'monto_max_uma' => 25.0],
            ['fundamento_legal' => 'Art. 389 Frac. II', 'descripcion' => 'Cometer actos discriminatorios contra el usuario', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 25.0, 'monto_max_uma' => 50.0],
            ['fundamento_legal' => 'Art. 389 Frac. III', 'descripcion' => 'No cumplir los horarios de ruta o despachos programados', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 40.0, 'monto_max_uma' => 45.0],
            ['fundamento_legal' => 'Art. 389 Frac. IV', 'descripcion' => 'Falta de aseo personal del operador', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 5.0, 'monto_max_uma' => 8.0],
            ['fundamento_legal' => 'Art. 389 Frac. V', 'descripcion' => 'Fumar a bordo de la unidad', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 30.0, 'monto_max_uma' => 35.0],
            ['fundamento_legal' => 'Art. 389 Frac. XII', 'descripcion' => 'Negarse a la práctica de exámenes médicos/toxicológicos', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 14.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 389 Frac. XIV', 'descripcion' => 'Ascenso o descenso de pasaje fuera de paradas autorizadas', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 10.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 389 Frac. XV', 'descripcion' => 'Permanecer en la parada sin estar realizando ascenso/descenso', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 10.0, 'monto_max_uma' => 15.0],
            ['fundamento_legal' => 'Art. 389 Frac. XVI', 'descripcion' => 'No respetar tarifa preferencial o cobrar pasaje a menores de 6 años', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 15.0, 'monto_max_uma' => 18.0],
            ['fundamento_legal' => 'Art. 389 Frac. XVIII', 'descripcion' => 'Bloquear el tránsito vehicular', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 29.0, 'monto_max_uma' => 30.0],
            ['fundamento_legal' => 'Art. 389 Frac. XIX', 'descripcion' => 'No respetar las restricciones de velocidad', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 30.0, 'monto_max_uma' => 35.0],
            ['fundamento_legal' => 'Art. 389 Frac. XXIV', 'descripcion' => 'No mantener puertas cerradas con el vehículo en movimiento', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 8.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 389 Frac. XXV', 'descripcion' => 'Estacionarse en la vía pública', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 50.0, 'monto_max_uma' => 55.0],
            ['fundamento_legal' => 'Art. 389 Frac. XXVI', 'descripcion' => 'Llevar acompañantes que lo distraigan al conducir', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 15.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 389 Frac. XXVII', 'descripcion' => 'Circular fuera de carriles autorizados / no circular por la extrema derecha', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 8.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 389 Frac. XXVIII', 'descripcion' => 'Llevar equipo reproductor de música que exceda los 68 dB', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 40.0, 'monto_max_uma' => 50.0],
            ['fundamento_legal' => 'Art. 389 Frac. XXIX', 'descripcion' => 'No obedecer señales de tránsito y/o luz roja de alto', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 8.0, 'monto_max_uma' => 10.0],
            ['fundamento_legal' => 'Art. 389 Frac. XXX', 'descripcion' => 'Negarse a respetar medidas de sanidad/salud decretadas', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 50.0, 'monto_max_uma' => 55.0],
            ['fundamento_legal' => 'Art. 390 Frac. I', 'descripcion' => 'Ingerir bebidas alcohólicas o consumir drogas durante el servicio', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 50.0, 'monto_max_uma' => 60.0],
            ['fundamento_legal' => 'Art. 390 Frac. II', 'descripcion' => 'Conducir con audífonos, celular o dispositivos de distracción', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 15.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 390 Frac. III', 'descripcion' => 'Conducir o presentarse a trabajar con aliento alcohólico', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 15.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 390 Frac. IV', 'descripcion' => 'Conducir o presentarse a trabajar bajo efecto de drogas', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 50.0, 'monto_max_uma' => 60.0],
            ['fundamento_legal' => 'Art. 390 Frac. V', 'descripcion' => 'Realizar actos deshonestos u obscenos a bordo/terminal', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 29.0, 'monto_max_uma' => 30.0],
            ['fundamento_legal' => 'Art. 390 Frac. VI', 'descripcion' => 'Realizar competencias por pasaje en la vía pública', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 15.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 390 Frac. VII', 'descripcion' => 'Abastecer combustible con pasajeros a bordo', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 15.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 390 Frac. VIII', 'descripcion' => 'Circular con pasajeros en toldos, cofres, defensas, estribos o colgados', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 50.0, 'monto_max_uma' => 60.0],
            ['fundamento_legal' => 'Art. 390 Frac. IX', 'descripcion' => 'Permitir conducir a persona que no cuente con cédula de operador', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 25.0, 'monto_max_uma' => 30.0],
            ['fundamento_legal' => 'Art. 390 Frac. X', 'descripcion' => 'Transportar combustibles, materiales explosivos, corrosivos o pestilentes', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 25.0, 'monto_max_uma' => 30.0],
            ['fundamento_legal' => 'Art. 390 Frac. XI', 'descripcion' => 'Alterar o modificar la imagen del vehículo sin autorización', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 20.0, 'monto_max_uma' => 25.0],
            ['fundamento_legal' => 'Art. 390 Frac. XII', 'descripcion' => 'Cubrir ventanas dificultando la visibilidad al interior/exterior', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 15.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 390 Frac. XIII', 'descripcion' => 'Fijar placas con soldadura u otro material que impida el retiro', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 15.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 390 Frac. XIV', 'descripcion' => 'Sobornar/instigar al inspector de movilidad o faltarle al respeto', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 15.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 390 Frac. XVI', 'descripcion' => 'Ejercer violencia física contra algún usuario', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 25.0, 'monto_max_uma' => 50.0],
            ['fundamento_legal' => 'Art. 390 Frac. XVI (bis)', 'descripcion' => 'Ejercer violencia verbal, moral o física contra otros usuarios de la vía', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 25.0, 'monto_max_uma' => 50.0],
            ['fundamento_legal' => 'Art. 395 Frac. II / Art. 398', 'descripcion' => 'No proporcionar boleto al usuario que paga pasaje en efectivo', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 4.0, 'monto_max_uma' => 5.0],
            ['fundamento_legal' => 'Art. 395 Frac. IV / Art. 398', 'descripcion' => 'No cobrar tarifa preferencial a beneficiarios legítimos', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 20.0, 'monto_max_uma' => 30.0],
            ['fundamento_legal' => 'Art. 395 Frac. V / Art. 398', 'descripcion' => 'No permitir ascenso/descenso por puerta delantera a grupos vulnerables', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 15.0, 'monto_max_uma' => 20.0],
            ['fundamento_legal' => 'Art. 395 Frac. VII / Art. 398', 'descripcion' => 'Ante descompostura, no sustituir unidad en 15 min ni devolver pasaje', 'categoria_actor' => 'concesionario_operador', 'monto_min_uma' => 10.0, 'monto_max_uma' => 20.0],
        ];

        $existentes = $this->db->table('catalogo_infracciones')
            ->select('fundamento_legal')
            ->get()
            ->getResultArray();
        $yaExisten = array_column($existentes, 'fundamento_legal');

        $nuevos = array_filter($infracciones, function ($inf) use ($yaExisten) {
            return !in_array($inf['fundamento_legal'], $yaExisten, true);
        });

        if (!empty($nuevos)) {
            $this->db->table('catalogo_infracciones')->insertBatch(array_values($nuevos));
        }

        // Parámetros de sistema (configuración de UMA, descuentos y recargos)
        $parametros = [
            [
                'clave'       => 'valor_uma_vigente',
                'valor'       => '117.31',
                'descripcion' => 'Pendiente de confirmar valor oficial vigente con INEGI',
            ],
            [
                'clave'       => 'porcentaje_descuento_pronto_pago',
                'valor'       => '40.00',
                'descripcion' => 'Porcentaje de descuento por pronto pago (primeros 10 días hábiles)',
            ],
            [
                'clave'       => 'dias_habiles_descuento',
                'valor'       => '10',
                'descripcion' => 'Días hábiles límite para aplicar descuento por pronto pago (Lunes a Viernes)',
            ],
            [
                'clave'       => 'porcentaje_recargo_mensual',
                'valor'       => '5.00',
                'descripcion' => 'Porcentaje de recargo mensual por mora después de 1 mes (Art. 180 del Reglamento). Nota: Mencionado verbalmente como 0.05% por el Juez, pero impreso como 5% en boleta oficial.',
            ],
        ];

        foreach ($parametros as $param) {
            $paramExists = $this->db->table('parametros_sistema')
                ->where('clave', $param['clave'])
                ->countAllResults();

            if ($paramExists === 0) {
                $this->db->table('parametros_sistema')->insert([
                    'clave'          => $param['clave'],
                    'valor'          => $param['valor'],
                    'descripcion'    => $param['descripcion'],
                    'actualizado_en' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
