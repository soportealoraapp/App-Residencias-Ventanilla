-- =============================================================================
-- 0019 · Catálogo oficial de infracciones (201 faltas) en Supabase y en la app
-- =============================================================================
--
-- Por qué: 0008 creó las tablas catalogo_infracciones y boleta_checklist_item
--   pero nunca les cargó datos, así que la base hosteada no tenía el reglamento.
--   Y desde f870a1d la app de agentes toma sus faltas de catalogo_infracciones_app
--   (Panel → App tránsito → Catálogo), que solo tenía las 14 faltas de ejemplo de
--   0005. Por eso la app mostraba 14 faltas en lugar de las 201.
--
-- Qué hace:
--   1. Carga las 201 faltas oficiales (catalogo_infracciones) si no existen.
--   2. Carga las 80 casillas de la boleta física (boleta_checklist_item) si no existen.
--   3. Pasa las 201 al catálogo de la app. De las 14 de ejemplo, las que siguen
--      exactamente como se sembraron toman el texto y montos oficiales, y las 4
--      cuyo artículo no está en el reglamento se desactivan (no se borran).
--      Lo que el administrador haya editado o creado desde el panel no se toca.
--
-- Cómo usarlo: péguelo completo en el SQL Editor de Supabase y ejecútelo.
--   Es idempotente y transaccional.
--
-- Equivale a app/Database/Migrations/2026-11-03-000034_CatalogoAppConInfraccionesOficiales.php
-- (lógica en app/Database/Seeds/CatalogoInfraccionesAppSeeder.php).
-- =============================================================================

BEGIN;

-- Si los datos se importaron con ids explícitos, las secuencias pueden ir atrasadas.
SELECT setval(pg_get_serial_sequence('public.catalogo_infracciones', 'id'),
              COALESCE((SELECT MAX(id) FROM public.catalogo_infracciones), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.boleta_checklist_item', 'id'),
              COALESCE((SELECT MAX(id) FROM public.boleta_checklist_item), 0) + 1, false);
SELECT setval(pg_get_serial_sequence('public.catalogo_infracciones_app', 'id'),
              COALESCE((SELECT MAX(id) FROM public.catalogo_infracciones_app), 0) + 1, false);

-- 1. Catálogo oficial del reglamento (equivale a CatalogoInfraccionesSeeder).
INSERT INTO public.catalogo_infracciones (fundamento_legal, descripcion, categoria_actor, monto_min_uma, monto_max_uma, created_at, updated_at)
SELECT v.fundamento_legal, v.descripcion, v.categoria_actor, v.monto_min_uma, v.monto_max_uma, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM (VALUES
    ('Art. 39 Frac. I', 'No obedecer los señalamientos viales de Tránsito', 'conductor_general', 15, 20),
    ('Art. 39 Frac. II', 'No obedecer indicaciones del personal de la Dirección y/o promotores voluntarios', 'conductor_general', 5, 10),
    ('Art. 39 Frac. III', 'No respetar los límites de velocidad establecidos', 'conductor_general', 30, 50),
    ('Art. 39 Frac. IV', 'Circular en sentido opuesto al que indica la vialidad', 'conductor_general', 3, 5),
    ('Art. 39 Frac. V', 'No utilizar el cinturón de seguridad el conductor y/o ocupantes', 'conductor_general', 5, 10),
    ('Art. 39 Frac. VI', 'No utilizar el sistema de retención infantil al llevar acompañantes menores', 'conductor_general', 5, 10),
    ('Art. 39 Frac. VII', 'Circular sin faros delanteros y/o luces posteriores', 'conductor_general', 5, 10),
    ('Art. 39 Frac. VIII', 'No orillarse al carril derecho ni utilizar intermitentes para ascenso/descenso', 'conductor_general', 10, 15),
    ('Art. 39 Frac. IX', 'No obedecer la señal de alto cuando la luz del semáforo esté en rojo', 'conductor_general', 15, 25),
    ('Art. 39 Frac. X', 'No obedecer la señal de alto indicada por el Agente u Oficial', 'conductor_general', 10, 15),
    ('Art. 39 Frac. XI', 'No respetar el derecho de preferencia de paso a peatones y/o ciclistas', 'conductor_general', 5, 10),
    ('Art. 39 Frac. XII', 'Al ascender/descender, no cerciorarse de que no exista peligro', 'conductor_general', 5, 10),
    ('Art. 39 Frac. XIII', 'No ceder el paso al incorporarse a una vía rápida o primaria', 'conductor_general', 5, 10),
    ('Art. 39 Frac. XIV', 'Al circular en vía de menor jerarquía, no ceder el paso a vehículos de vía mayor', 'conductor_general', 5, 10),
    ('Art. 39 Frac. XV', 'No ceder el paso en intersección sin señalamientos a vehículo dentro de ella', 'conductor_general', 5, 10),
    ('Art. 39 Frac. XVI', 'No contar con luces indicadoras de frenos', 'conductor_general', 3, 5),
    ('Art. 39 Frac. XVII', 'No contar con luces direccionales o intermitentes', 'conductor_general', 3, 5),
    ('Art. 39 Frac. XVIII', 'No contar con cuartos delanteros y/o traseros', 'conductor_general', 3, 5),
    ('Art. 39 Frac. XIX', 'No contar con luces indicadoras de reversa', 'conductor_general', 3, 5),
    ('Art. 39 Frac. XX', 'Rebasar por la derecha y/o no respetar la distancia lateral mínima de 1 metro', 'conductor_general', 3, 5),
    ('Art. 39 Frac. XXI', 'No dar preferencia o facilidades a la circulación de transporte público', 'conductor_general', 10, 15),
    ('Art. 39 Frac. XXII', 'No dar preferencia a vehículos de emergencia', 'conductor_general', 10, 15),
    ('Art. 39 Frac. XXIII', 'Pasar la intersección ante el indicativo de luz ámbar del semáforo', 'conductor_general', 5, 10),
    ('Art. 39 Frac. XXIV', 'Dar vuelta a la derecha o izquierda sin flecha o luz verde del semáforo', 'conductor_general', 5, 15),
    ('Art. 39 Frac. XXV', 'Circular con puertas abiertas', 'conductor_general', 15, 30),
    ('Art. 39 Frac. XXVI', 'Llevar a bordo más personas de las indicadas en la tarjeta de circulación', 'conductor_general', 3, 5),
    ('Art. 40 Frac. I', 'No respetar la preferencia de paso de los peatones', 'conductor_general', 5, 10),
    ('Art. 40 Frac. II', 'No respetar la preferencia de paso de vehículos no motorizados', 'conductor_general', 5, 10),
    ('Art. 40 Frac. III', 'No detenerse en cruceros antes de cruzar', 'conductor_general', 3, 5),
    ('Art. 40 Frac. IV a)', 'Semáforo en rojo: no detenerse en línea de alto o invadir cruce peatonal/espera ciclista', 'conductor_general', 3, 5),
    ('Art. 40 Frac. IV b)', 'Semáforo en verde con congestión: no detenerse y obstruir calle transversal', 'conductor_general', 3, 5),
    ('Art. 40 Frac. IV c)', 'Semáforo destellando en rojo: no detener la marcha en la línea de alto', 'conductor_general', 3, 5),
    ('Art. 40 Frac. IV d)', 'Semáforo destellando en verde: no disminuir la velocidad', 'conductor_general', 3, 5),
    ('Art. 40 Frac. IV e)', 'Semáforos apagados/sin señalamiento: no respetar regla del uno a uno', 'conductor_general', 3, 5),
    ('Art. 40 Frac. IV f)', 'Vuelta continua sin señalamiento: poner en riesgo al peatón', 'conductor_general', 3, 5),
    ('Art. 40 Frac. V', 'No detener la marcha cuando no sea posible cruzar la vía en su totalidad', 'conductor_general', 3, 4),
    ('Art. 40 Frac. VI', 'No dar preferencia de paso a vehículos en glorieta o al salir de esta', 'conductor_general', 3, 5),
    ('Art. 40 Frac. VII', 'Intersecciones de misma jerarquía sin señalamiento: no respetar regla uno a uno', 'conductor_general', 5, 10),
    ('Art. 40 Frac. VIII', 'No dar preferencia a peatones/ciclistas en accesos de cocheras, comercios, etc.', 'conductor_general', 5, 10),
    ('Art. 40 Frac. IX', 'No conservar respecto al vehículo precedente la distancia de seguridad oportuna', 'conductor_general', 5, 10),
    ('Art. 87 Frac. I a)', 'Transitar sin ambas placas o sin una de las placas', 'conductor_general', 2, 5),
    ('Art. 87 Frac. I b)', 'Transitar con placas ocultas o en el interior del vehículo', 'conductor_general', 2, 5),
    ('Art. 87 Frac. I c)', 'Utilizar placas con alteraciones o cubiertas que dificulten la visibilidad', 'conductor_general', 2, 5),
    ('Art. 87 Frac. I d)', 'Fijar placas con soldadura, remaches o tornillos modificados', 'conductor_general', 2, 5),
    ('Art. 87 Frac. II', 'No portar permiso provisional de circulación en ausencia de placas', 'conductor_general', 2, 5),
    ('Art. 87 Frac. III', 'No portar licencia o permiso de conducir vigente correspondiente al vehículo', 'conductor_general', 2, 5),
    ('Art. 87 Frac. IV', 'No portar tarjeta de circulación original', 'conductor_general', 2, 5),
    ('Art. 87 Frac. V', 'No portar póliza vigente de seguro por responsabilidad civil', 'conductor_general', 2, 5),
    ('Art. 87 Frac. VI', 'No entregar licencia o tarjeta de circulación a requerimiento de la autoridad', 'conductor_general', 5, 10),
    ('Art. 87 Frac. VII', 'No dar preferencia a peatones, personas con discapacidad o transporte no motorizado', 'conductor_general', 5, 10),
    ('Art. 87 Frac. VIII', 'No respetar paso peatonal en esquinas / no detenerse o usar el claxon', 'conductor_general', 3, 5),
    ('Art. 87 Frac. IX', 'Realizar maniobras imprudentes al manejar', 'conductor_general', 5, 8),
    ('Art. 87 Frac. X', 'No transitar por el carril derecho de las vías públicas', 'conductor_general', 3, 5),
    ('Art. 87 Frac. XI', 'Rebasar por la derecha / no dejar 1.50m de separación al rebasar ciclistas/motos', 'conductor_general', 5, 10),
    ('Art. 87 Frac. XII', 'Cambiar de carril o tomar carril extremo de giro de manera irresponsable', 'conductor_general', 3, 5),
    ('Art. 87 Frac. XIII', 'En cruce uno a uno, no detenerse para ceder el paso', 'conductor_general', 10, 15),
    ('Art. 87 Frac. XIV', 'No esperar la marcha del vehículo detenido para ceder paso a peatones', 'conductor_general', 3, 5),
    ('Art. 87 Frac. XV', 'No indicar mediante luces direccionales el giro o cambio de carril', 'conductor_general', 3, 5),
    ('Art. 87 Frac. XVI', 'Llevar menores en asientos traseros sin cinturón o sistema de retención', 'conductor_general', 5, 10),
    ('Art. 87 Frac. XVII', 'No colaborar en la limpieza o retiro de objetos de la vía pública', 'conductor_general', 3, 5),
    ('Art. 87 Frac. XVIII', 'Ante avería, no retirar el vehículo de la superficie de rodamiento', 'conductor_general', 2, 3),
    ('Art. 87 Frac. XIX', 'Realizar prácticas de manejo en áreas con tráfico vehicular', 'conductor_general', 3, 5),
    ('Art. 87 Frac. XX', 'No dar prioridad a vehículos de emergencia con señales luminosas/audibles', 'conductor_general', 3, 5),
    ('Art. 87 Frac. XXI', 'Transportar animales en vehículos inadecuados o sin medidas de seguridad', 'conductor_general', 3, 5),
    ('Art. 87 Frac. XXII', 'Transportar mascotas sin tomar medidas para evitar distracción/accidente', 'conductor_general', 2, 3),
    ('Art. 87 Frac. XXIII', 'No apagar el vehículo al cargar combustible y/o fumar en gasolineras', 'conductor_general', 3, 5),
    ('Art. 87 Frac. XXIV', 'Circular sin calcomanía/comprobante de verificación anticontaminante', 'conductor_general', 3, 5),
    ('Art. 87 Frac. XXV', 'No respetar disposiciones sanitarias de carácter obligatorio durante emergencias', 'conductor_general', 5, 10),
    ('Art. 89 Frac. I', 'Conducir bajo efectos de alcohol, enervantes o estupefacientes', 'conductor_general', 10, 15),
    ('Art. 89 Frac. II', 'Conducir un vehículo que no reúna los requisitos legales para circular', 'conductor_general', 5, 10),
    ('Art. 89 Frac. III', 'Conducir llevando entre los brazos personas, animales u objetos', 'conductor_general', 5, 10),
    ('Art. 89 Frac. IV', 'Utilizar equipos de comunicación móvil/portátil al conducir', 'conductor_general', 10, 20),
    ('Art. 89 Frac. V', 'Circular en áreas destinadas a peatones, personas con discapacidad o ciclistas', 'conductor_general', 5, 10),
    ('Art. 89 Frac. VI', 'Circular sobre las líneas delimitadoras de carriles o zonas de transición', 'conductor_general', 2, 3),
    ('Art. 89 Frac. VII', 'Circular por plazas, aceras, banquetas, camellones, parques y jardines', 'conductor_general', 3, 5),
    ('Art. 89 Frac. VIII', 'Circular de forma continua sobre acotamiento o rebasar por dicho lugar', 'conductor_general', 5, 10),
    ('Art. 89 Frac. IX', 'Circular detrás de vehículos de emergencia (<50m de distancia)', 'conductor_general', 3, 5),
    ('Art. 89 Frac. X', 'Circular a velocidad excesivamente baja sin causa', 'conductor_general', 2, 3),
    ('Art. 89 Frac. XI', 'Adelantar o rebasar a un vehículo que se encuentra realizando la misma maniobra', 'conductor_general', 5, 10),
    ('Art. 89 Frac. XII', 'Permitir que un pasajero tome el control del volante', 'conductor_general', 5, 10),
    ('Art. 89 Frac. XIII', 'Detener el vehículo invadiendo cruces peatonales o áreas ciclistas', 'conductor_general', 3, 5),
    ('Art. 89 Frac. XIV', 'Entorpecer la marcha de cortejos fúnebres, desfiles, competencias, caravanas', 'conductor_general', 2, 3),
    ('Art. 89 Frac. XV', 'Acrobacias, arrancones o competencias en vía pública', 'conductor_general', 10, 20),
    ('Art. 89 Frac. XVI', 'Transportar personas en carrocería, toldos, cofres, estribos', 'conductor_general', 5, 10),
    ('Art. 89 Frac. XVII', 'Abrir las puertas cuando el vehículo se encuentre en movimiento', 'conductor_general', 10, 15),
    ('Art. 89 Frac. XVIII', 'Estacionar el vehículo en doble fila', 'conductor_general', 3, 5),
    ('Art. 89 Frac. XIX', 'Estacionar en paradas del transporte público o zonas de ascenso y descenso', 'conductor_general', 3, 5),
    ('Art. 89 Frac. XX', 'Ostentar en vehículo particular colores/números exclusivos de transporte público', 'conductor_general', 10, 15),
    ('Art. 89 Frac. XXI', 'Transportar combustible o sustancias peligrosas en envases abiertos o de cristal', 'conductor_general', 10, 20),
    ('Art. 89 Frac. XXII', 'Transportar materiales/sustancias inflamables o explosivas sin autorización', 'conductor_general', 10, 20),
    ('Art. 89 Frac. XXIII', 'Arrojar objetos o basura desde el interior del vehículo', 'conductor_general', 5, 10),
    ('Art. 92 Frac. I', 'No respetar a peatones, ciclistas o conductores de vehículos no motorizados', 'motociclista', 5, 10),
    ('Art. 92 Frac. II', 'No respetar los señalamientos viales', 'motociclista', 5, 10),
    ('Art. 92 Frac. III', 'Circular con más personas que las plazas designadas por el fabricante', 'motociclista', 5, 10),
    ('Art. 92 Frac. IV', 'No circular por el carril de la extrema derecha', 'motociclista', 5, 10),
    ('Art. 92 Frac. V', 'Conductor y/o tripulantes sin casco protector reglamentario', 'motociclista', 2, 5),
    ('Art. 92 Frac. VI', 'No utilizar un solo carril o circular en forma paralela con otras motocicletas', 'motociclista', 3, 8),
    ('Art. 92 Frac. VII', 'Sujetarse a otros vehículos en circulación', 'motociclista', 2, 5),
    ('Art. 92 Frac. VIII', 'Sin tarjeta de circulación, placa visible o colocada en sitio distinto al fabricante', 'motociclista', 5, 10),
    ('Art. 92 Frac. IX', 'No portar licencia de conducir vigente modalidad motociclista', 'motociclista', 2, 5),
    ('Art. 93 Frac. I', 'Transportar pasajero entre la persona que conduce y el manubrio', 'motociclista', 2, 5),
    ('Art. 93 Frac. II', 'Transportar carga u objeto que impida ambas manos en manubrio o visibilidad', 'motociclista', 2, 5),
    ('Art. 93 Frac. III', 'Sujetarse a otros vehículos en circulación', 'motociclista', 2, 5),
    ('Art. 93 Frac. IV', 'Uso de celulares, audífonos o dispositivos de distracción al conducir', 'motociclista', 5, 10),
    ('Art. 93 Frac. V', 'Estacionarse sobre zonas peatonales, banquetas o rampas de discapacidad', 'motociclista', 2, 3),
    ('Art. 93 Frac. VI', 'Circular sin luces principales/alto/direccionales, espejos o frenos/llantas en mal estado', 'motociclista', 3, 5),
    ('Art. 93 Frac. VII', 'Circular con fugas de combustible o aceite', 'motociclista', 5, 10),
    ('Art. 93 Frac. VIII', 'Circular sin escape/silenciador, o ruidoso/contaminante', 'motociclista', 2, 3),
    ('Art. 93 Frac. IX', 'Circular con luces frontales apagadas o de color distinto al de agencia', 'motociclista', 3, 5),
    ('Art. 93 Frac. X', 'Circular con equipos de audio/bocinas que excedan de 75 dB a 2 metros', 'motociclista', 3, 5),
    ('Art. 93 Frac. XI', 'Circular con luces traseras apagadas, dañadas o en mal funcionamiento', 'motociclista', 5, 10),
    ('Art. 93 Frac. XII', 'Realizar competencias de velocidad en vía pública o zigzaguear', 'motociclista', 5, 10),
    ('Art. 62 Frac. I', 'No dar preferencia al peatón', 'ciclista', 2, 5),
    ('Art. 62 Frac. II', 'No respetar señales de tránsito, indicaciones de agentes o dispositivos viales', 'ciclista', 2, 5),
    ('Art. 62 Frac. III', 'Circular en sentido opuesto en vialidad compartida o exclusiva (ciclovía)', 'ciclista', 2, 5),
    ('Art. 62 Frac. IV', 'Circular irresponsablemente en vialidades compartidas', 'ciclista', 2, 5),
    ('Art. 62 Frac. V', 'Rebasar a otro vehículo por el costado derecho', 'ciclista', 2, 5),
    ('Art. 62 Frac. VI', 'No transitar por la ciclovía existiendo una disponible en la vialidad', 'ciclista', 2, 3),
    ('Art. 62 Frac. VII', 'No circular por la extrema derecha (a distancia mayor de 1m de la banqueta)', 'ciclista', 3, 5),
    ('Art. 62 Frac. VIII', 'No portar aditamento/banda reflejante o luminosa para visión nocturna', 'ciclista', 3, 6),
    ('Art. 62 Frac. IX', 'Llevar más personas que las previstas en el diseño de la bicicleta', 'ciclista', 3, 6),
    ('Art. 62 Frac. X', 'No indicar giro o cambio de carril con brazo y mano', 'ciclista', 3, 6),
    ('Art. 62 Frac. XI', 'No compartir irresponsablemente carriles de extrema derecha', 'ciclista', 2, 5),
    ('Art. 62 Frac. XII', 'Circular sin casco protector y/o sin chaleco reflejante nocturno', 'ciclista', 2, 5),
    ('Art. 62 Frac. XIII', 'No respetar carriles confinados para otros modos de transporte', 'ciclista', 3, 5),
    ('Art. 62 Frac. XIV', 'En intersección con alto, no detenerse para ceder el paso', 'ciclista', 2, 3),
    ('Art. 62 Frac. XV', 'Realizar reparaciones mecánicas sobre la superficie de rodamiento/ciclovía', 'ciclista', 1, 2),
    ('Art. 63 Frac. I', 'Arrojar basura u objetos que obstaculicen o dañen la vía pública', 'ciclista', 5, 10),
    ('Art. 63 Frac. II', 'Usar audífonos, celulares o dispositivos electrónicos al circular', 'ciclista', 5, 10),
    ('Art. 63 Frac. III', 'Conducir bajo efectos de alcohol o drogas / consumirlos durante la marcha', 'ciclista', 10, 20),
    ('Art. 63 Frac. IV', 'Sujetarse a vehículos en movimiento', 'ciclista', 2, 5),
    ('Art. 63 Frac. V', 'Circular sobre banquetas, zonas peatonales o vías restringidas', 'ciclista', 3, 6),
    ('Art. 63 Frac. VI', 'Circular en línea al lado de otro ciclista', 'ciclista', 2, 5),
    ('Art. 63 Frac. VII', 'Adelantar vehículos por el carril de la izquierda', 'ciclista', 2, 5),
    ('Art. 63 Frac. VIII', 'Llevar carga que dificulte visibilidad, equilibrio o maniobra', 'ciclista', 2, 5),
    ('Art. 63 Frac. IX', 'Transitar con animales sin correa o canastilla asegurada', 'ciclista', 2, 3),
    ('Art. 63 Frac. X', 'Zigzaguear, realizar acrobacias o competencias de velocidad', 'ciclista', 3, 6),
    ('Art. 63 Frac. XI', 'Circular por carriles centrales/interiores de acceso controlado', 'ciclista', 3, 6),
    ('Art. 63 Frac. XII', 'Circular entre carriles', 'ciclista', 3, 6),
    ('Art. 63 Frac. XIII', 'Transportar pasajero entre la persona que conduce y el manubrio', 'ciclista', 3, 6),
    ('Art. 64 Frac. I', 'Colocar, abandonar o arrojar objetos/materiales en la ciclovía', 'ciclista', 1, 3),
    ('Art. 64 Frac. II', 'Ocupar transitoriamente la ciclovía y obstaculizar el libre flujo ciclista', 'ciclista', 1, 2),
    ('Art. 64 Frac. III', 'Realizar cualquier actividad en la ciclovía que afecte la seguridad', 'ciclista', 2, 3),
    ('Art. 64 Frac. IV', 'Circular en sentido contrario o invadir el carril de contraflujo', 'ciclista', 1, 2),
    ('Art. 64 Frac. V', 'Caminar sin justificación y por tiempo prolongado sobre la ciclovía', 'ciclista', 1, 2),
    ('Art. 64 Frac. VI', 'Destruir, deteriorar o alterar obra/instalación de la ciclovía', 'ciclista', 3, 10),
    ('Art. 64 Frac. VII', 'Circular con vehículo automotor en ciclovía o estacionarse interrumpiendo flujo', 'ciclista', 3, 5),
    ('Art. 382 Frac. II', 'Prestar un servicio distinto al autorizado', 'concesionario_operador', 20, 25),
    ('Art. 382 Frac. IV', 'Permitir conducir a persona no señalada en la hoja de despacho de la ruta', 'concesionario_operador', 30, 40),
    ('Art. 382 Frac. V', 'Cobrar tarifa diferente a la autorizada', 'concesionario_operador', 15, 18),
    ('Art. 382 Frac. VIII', 'No contar el operador con licencia o tarjetón vigente', 'concesionario_operador', 8, 10),
    ('Art. 382 Frac. X', 'Prestar el servicio con vehículos no autorizados por la Dirección', 'concesionario_operador', 40, 50),
    ('Art. 382 Frac. XI', 'Unidades sin aprobación de la revista físico-mecánica', 'concesionario_operador', 40, 50),
    ('Art. 382 Frac. XIII', 'No colaborar en estado de emergencia o contingencia', 'concesionario_operador', 50, 55),
    ('Art. 382 Frac. XIV', 'Prestar el servicio con colores o imagen distinta a la autorizada', 'concesionario_operador', 10, 15),
    ('Art. 382 Frac. XVI', 'Portar publicidad sin el permiso correspondiente', 'concesionario_operador', 20, 25),
    ('Art. 382 Frac. XVII', 'No permitir la inspección de vehículos, instalaciones y documentos', 'concesionario_operador', 30, 35),
    ('Art. 382 Frac. XVIII', 'No proporcionar los documentos requeridos a la autoridad', 'concesionario_operador', 29, 30),
    ('Art. 382 Frac. XIX', 'Colocar aditamentos que impidan la visibilidad o alteren imagen de agencia', 'concesionario_operador', 10, 15),
    ('Art. 382 Frac. XX', 'Negarse a colaborar en campañas y cursos de cultura vial', 'concesionario_operador', 14, 15),
    ('Art. 382 Frac. XXI', 'No mantener los vehículos en óptimas condiciones', 'concesionario_operador', 15, 20),
    ('Art. 382 Frac. XXII', 'Circular sin placas o sin el permiso respectivo vigente', 'concesionario_operador', 8, 10),
    ('Art. 365 / Art. 368', 'Entregar comprobante de pago en efectivo sin los datos obligatorios', 'concesionario_operador', 10, 15),
    ('Art. 389 Frac. I', 'Ejercer violencia verbal o moral contra algún usuario', 'concesionario_operador', 20, 25),
    ('Art. 389 Frac. II', 'Cometer actos discriminatorios contra el usuario', 'concesionario_operador', 25, 50),
    ('Art. 389 Frac. III', 'No cumplir los horarios de ruta o despachos programados', 'concesionario_operador', 40, 45),
    ('Art. 389 Frac. IV', 'Falta de aseo personal del operador', 'concesionario_operador', 5, 8),
    ('Art. 389 Frac. V', 'Fumar a bordo de la unidad', 'concesionario_operador', 30, 35),
    ('Art. 389 Frac. XII', 'Negarse a la práctica de exámenes médicos/toxicológicos', 'concesionario_operador', 14, 15),
    ('Art. 389 Frac. XIV', 'Ascenso o descenso de pasaje fuera de paradas autorizadas', 'concesionario_operador', 10, 15),
    ('Art. 389 Frac. XV', 'Permanecer en la parada sin estar realizando ascenso/descenso', 'concesionario_operador', 10, 15),
    ('Art. 389 Frac. XVI', 'No respetar tarifa preferencial o cobrar pasaje a menores de 6 años', 'concesionario_operador', 15, 18),
    ('Art. 389 Frac. XVIII', 'Bloquear el tránsito vehicular', 'concesionario_operador', 29, 30),
    ('Art. 389 Frac. XIX', 'No respetar las restricciones de velocidad', 'concesionario_operador', 30, 35),
    ('Art. 389 Frac. XXIV', 'No mantener puertas cerradas con el vehículo en movimiento', 'concesionario_operador', 8, 10),
    ('Art. 389 Frac. XXV', 'Estacionarse en la vía pública', 'concesionario_operador', 50, 55),
    ('Art. 389 Frac. XXVI', 'Llevar acompañantes que lo distraigan al conducir', 'concesionario_operador', 15, 20),
    ('Art. 389 Frac. XXVII', 'Circular fuera de carriles autorizados / no circular por la extrema derecha', 'concesionario_operador', 8, 10),
    ('Art. 389 Frac. XXVIII', 'Llevar equipo reproductor de música que exceda los 68 dB', 'concesionario_operador', 40, 50),
    ('Art. 389 Frac. XXIX', 'No obedecer señales de tránsito y/o luz roja de alto', 'concesionario_operador', 8, 10),
    ('Art. 389 Frac. XXX', 'Negarse a respetar medidas de sanidad/salud decretadas', 'concesionario_operador', 50, 55),
    ('Art. 390 Frac. I', 'Ingerir bebidas alcohólicas o consumir drogas durante el servicio', 'concesionario_operador', 50, 60),
    ('Art. 390 Frac. II', 'Conducir con audífonos, celular o dispositivos de distracción', 'concesionario_operador', 15, 20),
    ('Art. 390 Frac. III', 'Conducir o presentarse a trabajar con aliento alcohólico', 'concesionario_operador', 15, 20),
    ('Art. 390 Frac. IV', 'Conducir o presentarse a trabajar bajo efecto de drogas', 'concesionario_operador', 50, 60),
    ('Art. 390 Frac. V', 'Realizar actos deshonestos u obscenos a bordo/terminal', 'concesionario_operador', 29, 30),
    ('Art. 390 Frac. VI', 'Realizar competencias por pasaje en la vía pública', 'concesionario_operador', 15, 20),
    ('Art. 390 Frac. VII', 'Abastecer combustible con pasajeros a bordo', 'concesionario_operador', 15, 20),
    ('Art. 390 Frac. VIII', 'Circular con pasajeros en toldos, cofres, defensas, estribos o colgados', 'concesionario_operador', 50, 60),
    ('Art. 390 Frac. IX', 'Permitir conducir a persona que no cuente con cédula de operador', 'concesionario_operador', 25, 30),
    ('Art. 390 Frac. X', 'Transportar combustibles, materiales explosivos, corrosivos o pestilentes', 'concesionario_operador', 25, 30),
    ('Art. 390 Frac. XI', 'Alterar o modificar la imagen del vehículo sin autorización', 'concesionario_operador', 20, 25),
    ('Art. 390 Frac. XII', 'Cubrir ventanas dificultando la visibilidad al interior/exterior', 'concesionario_operador', 15, 20),
    ('Art. 390 Frac. XIII', 'Fijar placas con soldadura u otro material que impida el retiro', 'concesionario_operador', 15, 20),
    ('Art. 390 Frac. XIV', 'Sobornar/instigar al inspector de movilidad o faltarle al respeto', 'concesionario_operador', 15, 20),
    ('Art. 390 Frac. XVI', 'Ejercer violencia física contra algún usuario', 'concesionario_operador', 25, 50),
    ('Art. 390 Frac. XVI (bis)', 'Ejercer violencia verbal, moral o física contra otros usuarios de la vía', 'concesionario_operador', 25, 50),
    ('Art. 395 Frac. II / Art. 398', 'No proporcionar boleto al usuario que paga pasaje en efectivo', 'concesionario_operador', 4, 5),
    ('Art. 395 Frac. IV / Art. 398', 'No cobrar tarifa preferencial a beneficiarios legítimos', 'concesionario_operador', 20, 30),
    ('Art. 395 Frac. V / Art. 398', 'No permitir ascenso/descenso por puerta delantera a grupos vulnerables', 'concesionario_operador', 15, 20),
    ('Art. 395 Frac. VII / Art. 398', 'Ante descompostura, no sustituir unidad en 15 min ni devolver pasaje', 'concesionario_operador', 10, 20)
) AS v(fundamento_legal, descripcion, categoria_actor, monto_min_uma, monto_max_uma)
ON CONFLICT (fundamento_legal) DO NOTHING;

-- 2. Casillas de la boleta física (equivale a BoletaChecklistSeeder).
INSERT INTO public.boleta_checklist_item (categoria_boleta, etiqueta_casilla, catalogo_infraccion_id, created_at)
SELECT v.categoria_boleta, v.etiqueta_casilla,
       (SELECT c.id FROM public.catalogo_infracciones c WHERE c.fundamento_legal = v.fundamento_legal),
       CURRENT_TIMESTAMP
FROM (VALUES
    (1, 'DOCUMENTACIÓN', 'Falta de tarjeta de circulación', 'Art. 87 Frac. IV'),
    (2, 'DOCUMENTACIÓN', 'Falta de tarjeta de identificación del conductor (TTE Público)', 'Art. 382 Frac. VIII'),
    (3, 'DOCUMENTACIÓN', 'Falta de licencia', 'Art. 87 Frac. III'),
    (4, 'DOCUMENTACIÓN', 'Permiso para conducir', 'Art. 87 Frac. III'),
    (5, 'DOCUMENTACIÓN', 'Licencia vencida', 'Art. 87 Frac. III'),
    (6, 'PLACAS', 'Falta de placa(s) en vehículo(s)', 'Art. 87 Frac. I a)'),
    (7, 'PLACAS', 'Usar placas vencidas', NULL),
    (8, 'PLACAS', 'Falta de una', 'Art. 87 Frac. I a)'),
    (9, 'PLACAS', 'Falta de ambas', 'Art. 87 Frac. I a)'),
    (10, 'PARTES INTEGRALES DEL VEHÍCULO', 'No usar cinturón de seguridad', 'Art. 39 Frac. V'),
    (11, 'PARTES INTEGRALES DEL VEHÍCULO', 'Falta de cinturón de seguridad', 'Art. 39 Frac. V'),
    (12, 'PARTES INTEGRALES DEL VEHÍCULO', 'Falta de parabrisas', NULL),
    (13, 'PARTES INTEGRALES DEL VEHÍCULO', 'Agrietado (Parabrisas)', NULL),
    (14, 'PARTES INTEGRALES DEL VEHÍCULO', 'Falta de medallón', NULL),
    (15, 'PARTES INTEGRALES DEL VEHÍCULO', 'Agrietado (Medallón)', NULL),
    (16, 'PARTES INTEGRALES DEL VEHÍCULO', 'Falta de faro izquierdo', 'Art. 39 Frac. VII'),
    (17, 'PARTES INTEGRALES DEL VEHÍCULO', 'Falta de faro derecho', 'Art. 39 Frac. VII'),
    (18, 'PARTES INTEGRALES DEL VEHÍCULO', 'Falta de ambos faros', 'Art. 39 Frac. VII'),
    (19, 'PARTES INTEGRALES DEL VEHÍCULO', 'Falta de direccional izquierda', 'Art. 39 Frac. XVII'),
    (20, 'PARTES INTEGRALES DEL VEHÍCULO', 'Falta de direccional derecha', 'Art. 39 Frac. XVII'),
    (21, 'PARTES INTEGRALES DEL VEHÍCULO', 'En motocicleta', 'Art. 93 Frac. VI'),
    (22, 'PARTES INTEGRALES DEL VEHÍCULO', 'En motoneta', 'Art. 93 Frac. VI'),
    (23, 'SEÑALAMIENTOS', 'Circular por lugar prohibido', 'Art. 89 Frac. VII'),
    (24, 'SEÑALAMIENTOS', 'No obedecer señalamientos de alto', 'Art. 39 Frac. I'),
    (25, 'SEÑALAMIENTOS', 'No obedecer señal de prohibido el paso a vehículos pesados', NULL),
    (26, 'SEÑALAMIENTOS', 'Señales del oficial de TTO.', 'Art. 39 Frac. X'),
    (27, 'SEÑALAMIENTOS', 'Semáforo en luz roja', 'Art. 39 Frac. IX'),
    (28, 'SEÑALAMIENTOS', 'Dañar, destruir u obstruir la visibilidad de señales de tránsito', NULL),
    (29, 'SEÑALAMIENTOS', 'Exceso de velocidad', 'Art. 39 Frac. III'),
    (30, 'ESTACIONAMIENTOS', 'Lugar prohibido', NULL),
    (31, 'ESTACIONAMIENTOS', 'Doble fila', 'Art. 89 Frac. XVIII'),
    (32, 'ESTACIONAMIENTOS', 'Zona donde la guarnición esté pintada de amarillo', NULL),
    (33, 'ESTACIONAMIENTOS', 'Sentido contrario', NULL),
    (34, 'ESTACIONAMIENTOS', 'Fuera del límite', NULL),
    (35, 'ESTACIONAMIENTOS', 'Frente a cochera', NULL),
    (36, 'ESTACIONAMIENTOS', 'Salida de veh. emergencias', NULL),
    (37, 'ESTACIONAMIENTOS', 'Sobre aceras', 'Art. 89 Frac. VII'),
    (38, 'ESTACIONAMIENTOS', 'Sup. de acotamiento', 'Art. 89 Frac. VIII'),
    (39, 'ESTACIONAMIENTOS', 'En parada de autobuses', 'Art. 89 Frac. XIX'),
    (40, 'ESTACIONAMIENTOS', 'En accesos para discapacitados', 'Art. 93 Frac. V'),
    (41, 'ESTACIONAMIENTOS', 'En zonas de carga y descarga', NULL),
    (42, 'ESTACIONAMIENTOS', 'En vía pública por más de 5 días naturales', NULL),
    (43, 'ESTACIONAMIENTOS', 'Ascenso y descenso de pasaje en lugar no permitido', 'Art. 389 Frac. XIV'),
    (44, 'MANEJO', 'No utilizar casco protector', 'Art. 92 Frac. V'),
    (45, 'MANEJO', 'Placas ocultas', 'Art. 87 Frac. I b)'),
    (46, 'MANEJO', 'Abastecer combustible con pasaje a bordo en trans. público', 'Art. 390 Frac. VII'),
    (47, 'MANEJO', 'Falta de licencia', 'Art. 87 Frac. III'),
    (48, 'MANEJO', 'Permiso para conducir', 'Art. 87 Frac. III'),
    (49, 'MANEJO', 'Falta de permiso carga y descarga', NULL),
    (50, 'ESTADO FÍSICO DEL CONDUCTOR', 'Aliento alcohólico', 'Art. 390 Frac. III'),
    (51, 'ESTADO FÍSICO DEL CONDUCTOR', 'Manejar en visible y notorio estado de ebriedad', 'Art. 89 Frac. I'),
    (52, 'ESTADO FÍSICO DEL CONDUCTOR', 'Guiar drogado', 'Art. 390 Frac. IV'),
    (53, 'PREFERENCIA', 'No conceder el paso a peatones', 'Art. 40 Frac. I'),
    (54, 'PREFERENCIA', 'No darle a vehículos que circulen en glorieta', 'Art. 40 Frac. VI'),
    (55, 'PREFERENCIA', 'No darle a vehículos de emergencia', 'Art. 39 Frac. XXII'),
    (56, 'REBASAR', 'En zona prohibida', NULL),
    (57, 'REBASAR', 'Por el lado derecho', 'Art. 39 Frac. XX'),
    (58, 'REBASAR', 'Estando a punto de causar accidentes', NULL),
    (59, 'REBASAR', 'Vehículos por el acotamiento', 'Art. 89 Frac. VIII'),
    (60, 'REBASAR', 'Crucero', NULL),
    (61, 'ACCIDENTES', 'Por causar daños a bienes del municipio', NULL),
    (62, 'ACCIDENTES', 'Provocar accidente causando herido', NULL),
    (63, 'ACCIDENTES', 'Muerto', NULL),
    (64, 'ACCIDENTES', 'Abandono de vehículo ocasionando accidente', 'Art. 87 Frac. XVIII'),
    (65, 'ACCIDENTES', 'Provocando o causando daños materiales', NULL),
    (66, 'ACCIDENTES', 'Abandono de vehículo', 'Art. 87 Frac. XVIII'),
    (67, 'ACCIDENTES', 'Víctimas', NULL),
    (68, 'FALTAS A LA AUTORIDAD', 'Agresiones físicas o verbales a los usuarios', NULL),
    (69, 'FALTAS A LA AUTORIDAD', 'Agresiones físicas o verbales a oficiales de TTO.', 'Art. 390 Frac. XIV'),
    (70, 'CARGA', 'Cargar o descargar en horas no permitidas', NULL),
    (71, 'CARGA', 'Exceso de dimensiones en la carga', NULL),
    (72, 'REMOLCAR', 'Remolcar vehículos sin aditamentos requeridos', NULL),
    (73, 'CIRCULACIÓN', 'Circular en sentido contrario', 'Art. 39 Frac. IV'),
    (74, 'CIRCULACIÓN', 'Vehículos pesados en zona restringida', NULL),
    (75, 'CIRCULACIÓN', 'Efectuar vuelta en U', NULL),
    (76, 'CIRCULACIÓN', 'Obstruir la circulación a vehículos en vía pública', 'Art. 389 Frac. XVIII'),
    (77, 'MEDIO AMBIENTE', 'Utilizar vehículo con exceso de volumen', 'Art. 93 Frac. X'),
    (78, 'MEDIO AMBIENTE', 'Emisión excesiva de humo en vehículo', NULL),
    (79, 'MEDIO AMBIENTE', 'Falta de verificación vehicular', 'Art. 87 Frac. XXIV'),
    (80, 'MEDIO AMBIENTE', 'Hacer mal uso del claxon', NULL)
) AS v(orden, categoria_boleta, etiqueta_casilla, fundamento_legal)
WHERE NOT EXISTS (
    SELECT 1 FROM public.boleta_checklist_item b
    WHERE b.categoria_boleta = v.categoria_boleta AND b.etiqueta_casilla = v.etiqueta_casilla
)
ORDER BY v.orden;

-- 3. Catálogo de la app (equivale a CatalogoInfraccionesAppSeeder).
CREATE TEMP TABLE _ejemplos_0005 (fundamento_legal text, descripcion text, categoria text, monto_min_uma numeric, monto_max_uma numeric) ON COMMIT DROP;
INSERT INTO _ejemplos_0005 VALUES
    ('Art. 39 Frac. IX', 'No obedecer la señal de alto cuando la luz del semáforo esté en rojo', 'MANEJO Y VIALIDAD', 15, 25),
    ('Art. 82', 'Conducir utilizando teléfonos celulares o dispositivos de comunicación móvil en movimiento', 'MANEJO Y VIALIDAD', 10, 20),
    ('Art. 39 Frac. III', 'No respetar los límites de velocidad establecidos en la zona urbana o escolar', 'VELOCIDAD', 30, 50),
    ('Art. 95 / Art. 87 Frac. II', 'Estacionarse en lugar prohibido, doble fila o rampas reservadas para personas con discapacidad', 'ESTACIONAMIENTO', 10, 20),
    ('Art. 102', 'No portar casco protector certificado el conductor y/o acompañante de motocicleta', 'MOTOCICLETAS', 5, 10),
    ('Art. 87 Frac. I a)', 'Circular sin placas de circulación, con placas alteradas, ocultas o no visibles', 'PLACAS Y REGISTRO', 10, 15),
    ('Art. 39 Frac. V', 'No utilizar el cinturón de seguridad el conductor y/o acompañantes', 'SEGURIDAD PASIVA', 5, 10),
    ('Art. 39 Frac. IV', 'Circular en sentido opuesto al que indica la vialidad o señalamiento oficial', 'MANEJO Y VIALIDAD', 3, 5),
    ('Art. 87 Frac. III', 'Falta de licencia de conducir para el tipo de vehículo o presentar licencia vencida', 'DOCUMENTACIÓN', 5, 10),
    ('Art. 87 Frac. IV', 'Falta de tarjeta de circulación vigente del vehículo', 'DOCUMENTACIÓN', 5, 10),
    ('Art. 39 Frac. XI', 'No respetar el derecho de preferencia de paso a peatones y/o ciclistas en cruces marcados', 'PROTECCIÓN PEATONAL', 5, 10),
    ('Art. 115', 'Conducir vehículos bajo el influjo de bebidas alcohólicas, drogas o sustancias tóxicas', 'SEGURIDAD PÚBLICA', 50, 100),
    ('Art. 39 Frac. VII', 'Circular sin faros delanteros o luces posteriores en horario nocturno o condiciones adversas', 'EQUIPAMIENTO', 5, 10),
    ('Art. 39 Frac. XXV', 'Circular con puertas abiertas o permitir que pasajeros viajen en estribos del vehículo', 'TRANSPORTE Y PASAJE', 15, 30);

CREATE TEMP TABLE _oficial_app ON COMMIT DROP AS
SELECT c.id, c.fundamento_legal, c.descripcion, c.monto_min_uma, c.monto_max_uma,
       CASE c.categoria_actor
            WHEN 'conductor_general'      THEN 'CONDUCTORES'
            WHEN 'motociclista'           THEN 'MOTOCICLISTAS'
            WHEN 'ciclista'               THEN 'CICLISTAS'
            WHEN 'concesionario_operador' THEN 'CONCESIONARIOS Y OPERADORES'
            ELSE 'GENERAL'
       END AS categoria
FROM public.catalogo_infracciones c;

-- 3a. Ejemplos sin editar que sí están en el reglamento: texto y montos oficiales.
UPDATE public.catalogo_infracciones_app a
SET descripcion = o.descripcion, categoria = o.categoria,
    monto_min_uma = o.monto_min_uma, monto_max_uma = o.monto_max_uma,
    orden = 100 + o.id, updated_at = CURRENT_TIMESTAMP
FROM _oficial_app o, _ejemplos_0005 e
WHERE a.fundamento_legal = o.fundamento_legal
  AND e.fundamento_legal = a.fundamento_legal
  AND a.descripcion = e.descripcion AND a.categoria = e.categoria
  AND a.monto_min_uma = e.monto_min_uma AND a.monto_max_uma = e.monto_max_uma;

-- 3b. Faltas oficiales que todavía no están en la app.
INSERT INTO public.catalogo_infracciones_app (fundamento_legal, descripcion, categoria, monto_min_uma, monto_max_uma, activo, orden, created_at, updated_at)
SELECT o.fundamento_legal, o.descripcion, o.categoria, o.monto_min_uma, o.monto_max_uma, 1, 100 + o.id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM _oficial_app o
WHERE NOT EXISTS (SELECT 1 FROM public.catalogo_infracciones_app a WHERE a.fundamento_legal = o.fundamento_legal)
ORDER BY o.id;

-- 3c. Ejemplos sin editar cuyo artículo no existe en el reglamento: se desactivan.
UPDATE public.catalogo_infracciones_app a
SET activo = 0, updated_at = CURRENT_TIMESTAMP
FROM _ejemplos_0005 e
WHERE e.fundamento_legal = a.fundamento_legal
  AND a.activo = 1
  AND a.descripcion = e.descripcion AND a.categoria = e.categoria
  AND a.monto_min_uma = e.monto_min_uma AND a.monto_max_uma = e.monto_max_uma
  AND NOT EXISTS (SELECT 1 FROM public.catalogo_infracciones c WHERE c.fundamento_legal = a.fundamento_legal);

-- 4. Dejar registrada la migración CI4 equivalente, para que `spark migrate`
--    no intente volver a aplicarla.
INSERT INTO public.migrations (version, class, "group", namespace, time, batch)
SELECT '2026-11-03-000034_CatalogoAppConInfraccionesOficiales', 'CatalogoAppConInfraccionesOficiales', 'App', '',
       EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::bigint, 2
WHERE NOT EXISTS (
    SELECT 1 FROM public.migrations m
    WHERE m.version = '2026-11-03-000034_CatalogoAppConInfraccionesOficiales'
);

COMMIT;

-- 5. Verificación: 201 oficiales, 80 casillas y al menos 201 faltas activas en la app.
SELECT (SELECT count(*) FROM public.catalogo_infracciones)                  AS oficiales,
       (SELECT count(*) FROM public.boleta_checklist_item)                  AS casillas_boleta,
       (SELECT count(*) FROM public.catalogo_infracciones_app WHERE activo = 1) AS app_activas,
       (SELECT count(*) FROM public.catalogo_infracciones_app WHERE activo = 0) AS app_inactivas;
