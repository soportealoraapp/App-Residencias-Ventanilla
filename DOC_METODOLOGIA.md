# Metodología de Desarrollo y Coordinación del Proyecto
## Sistema Integral de Ventanilla Única de Movilidad y Transporte de Uriangato

**Documento Académico y Técnico de Residencia Profesional**
**Carrera:** Ingeniería en Sistemas Computacionales / Ingeniería en Software
**Institución Receptora:** Dirección de Movilidad y Transporte — H. Ayuntamiento de Uriangato, Gto.
**Autor:** Residente Profesional
**Versión del Documento:** 1.0
**Fecha:** Octubre 2026

> **Alcance de este documento.** Aquí vive el proceso: cómo se delimitó el trabajo, cómo se coordinó el equipo y en qué orden se construyó el sistema. El detalle funcional y técnico del Portal Ciudadano y del Panel Administrativo Web está en [`DOC_PORTAL_PANEL.md`](./DOC_PORTAL_PANEL.md), y el del backend completo en [`DOCUMENTACION_BACKEND.md`](./DOCUMENTACION_BACKEND.md).
>
> **Convención de referencias.** Las referencias del tipo `§x.y` remiten a `DOC_PORTAL_PANEL.md`; las secciones de este documento se citan por nombre.

---

## Índice General

1. [Delimitación del Alcance del Proyecto de Residencia](#1-delimitación-del-alcance-del-proyecto-de-residencia)
   * 1.1. Alcance Técnico Asignado
   * 1.2. Exclusiones del Documento
2. [Metodología de Trabajo: Desarrollo Iterativo-Incremental y Coordinación Síncrona](#2-metodología-de-trabajo-desarrollo-iterativo-incremental-y-coordinación-síncrona)
3. [Recorrido Procesal del Desarrollo (Fases)](#3-recorrido-procesal-del-desarrollo-fases)
   * 3.0. Mapa de Correspondencia con las Fases del Proyecto
   * 3.1. Fase de Análisis y Levantamiento de Requerimientos en Campo
   * 3.2. Fase de Diseño y Validación de Interfaces de Usuario (UX/UI)
   * 3.3. Fase de Diseño e Implementación de la Base de Datos
   * 3.4. Fase de Programación del Backend y Reglas de Negocio
   * 3.5. Fase de Frontend y Despliegue en Entorno de Producción
   * 3.6. Correspondencia con las Figuras del Documento Final
   * 3.7. Referencias Tecnológicas Utilizadas
   * 3.8. Fase de Cobros (COB-01 a COB-12): Del Folio de Campo al Recibo de Pago
4. [Conclusiones Técnicas](#4-conclusiones-técnicas)
5. [Cómo Usar Este Documento en el Informe Final](#5-cómo-usar-este-documento-en-el-informe-final)

---

## 1. Delimitación del Alcance del Proyecto de Residencia

Este documento narra el diseño ingenieril, la construcción técnica, el aseguramiento de la calidad y el despliegue del subsistema **Portal Ciudadano y Panel Administrativo Web**, desarrollado en el marco del proyecto integral de modernización digital para la Dirección de Movilidad y Transporte del Municipio de Uriangato, Guanajuato.

### 1.1. Alcance Técnico Asignado

* **Portal Ciudadano:** desarrollo de la interfaz web para el **autorregistro** autogestionado de ciudadanos, captura asistida de solicitudes para 7 trámites de transporte y vialidad, cálculo automatizado de derechos municipales según la Ley de Ingresos, carga validada de expedientes probatorios en formato digital, pasarela mock de pago en línea y consulta de estatus con trazabilidad histórica.
* **Panel Administrativo Web:** construcción del panel de control de uso exclusivo para funcionarios públicos (roles `administrador` y `operador_ventanilla`), con tableros analíticos de control (KPIs), visualizador centralizado de expedientes con dictaminación técnica, módulo de citas para verificación física, **radicación presencial de solicitudes desde ventanilla** (atención del ciudadano que no usa el portal web), bitácora de auditoría inmutable, **permisología configurable por sección para el operador**, gestión del padrón de cuentas y módulos de administración para los catálogos del municipio (tarifas, concesiones, convocatorias y formatos oficiales).
* **Transversal:** diseño responsivo *mobile-first* para operarse desde teléfono, tableta o escritorio en ambas interfaces, y despliegue sobre infraestructura serverless (Vercel PHP) con doble estrategia de almacenamiento documental (nube Supabase y depósito local firmado).

### 1.2. Exclusiones del Documento

Se excluye del análisis profundo de este documento la implementación interna del cliente móvil nativo para agentes de tránsito (desarrollado en Expo / React Native), abordándose únicamente en lo relativo a los puntos de contacto e interoperabilidad con la base de datos relacional y los endpoints de configuración y registro expuestos por el backend web.

---

## 2. Metodología de Trabajo: Desarrollo Iterativo-Incremental y Coordinación Síncrona

El subsistema web no se construyó como un bloque único que se prueba al final, sino con una **metodología iterativa e incremental**: se entregaron primero los troncos comunes (autenticación, roles, expediente maestro, listas) y sobre ellos se fueron incorporando iteraciones —un trámite, luego dos, luego el canal presencial, luego la permisología, luego la adaptación móvil—, de modo que cada iteración se **construyó, probó y validó por separado** antes de depender de la siguiente. Esta secuencia fue posible porque el esquema de datos se definió de forma declarativa (Anexo A de `DOC_PORTAL_PANEL.md`) y porque cada iteración quedó protegida por pruebas automatizadas (§12), de modo que si un cambio rompía una regla ya validada, la suite lo detectaba el mismo día.

Tres consecuencias prácticas de ese orden de trabajo:

1. **El andamiaje se construyó antes que la funcionalidad voluminosa.** Enrutamiento, filtros, sesiones en base de datos y la fachada de almacenamiento existían antes del segundo trámite, así que agregar los seis restantes fue rellenar un patrón ya probado y no reinventarlo siete veces.
2. **Nada se declaró terminado sin evidencia.** Cada iteración cerró con una verificación reproducible (una corrida de PHPUnit, una prueba de humo contra la URL desplegada o una medición en el navegador), no con una revisión visual.
3. **El desecho fue barato.** Cuando una decisión resultó cara —por ejemplo el modelo de datos duplicado por trámite que se reemplazó por el catálogo declarativo (§5.5.2)— solo se perdió el trabajo de una iteración, no el del proyecto.

**Gestión diaria del equipo.** La residencia se repartió en tres responsables de módulo: **backend**, **panel web** y **aplicación móvil**. Inicialmente se evaluaron herramientas tradicionales de gestión (tableros virtuales tipo Kanban, seguimiento de tarjetas, flujos de ticket), pero se determinó que la dinámica real del equipo —tres personas, un mismo repositorio, bloqueos técnicos que se resolvían en minutos y no en días— pedía coordinación directa. La división de módulos, la asignación de responsabilidades y el desbloqueo técnico se llevaron mediante **comunicación síncrona en un grupo de mensajería instantánea (WhatsApp)**, lo que mantuvo el avance de las tres ramas alineado: cuando el backend publicaba un endpoint nuevo, el panel y la app lo consumían en la misma jornada.

Esta elección de gestión no sustituyó la disciplina técnica, sino que se apoyó en ella, y es la razón por la que el repositorio conserva artefactos que hacen visible el estado del trabajo sin necesidad de un tablero:

| Artefacto en el repositorio | Qué sustituye del tablero tradicional |
|---|---|
| Historial de `git` con mensajes convencionales (`feat:`, `fix:`, `docs:`) | Registro de tareas terminadas y su autor |
| Suite de PHPUnit en verde (`vendor/bin/phpunit`) | Criterio de "listo" (*definition of done*) verificable |
| `FeatureFlags` y banderas de función (§1.3) | Tablero de "en desarrollo / listo para activar" |
| Los cuatro documentos de este repositorio (§5) | Documentación viva de cada módulo en lugar de tickets sueltos |
| Bitácora de `auditoria` en base de datos (§9.4) | Traza de decisiones tomadas sobre datos reales, no conversaciones |

> **Nota para el documento final:** la coordinación por mensajería es válida y defendible en una residencia de tres personas, pero conviene registrarla con su límite: no deja artefactos buscables por sí sola. El compromiso metodológico que se documentó aquí es que **todo acuerdo de conversación se materializó en un commit, una prueba o una línea de documentación**, y esa es la parte que se puede auditar años después.

---

## 3. Recorrido Procesal del Desarrollo (Fases)

El resto de la documentación de este repositorio está organizada **por tema técnico** (pantallas, rutas, cobros, frontend, pruebas). Este capítulo está organizado **por fase de trabajo**, que es como sucedió realmente y como suele pedirse que se narre en un informe de residencia. Su función es servir de matriz de despliegue: cada apartado indica qué ocurrió, con qué evidencia en el repositorio puede demostrarse y a qué sección temática remitir para el detalle.

### 3.0. Mapa de Correspondencia con las Fases del Proyecto

| Fase | Qué se hizo | Evidencia verificable en el repositorio | Detalle temático |
|---|---|---|---|
| **1. Análisis y levantamiento en campo** | Reuniones en Presidencia (área de TI) y entrevista en Tránsito y Seguridad | Directiva de framework (§3.1.1), catálogos y módulos derivados del proceso de ventanilla (§3.1.2) | §§1, 4, 5 |
| **2. Diseño y validación de interfaces** | Portal ciudadano y panel; iteración sobre los 7 trámites; *mobile-first* | `app/Views/` (76 archivos de vista), 4 *layouts*, `public/css/custom.css` (2 140 líneas), auditoría de desbordes (§11.9) | §11 |
| **3. Diseño e implementación de la base de datos** | Modelado relacional, su construcción automatizada y la corrección de la deriva con la base instalada | 33 migraciones PHP en `app/Database/Migrations/`, 19 migraciones SQL en `supabase/migrations/`, verificador `scripts/verificar_esquema_bd.php` | Anexo A, §3.3.4 |
| **4. Programación del backend y reglas de negocio** | MVC, gestión documental, seguridad por roles, tarifario, máquina de estados, cobros y calificación judicial | 30 controladores, 24 modelos, 22 archivos en `app/Libraries/` (20 servicios + 2 excepciones de dominio), 5 filtros propios | §§1, 9, 10, Anexo B |
| **5. Frontend y despliegue en producción** | Vistas conectadas al controlador, pruebas end-to-end, publicación serverless | `vercel.json`, `scripts/vercel-install.sh`, tabla `ci_sessions`, 43 clases de prueba con 410 métodos | §11, §12, Anexo C |

---

### 3.1. Fase de Análisis y Levantamiento de Requerimientos en Campo

El primer paso no fue escribir código sino entender las necesidades exactas del H. Ayuntamiento de Uriangato. El levantamiento fue presencial e interactivo, y se dividió en dos vertientes que aportaron requerimientos distintos pero complementarios.

#### 3.1.1. Reuniones en Presidencia Municipal (Área de TI)

Se acudió en múltiples ocasiones a las instalaciones de la Presidencia para entrevistarse con el encargado del departamento de Tecnologías de la Información y Telecomunicaciones. De esas sesiones salieron dos determinantes que condicionaron todo el trabajo posterior:

1. **Directriz técnica inamovible: CodeIgniter 4.** La decisión no fue de preferencia personal ni de moda, sino de **compatibilidad futura**: es el mismo framework que utiliza la Ventanilla Digital oficial del municipio, así que el código de este proyecto podrá ser leído, mantenido y absorbido por el personal que ya opera esa plataforma. Consecuencia práctica en el repositorio: se aceptó la línea `codeigniter4/framework: ^4.5` y se trabajó sobre la versión instalada (4.7.4), sin introducir un segundo framework ni una capa de interfaz gráfica del lado del servidor (ningún Laravel, Symfony completo, Vue o React en el portal).
2. **No existía API ni backend preexistente que consumir.** La dependencia no contaba con un servicio institucional. El equipo asumió entonces **toda la infraestructura desde cero**, lo que convierte el proyecto en un desarrollo *greenfield* y no en una integración. El inventario de lo que hubo que construir desde el vacío es, en sí mismo, un resultado medible:

| Pieza construida desde cero | Dónde vive | Nota |
|---|---|---|
| Esquema de base de datos | 33 migraciones PHP + 19 SQL | Anexo A, §3.3 |
| Autenticación (login, registro, cierre, recuperación) | `AuthController`, `app/Views/auth/`, `AuthFilter` | §3, §10 |
| Gestión de roles (ciudadano, operador de ventanilla, juez calificador, administrador) | `roles`, `user_roles`, `RoleModel`, `RoleFilter` | §10.1 |
| Autorización de grano fino por sección | `PermisoOperadorFilter`, `PermisosOperador`, `parametros_app` | §9.5 |
| Lógica de los 7 trámites | 7 controladores `Tramite*` + `TramitesController` | §4.7 |
| Máquina de estados del expediente | `EstadoSolicitudService`, `historial_estatus` | §9.1 |
| Tarifario y cálculo de derechos | `TarifarioService`, `tarifas` | §4.7 |
| Gestión documental y almacenamiento | `DocumentoUploader`, `SupabaseStorage`, `ArchivosLocalesController` | Anexo B |
| API REST para clientes externos | `Api\AgentesTransitoApiController`, `Api\InfraccionesApiController` | §1.6 |
| Sesiones persistentes en base de datos | `ci_sessions`, `Config/Session.php` | Anexo C |
| Módulo de cobros (trámites y multas) | `CobroService`, `cobros`, `cobro_movimientos` | §7 |
| Calificación judicial de boletas | `CalificarBoletaService`, `boleta_calificaciones`, `boleta_calificacion_detalles` | §6.3, §3.8 |
| Hoja de ayuda para el pago | `HojaPagoService`, `DocumentosPagoController` | §7.10 |

> **Cómo redactar esto en la tesis:** la directriz de TI es un **requerimiento de diseño impuesto por el cliente**, no una elección del residente. Conviene enunciarla así, porque explica decisiones que de otro modo parecerían arbitrarias (ausencia de un SPA en el portal, uso de vistas PHP, Bootstrap en lugar de una librería de componentes moderna).

#### 3.1.2. Entrevista Operativa en Tránsito y Seguridad

Se realizó una visita de campo a las oficinas de Tránsito y Seguridad de Uriangato y se entrevistó al personal encargado. El motivo central era el proceso de **levantamiento de infracciones viales**, requerimiento de la aplicación móvil que desarrollaba en paralelo otro miembro del equipo; pero la visita resultó vital también para el módulo web, por tres razones concretas y cada una con su huella en el código:

1. **Comprender el flujo operativo de la dependencia permitió dimensionar el panel.** El Panel Administrativo no es un CRUD de solicitudes genéricas: tiene que **recibir, visualizar y administrar boletas de campo generadas por los agentes**. Eso se tradujo en un módulo específico de monitoreo (`Admin\AppTransitoController`, `Admin\DesgloseController`), en las tablas `boletas_infracciones`, `boleta_checklist_item`, `catalogo_infracciones_app`, `agentes_transito` y `agentes_transito_tokens`, y en el visor `/admin/app-transito/boletas`.
2. **La bitácora física de control de folios y documentos retenidos reveló el modelo de datos real.** El municipio ya llevaba control en papel: folio consecutivo, placas retenidas, licencia retenida, vehículo al corralón, inventario de grúa. Ese mismo vocabulario se conservó en el sistema para que el funcionario reconozca su propio proceso: la app captura **garantías retenidas** y la **tipificación con fundamento legal y rangos de UMA**, y el expediente conserva el **folio único** (`FolioGenerator`).
3. **El trabajo en vía pública ocurre sin cobertura.** La observación operativa de que el agente levanta la boleta en la calle, sin señal, es el origen del requisito *offline-first* de la app y de los endpoints de sincronización y evidencia (`/api/infracciones/sincronizar`, `/api/infracciones/evidencias`) que el backend web expone.

> **Dato metodológico aprovechable:** la entrevista de campo no generó un documento de requerimientos formal en el sentido estricto; generó **restricciones verificables** (framework obligatorio, inexistencia de backend, vocabulario operativo del papel, trabajo sin cobertura). En la tesis conviene presentarlas así, porque una restricción es auditable y un "el usuario quiere algo moderno" no lo es.

---

### 3.2. Fase de Diseño y Validación de Interfaces de Usuario (UX/UI)

Con los requerimientos técnicos y operativos claros, se diseñó la interfaz gráfica con dos objetivos diferenciados: un **portal ciudadano fácil de usar para cualquier persona**, y un **panel administrativo que diera herramientas de control real a los funcionarios** (no solo consultar, sino dictaminar).

**Componentes principales** que se diseñaron y construyeron: formularios de trámite con validación en el servidor y mensajes en español, tablas de expedientes con búsqueda y filtros por estatus, botones de acción por transición válida de la máquina de estados, tarjetas de métricas enlazadas a su detalle, gaveta lateral de navegación, visor de documentos y asistente de radicación en dos pasos.

**Iteración sobre los 7 trámites operativos.** La interfaz sufrió varias correcciones para ajustarse a la realidad normativa de cada trámite: UR-01 exige distinguir persona física de moral y validar RFC; UR-02 es gratuito pero requiere cita de inspección física en patio; UR-03 genera oficio para trámite estatal (SATEG); UR-04 cotiza por días de vigencia; UR-05 cobra por horas/días y categoría vial y exige plan de vías alterna y anuencia vecinal; UR-06 está regulada y se libera por bandera de función; UR-07 calcula por toneladas y periodo. Cada una de esas diferencias se resolvió en el mismo tronco (EAV + catálogo declarativo + tarifario), que es la razón por la que la iteración fue barata (§1.3, §4.7, §5.5.2).

**Maquetación y responsividad.** Para el código final se utilizó **Bootstrap 5.3.2** (CDN de jsDelivr) con **Bootstrap Icons 1.11.1** y una hoja propia `public/css/custom.css`, declarados en los tres *layouts* (`layouts/portal.php`, `layouts/admin.php`, `auth/layout_auth.php`). La responsividad no se dejó al comportamiento por defecto del marco: se definieron *design tokens* (variables CSS para tipografía, espaciado y el objetivo táctil de 44 px), escala tipográfica fluida con `clamp()`, campos de formulario a 16 px para evitar el zoom automático de iOS, navegación inferior fija en el portal y gaveta deslizante en el panel (§11.2–11.5).

**Validación de las interfaces.** La fase cerró con tres mecanismos, en orden de costo creciente:

1. **Recorrido manual en el navegador** de cada pantalla en escritorio y en móvil.
2. **Auditoría instrumental de desbordes horizontales** (§11.9): 55 páginas renderizadas con datos reales, medidas a 360/320/290 px con un detector clasificado y una página de control. Esta auditoría detectó y corrigió la clase de defecto que la inspección visual no dimensiona: texto recortado en silencio por `overflow-x: hidden`.
3. **Barrido de renderizado de vistas** (§12.3): prueba automatizada que aserta sobre el HTML producido, la cual destapó que el título de todas las páginas administrativas se pintaba vacío.

> **Lección de diseño que conviene citar:** las dos últimas verificaciones encontraron fallos **después** de que las pantallas "se veían bien". La validación de interfaz en este proyecto no fue una etapa previa que termina al entregar el diseño, sino un criterio de cierre que se aplicó sobre el producto ya funcionando.

---

### 3.3. Fase de Diseño e Implementación de la Base de Datos

#### 3.3.1. Modelado y alojamiento

Para modelar y alojar la base de datos se utilizó **Supabase**, plataforma que proveyó un entorno robusto de **PostgreSQL** en la nube. La elección aporta tres propiedades que interesaban al proyecto: motor relacional maduro (integridad referencial real, transacciones ACID), almacenamiento de objetos integrado (buckets privados para expedientes y evidencias) y acceso por dos vías —conexión directa con `pdo_pgsql`/PgBouncer para el backend y API REST para el cliente móvil— sin duplicar la fuente de verdad.

#### 3.3.2. Construcción del esquema mediante migraciones, no de forma manual

La creación de las tablas (usuarios, solicitudes, documentos, catálogos) **no se hizo a mano** en el panel de la plataforma, sino a través del sistema de **Migraciones de CodeIgniter 4**. Se escribieron clases en PHP que definen cada tabla, sus columnas, índices y llaves primarias y foráneas:

| Aspecto | Valor real del repositorio |
|---|---|
| Migraciones PHP (esquema) | 33 archivos en `app/Database/Migrations/`, con prefijo de fecha y secuencia (`2026-01-01-000005_CreateSolicitudesTable.php`) |
| Migraciones SQL complementarias (Supabase) | 19 archivos en `supabase/migrations/`, entre ellas `0003_ci_sessions.sql`, `0008_completar_esquema_pendiente.sql` y `0009_concesion_id_sin_cascade.sql` |
| Tablas del modelo relacional | Definidas en el Anexo A de `DOC_PORTAL_PANEL.md`; la base instalada conserva además `migrations` —el historial que escribe CI4— y `ciudadanos` / `administradores`, herencia del levantamiento inicial |
| Definición | `Forge` de CodeIgniter: `$this->forge->addField([...])`, `addKey('id', true)`, `addForeignKey(...)` |

**Extracto real de una migración del proyecto** (`app/Database/Migrations/2026-01-01-000005_CreateSolicitudesTable.php`, con omisiones marcadas):

```php
<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSolicitudesTable extends Migration
{
    public function up()
    {
        // Id generada según el conductor: PostgreSQL usa BIGSERIAL,
        // SQLite (harness de pruebas) usa INTEGER.
        $idType = $this->db->DBDriver === 'SQLite3' ? 'INTEGER' : 'BIGSERIAL';

        $this->forge->addField([
            'id'                => ['type' => $idType, 'auto_increment' => true],
            'folio'             => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'tramite'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'ciudadano_id'      => ['type' => 'BIGINT',  'null' => false],
            'estatus'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'monto'             => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'fecha_solicitud'   => ['type' => 'TIMESTAMP', 'null' => false],
            'fecha_pago'        => ['type' => 'TIMESTAMP', 'null' => true],
            // ... fechas de vigencia ...
            'comentario_rechazo' => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at'        => ['type' => 'TIMESTAMP', 'null' => true],
        ]);

        $this->forge->addKey('id', true);                      // clave primaria
        $this->forge->addKey('folio', false, true);            // índice único
        $this->forge->addKey('ciudadano_id');
        $this->forge->addKey('tramite');
        $this->forge->addKey('estatus');
        $this->forge->addForeignKey('ciudadano_id', 'users', 'id', 'RESTRICT', 'RESTRICT');

        $this->forge->createTable('solicitudes', true);
    }

    public function down()
    {
        $this->forge->dropTable('solicitudes', true);
    }
}
```

Tres detalles de este archivo documentan el proyecto mejor que cualquier descripción prolongada:

* **La columna `comentario_rechazo` está en el esquema, no solo en la interfaz.** La regla de negocio de que un operador debe escribir el motivo del rechazo tiene respaldo físico: si la columna no admite el texto, el rechazo no se registra (§9.2).
* **`addForeignKey('ciudadano_id', 'users', 'id', 'RESTRICT', 'RESTRICT')` protege el expediente maestro:** no se puede borrar la cuenta de un ciudadano que ya tiene trámites. La baja de cuentas es lógica, no física (§8.1).
* **La misma migración produce dos dialectos.** La consulta por `DBDriver` para elegir `BIGSERIAL` o `INTEGER` es lo que permite que el esquema se levante tanto en el Supabase de producción como en el SQLite en memoria del harness de pruebas (§12.6). La portabilidad del esquema se pagó con una línea por columna generada, y fue la inversión más rentable del proyecto en términos de capacidad de prueba.

#### 3.3.3. Ejecución y comprobación

Posteriormente se ejecutaron los comandos de migración desde la consola, lo que construyó automáticamente el esquema relacional en el servidor de Supabase:

```bash
php spark migrate          # aplica las migraciones pendientes
php spark migrate:refresh  # reconstruye desde cero (solo entorno de pruebas)
```

Accediendo al panel visual de la plataforma se comprobó que las entidades quedaran correctamente generadas: tablas, claves foráneas y tipos. El mismo mecanismo sustenta el harness de pruebas (§12.6), que levanta y revierte el esquema completo contra SQLite antes de cada caso.

**Tres decisiones de modelado que conviene explicar en la tesis**, porque son las que sostienen el resto del sistema:

1. **Expediente maestro + metadatos EAV.** Una sola tabla `solicitudes` para los 7 trámites, con los campos específicos en `solicitud_datos` (entidad-atributo-valor). Evita siete tablas casi idénticas y permite que agregar un trámite nuevo sea **añadir una entrada al catálogo declarativo**, no un controlador y una vista nuevos (§1.3, §5.5.2).
2. **Traza inmutable de decisiones.** `historial_estatus` guarda cada cambio de estado con funcionario responsable, fecha y comentario obligatorio; `auditoria` registra eventos sensibles con IP. Son la base de la validez administrativa del expediente (§9.4).
3. **Origen del trámite como dato, no como esquema.** El canal (`portal` o `presencial`) se registra como un atributo en `solicitud_datos`, de modo que las dos vías de radicación comparten folio, tarifario, estados y expediente en lugar de duplicarlos (§5.5.5).

#### 3.3.4. Deriva de esquema entre el repositorio y la base instalada (el caso real de 0008 y 0009)

El punto 2 de la sección anterior —"las migraciones construyen el esquema"— es cierto para el harness de pruebas y **no lo fue para la base de producción**. La base institucional se levantó pegando a mano los archivos de `supabase/migrations/` en el SQL Editor de Supabase, mientras que las pruebas levantan el esquema desde las migraciones PHP de `app/Database/Migrations/`. Son **dos rutas hacia el mismo esquema**, y cuando una se queda atrás el fallo no aparece hasta que alguien abre la pantalla en producción.

Eso fue exactamente lo que pasó: `/admin/verificaciones` y el alta presencial de UR-01 respondían error, `/admin/convocatorias` mostraba un estado vacío imposible de salir, el desglose reportaba "no hay valor de UMA vigente" y UR-02/03/06 no podían vincular su concesión. Ninguna prueba lo detectaba porque **la suite siempre construía el esquema completo desde cero** y por tanto nunca veía la base real.

**Cómo se cerró, y en qué orden:**

1. **Medir antes de tocar.** Se escribió un verificador de deriva **de solo lectura**, `scripts/verificar_esquema_bd.php`: compara los nombres de tabla y de columna que el repositorio declara en sus migraciones contra los que existen realmente en la base del `.env`, y con `--formato=sql` imprime el `CREATE`/`ALTER` faltante. Compara nombres, no tipos, porque `DECIMAL` y `NUMERIC` son el mismo tipo en PostgreSQL y marcarlos como diferencia genera ruido.
2. **Entregar el DDL, no ejecutarlo.** Regla de trabajo acordada con la institución: **el desarrollo no lanza sentencias de alteración contra la base institucional**; entrega el SQL para que la persona responsable lo aplique en el SQL Editor de Supabase. Por eso `0008_completar_esquema_pendiente.sql` y `0009_concesion_id_sin_cascade.sql` son archivos del repositorio, pensados para pegarse completos: **idempotentes** (`CREATE TABLE IF NOT EXISTS`, `ADD COLUMN IF NOT EXISTS`, `DROP CONSTRAINT IF EXISTS`), **dentro de transacción** y **con su consulta de verificación al final** para que la aplicación quede comprobada en el mismo acto.
3. **Comprobar después de aplicar.** Volver a correr el verificador hasta que la salida no reporte ninguna tabla ni columna faltante, y confirmar en el mismo acto que la llave de `concesion_id` reporta `RESTRICT`.

**Lección metodológica:** una base construida a mano no puede verificarse "abriendo pantallas". El verificador de deriva quedó en el repositorio y forma parte del procedimiento de despliegue: se corre contra la base real, y cualquier divergencia se resuelve **regresando migraciones al repositorio**, nunca editando la base en silencio.

---

### 3.4. Fase de Programación del Backend y Reglas de Negocio

El desarrollo del motor del sistema en CodeIgniter 4 bajo el patrón **Modelo-Vista-Controlador (MVC)** fue el núcleo del trabajo: al no existir una API institucional (§3.1.1), hubo que programar toda la lógica operativa.

#### 3.4.1. Modelos y controladores

Se programaron los *Models* para interactuar de forma segura con la base de datos y los *Controllers* para recibir las peticiones del usuario. El inventario real del repositorio:

| Capa | Cantidad | Detalle |
|---|---|---|
| Controladores | 30 | 5 de acceso, inicio y documentos (`AuthController`, `Home`, `ArchivosLocalesController`, `AgentesWebController`, `DocumentosPagoController`), 12 del panel, 9 del portal, 1 del panel judicial, 1 de cobros compartido (`CobrosController`, sirve a panel y juez), 2 API REST |
| Modelos | 24 | uno por entidad persistible, todos sobre `BaseModel` con *prepared statements* de PDO |
| Bibliotecas de servicio | 22 | 20 servicios + 2 excepciones de dominio; lógica que no pertenece ni al controlador ni al modelo (§3.4.5) |
| Vistas PHP | 76 | organizadas por destino: `admin/`, `tramites/`, `cobros/`, `catalogos/`, `usuarios/`, `convocatorias/`, `app_transito/`, `solicitudes_presenciales/`, `portal/`, `auth/`, `juez/`, `documentos/`, `layouts/` y `errors/html/` |
| Filtros propios | 5 | `AuthFilter`, `RoleFilter`, `PermisoOperadorFilter`, `AdminRedirectFilter`, `AgenteTokenFilter` |

**Controladores independientes por trámite.** Se desarrolló un controlador dedicado para cada uno de los 7 trámites operativos de movilidad, asegurando que el sistema validara correctamente los campos antes de guardarlos:

| Trámite | Controlador | Regla distintiva que valida |
|---|---|---|
| UR-TT-T-01 Concesión de Transporte | `Ciudadano\TramiteConcesionTransporteController` | persona física/moral, RFC, modalidades |
| UR-TT-T-02 Constancia de Despintado | `Ciudadano\TramiteDespintadoController` | exento de pago, obliga a cita de inspección física |
| UR-TT-T-03 Orden de Plaqueo | `Ciudadano\TramiteOrdenPlaqueoController` | oficio para gestión estatal (SATEG) |
| UR-TT-T-04 Permiso Eventual | `Ciudadano\TramitePermisoEventualController` | cotización por días de vigencia, con pago |
| UR-TT-T-05 Cierre Temporal de Calles | `Ciudadano\TramiteCierreCalleController` | horas/días y categoría vial, vías alternas |
| UR-TT-T-06 Cesión de Concesión | `Ciudadano\TramiteCesionConcesionController` | regulado, detrás de bandera de función |
| UR-TT-T-07 Carga y Descarga | `Ciudadano\TramiteCargaDescargaController` | toneladas y periodo, con pago |

Cada uno de los siete tiene su clase de prueba dedicada o compartida en la suite (§12.2), más `TramitesValidacionRedirectTest`, que verifica que un formulario inválido regrese con los errores visibles y **no** con el expediente a medias.

#### 3.4.2. Gestión documental (el módulo crítico)

Se programó un módulo específico para la subida de archivos (`app/Libraries/DocumentoUploader.php` + `app/Libraries/SupabaseStorage.php`). Su secuencia, tal como está en el código:

1. **Rechazo en origen:** se descarta cualquier archivo con error de subida y, fuera del entorno de pruebas, todo lo que no pase `isValid()` (evita confiar en un `multipart` fabricado a mano).
2. **Validación de peso:** tope duro de **10 MB** (`$tamanoMaximo = 10485760`).
3. **Validación de tipo por contenido, no por nombre:** se inspecciona el *MIME* real del archivo con `getMimeType()` contra una lista corta de tipos permitidos —`application/pdf`, `image/jpeg`, `image/png`— y la **extensión se deduce del MIME**, nunca del nombre que envió el cliente. Un archivo llamado `licencia.pdf` que en realidad es otro formato no pasa.
4. **Nombre único:** se genera un **UUID v4** completo con `random_bytes(16)` (con los bits de versión y variante correctos) y se le anexa la extensión deducida. La ruta dentro del almacén es `solicitud_{id}/{uuid}.{ext}`, así que los documentos de un expediente quedan agrupados y ninguno puede pisar a otro.
5. **Huella criptográfica:** se calcula `hash('sha256', $contenido)` sobre el binario leído en memoria y se guarda en la columna `documentos.hash_sha256`. Esto garantiza que el archivo **no sea alterable en el futuro** sin que la alteración se detecte al recalcular la huella.
6. **Envío al bucket** `documentos` de Supabase Storage, en modo privado, con el *metadata* de `nombre_original`, `mime_type` y tamaño conservados como datos descriptivos.
7. **Entrega controlada:** la descarga no usa URLs públicas sino **URLs firmadas de vida corta** (Anexo B.2); en modo local la firma es propia con **HMAC-SHA256**, caducidad de 1 hora, comparación en tiempo constante (`hash_equals`) y validación contra travesía de rutas (Anexo B.4).

> **Argumento para la defensa:** el orden importa. Validar el MIME **antes** de asignar nombre y **antes** de subir significa que nunca se escribe en el almacén un archivo que no se quiera; y calcular el hash sobre el contenido en memoria (no sobre el archivo ya almacenado) significa que la huella corresponde a lo que el ciudadano efectivamente entregó, no a lo que el almacén conserva.

#### 3.4.3. Seguridad y roles

Se implementaron **Filtros** (*middleware*) de CodeIgniter para proteger el sistema. Estos filtros leen el rol del usuario que inició sesión; si un ciudadano intenta abrir una URL que pertenece al panel de administración, el sistema lo bloquea automáticamente.

| Filtro | Qué hace | Alcance declarado |
|---|---|---|
| `AuthFilter` | Exige sesión iniciada (`user_id`); sin ella, redirige a login | rutas protegidas de portal y panel |
| `RoleFilter` | Consulta el rol real en base de datos (`tieneRol`) y autoriza o rechaza | argumentos por ruta (`role:administrador`) |
| `AdminRedirectFilter` | Si un funcionario entra al portal ciudadano, lo devuelve a su panel | prefijo `portal/*` |
| `PermisoOperadorFilter` | Autorización de grano fino por sección dentro del rol operador | `admin`, `admin/*` |
| `AgenteTokenFilter` | Valida el token de portador con caducidad para las escrituras de la app móvil | `api/infracciones/*` |

Complemento importante para el documento final: la permisología de grano fino se resuelve por **prefijo de URI más largo**, así que una ruta nueva creada bajo un prefijo ya catalogado queda protegida sin escribir configuración (§9.5), y el administrador **nunca puede bloquearse a sí mismo** (§9.5).

#### 3.4.4. Reglas de negocio del expediente

* **Máquina de estados finita:** `EstadoSolicitudService` define qué transiciones son legales; un expediente no puede saltar de `Rechazado` a `Aprobado` ni pasar por alto un estado intermedio (§9.1).
* **Motivo obligatorio:** ningún cambio a `Prevención` o `Rechazo` se acepta sin comentario escrito, con respaldo físico en `historial_estatus.comentario` y en `solicitudes.comentario_rechazo` (§9.2, §3.3.2).
* **Cotización dinámica:** `TarifarioService` resuelve el monto a partir de tarifas vigentes por periodo y, cuando aplica, del valor de la UMA; `ResolverMontoInfraccionService` hace lo propio con los rangos mínimo/máximo de las infracciones (§4.7).
* **Folio único:** `FolioGenerator` produce un identificador de 20 caracteres derivados de un UUID v4, usado como llave visible del expediente y como criterio de búsqueda en ambos canales (§5.5).
* **Integridad histórica del padrón:** ningún catálogo puede borrarse si tiene expedientes vinculados. Se aplica dos veces a propósito —en el controlador y en la llave foránea `ON DELETE RESTRICT`— porque el guardado de aplicación no protege un `DELETE` lanzado desde fuera (Anexo A.2, §3.3.4).
* **El ciudadano elige, el sistema no adivina:** cuando existen varias convocatorias abiertas, UR-01 presenta la lista y obliga a elegir; el parámetro de URL solo preselecciona dentro de lo que realmente está abierto (§4.7.1).

#### 3.4.5. Desplazamiento de lógica a servicios (por qué no todo va en el controlador)

Veintidós archivos de `app/Libraries/` (veinte servicios y dos excepciones de dominio) asumen la lógica que un MVC ingenuo dejaría en los controladores: `EstadoSolicitudService`, `TarifarioService`, `ResolverMontoInfraccionService`, `CobroService`, `CalificarBoletaService`, `HojaPagoService`, `CorteCajaService`, `CondonacionRecargo`, `ReferenciaBancariaProvisional`, `BoletaAccionesService`, `AccionesSeguimientoSolicitud`, `CuentaCiudadanoService`, `DocumentoUploader`, `SupabaseStorage`, `FolioGenerator`, `BanbajioMockGateway`, `FeatureFlags`, `AgenteTokenService`, `PermisosOperador`, `DesgloseSistema`, más `CobroException` y `CalificacionBoletaException`. La razón no es estética: **una regla que vive en una biblioteca puede probarse sin HTTP** (las once clases de `tests/app/Libraries/`) y puede reutilizarse desde el portal, desde el panel y desde la API sin copiar código. El caso más claro es el tarifario: el mismo cálculo lo consumen el ciudadano que inicia el trámite, el operador que lo radica en ventanilla y el tablero que suma lo recaudado. El segundo es `HojaPagoService`: imprime el desglose sin ser dueño de ninguna fórmula de dinero (§7.10).

---

### 3.5. Fase de Frontend y Despliegue en Entorno de Producción

#### 3.5.1. Conexión de vistas

La última etapa de desarrollo consistió en programar las **vistas** (*Views*) en PHP/HTML, conectándolas con los datos que enviaba el controlador. Se construyeron dos entornos visuales complejos:

* **El Portal Ciudadano:** permite iniciar trámites, subir documentos y cuenta con un **simulador de pago en línea** para cambiar el estatus del expediente. El simulador no es un adorno del frontend: está aislado tras la interfaz `PaymentGatewayInterface` y su implementación `BanbajioMockGateway`, con tres endpoints `pagar` reales (`carga-descarga`, `permiso-eventual`, `cierre-calle`) que invocan la pasarela, registran `fecha_pago` y hacen avanzar la máquina de estados. Cambiar el proveedor real exige **sustituir la implementación, no reescribir el portal** (§1.3, §7.8).
* **El Panel Administrativo Web:** desarrollado desde cero con un *dashboard* de control para funcionarios, con desglose navegable (cada tarjeta de métrica lleva a su detalle), bandeja maestra de expedientes, visor documental, agenda de inspecciones, radicación presencial y administración de catálogos. Se incluyó una **regla de negocio estricta**: si un operador rechaza un trámite ciudadano, el sistema lo obliga a escribir el motivo del rechazo para que el ciudadano pueda subsanarlo (§9.2).

**Convención de capa de vista adoptada en la fase final.** Las 76 vistas se unificaron tras la convención `/** @var */` + lectura del arreglo inyectado (§12.3), y las páginas de error por defecto del marco se sustituyeron por cuatro páginas propias en español (`app/Views/errors/html/`: `error_400.php`, `error_404.php`, `error_exception.php` y `production.php`), de modo que un 404 o un 400 no muestre ni trazas técnicas ni texto en inglés a la ciudadanía. Los enlaces escritos a mano en esas páginas son los que destaparon el defecto de enrutamiento de §2.3 y motivaron el cotejo automático de §12.4.

#### 3.5.2. Pruebas y despliegue

Con el sistema codificado se realizaron **pruebas manuales simulando el ciclo completo de una solicitud ciudadana** (radicar → cotizar → pagar → revisar → prevenir → subsanar → dictaminar → consultar en el portal), y ese mismo ciclo quedó automatizado en la suite de funcionalidad de `tests/app/Controllers/` (§12.1, §12.2). Una vez validado, el sistema se publicó en internet mediante **Vercel**.

Dado que Vercel utiliza tecnología **serverless** (sin un servidor fijo ni sistema de archivos persistente), se tuvo que reconfigurar CodeIgniter para que **las sesiones de los usuarios se guardaran dentro de la base de datos de PostgreSQL** en lugar de en archivos temporales:

| Ajuste | Configuración |
|---|---|
| Motor de sesiones | se cambia **condicionalmente según la plataforma**, en el constructor de `app/Config/Session.php` (ver código abajo) |
| Tabla de destino | `ci_sessions`, creada por `supabase/migrations/0003_ci_sessions.sql` |
| Conectividad | Connection pooler **PgBouncer** de Supabase (puerto 6543, modo transacción) con PDO (Anexo C.3) |
| Entrega estática | `vercel.json` declara el arreglo `routes`; **solo se sirve lo declarado** (§12.7) |
| Construcción automática | `scripts/vercel-install.sh` ejecuta `composer install` y el *build* de la app Expo en cada publicación |
| HTTPS | filtro `forcehttps` y `APP_FORCE_HTTPS` hacia el dominio de producción |

El cambio de motor de sesiones no se escribió como un valor fijo sino como una **adaptación detectada en tiempo de arranque**, lo que permite conservar un solo código base para dos entornos:

```php
// app/Config/Session.php
public function __construct()
{
    parent::__construct();

    if ($this->isVercel()) {                 // getenv('VERCEL') == '1'
        $this->driver   = DatabaseHandler::class;
        $this->savePath = 'ci_sessions';
        $this->DBGroup  = null;              // usa el DBGroup 'default'
    }
}
```

Fuera de Vercel el controlador sigue siendo `FileHandler` con `savePath = WRITEPATH.'session'`, así que el desarrollo local no depende de tener la base de datos levantada para iniciar sesión. **El costo de esa comodidad:** el comportamiento de sesiones solo se ejercita contra PostgreSQL en producción, motivo adicional para verificar siempre contra la URL desplegada.

Con este último ajuste el sistema quedó publicado en línea, completamente funcional y seguro, y listo para la fase final de pruebas con la dependencia.

> **Error de despliegue que conviene registrar** (y que la prueba de humo contra producción detectó): en local, `php -S` / `spark serve` sirve todo el contenido de `public/`, pero Vercel solo sirve lo declarado en `vercel.json`. Un recurso estático nuevo (una imagen, un favicon) **funciona en local y responde 404 en producción**. Por eso la verificación de estáticos, rutas y *headers* se hace siempre contra la URL desplegada.

---
### 3.6. Correspondencia con las Figuras del Documento Final

La tabla siguiente indica, para cada figura, qué debe mostrar y de dónde obtenerla.

| Figura | Qué debe capturar | Dónde obtenerla |
|---|---|---|
| Figura 1 | Captura del grupo de mensajería del equipo | conversación real; recortar nombres y teléfonos ajenos |
| Figura 2 | Bitácora física de control de folios y documentos retenidos | fotografía de campo en Tránsito y Seguridad |
| Figura 3 | Diseño preliminar de la pantalla de inicio | borrador de diseño o captura temprana del repositorio (`git log` de `app/Views/`) |
| Figura 4 | Diagrama relacional preliminar de la base de datos | panel visual de Supabase (Database Schema) o exportación del esquema del Anexo A |
| Figura 5 | Fragmento del método de subida de archivos | `app/Libraries/DocumentoUploader.php::subir()` (el paso de UUID + SHA-256 es el más ilustrativo) |
| Figura 6 | Panel administrativo (`Dashboard`) | `/admin/dashboard` en producción |
| Figura 7 | Portal Ciudadano publicado en línea | `/portal/dashboard` en el dominio de producción |
| Figura 8 | Before/after del desbordamiento móvil | el título "Registrar Solicitud Presencial" recortado frente a acotado a dos líneas (§11.9.4) |
| Figura 9 | Terminal con `php spark migrate` y las migraciones aplicadas | evidencia directa de §3.3.3 |
| Figura 10 | Corrida de PHPUnit en verde | salida de `vendor/bin/phpunit --no-coverage`; citar la cifra obtenida en ese momento (§12.1) |
| Figura 11 | Flujo de la máquina de estados | diagrama de la sección 3 de `DOCUMENTACION_GENERAL.md` o §9.1 |
| Figura 12 | Pantalla de cobros con el listado y sus acciones | `/admin/cobros` y `/juez/cobros` (§7.4) |
| Figura 13 | Hoja de ayuda para el pago impresa | `/documentos/hoja-pago/solicitud/{folio}` (§7.10) |
| Figura 14 | Corte de caja con su exportación CSV | `/admin/cobros/corte` (§7.11) |

### 3.7. Referencias Tecnológicas Utilizadas (para la bibliografía)

Formato tentativo en APA 7, pendiente de que el autor verifique fechas de consulta y versión conforme a su institución:

* Bootstrap. (s.f.). *Bootstrap 5.3.2 documentation*. https://getbootstrap.com/
* Bootstrap Icons. (s.f.). *Bootstrap Icons 1.11.1*. https://icons.getbootstrap.com/
* CodeIgniter Foundation. (s.f.). *CodeIgniter 4 documentation* (versión 4.7.4). https://codeigniter.com/user_guide/
* Expo. (s.f.). *Expo SDK 57 / React Native 0.86*. https://docs.expo.dev/
* National Institute of Standards and Technology (NIST). (2015). *FIPS 180-4: Secure Hash Standard (SHS)*. U.S. Department of Commerce. https://nvlpubs.nist.gov/nistpubs/FIPS/NIST.FIPS.180-4.pdf
* PostgreSQL Global Development Group. (s.f.). *PostgreSQL 15 documentation*. https://www.postgresql.org/docs/15/
* PHP Group. (s.f.). *PHP 8.2 manual*. https://www.php.net/manual/es/
* PHPUnit. (s.f.). *PHPUnit 10.5 documentation*. https://phpunit.de/
* Supabase. (s.f.). *Supabase: Open Source Firebase Alternative* (PostgreSQL y Storage). https://supabase.com/docs
* Vercel. (s.f.). *Vercel: Build and deploy the best web experiences with the Frontend Cloud*. https://vercel.com/docs

---

### 3.8. Fase de Cobros (COB-01 a COB-12): Del Folio de Campo al Recibo de Pago

#### 3.8.1. El problema que abrió la fase

Hasta este corte el sistema sabía **dictaminar** y sabía **cobrar en línea con un mock**, pero no sabía **cobrar en ventanilla de forma trazable**. Convivían tres huecos verificables en el código previo:

1. Una solicitud aprobada tenía `monto` y `estado_pago`, pero ningún registro contable que dijera **quién cobró, cuándo, dónde y con qué desglose**.
2. Una boleta levantada por un agente llegaba a la base con `estado = 'recibida'` y **nadie la calificaba**: el monto de la multa no existía hasta que un juez lo fijara, y ese acto no tenía registro propio.
3. El ciudadano que iba a pagar salía de la ventanilla **sin un papel** que dijera cuánto, hasta cuándo y dónde.

La fase se organizó en **doce tickets repartidos en tres carriles paralelos**, con una regla de coordinación explícita: **cada carril agrega, ninguno cambia las fórmulas de otro**. Esa regla es la que permitió que el carril de documentos y ciudadano escribiera la hoja de pago **sin tocar `CobroService` ni `ResolverMontoInfraccionService`**.

#### 3.8.2. Tablero de tickets y estado real

| Ticket | Alcance verificado en el repositorio | Evidencia en código | Estado |
|---|---|---|---|
| **COB-01** | Base de cobros: tablas `cobros` y `cobro_movimientos`, folios `COB-AAAA-NNNNNN` / `REC-AAAA-NNNNNN`, creación, cobro, cancelación e historial | `CobroService`, migración `000027` | **Entregado** |
| **COB-02** | Listado de cobros con filtros, resumen de cartera, detalle con historial y ventanas de cobrar / mandar a completar / cancelar | `CobrosController` (`index`, `ver`, `cobrar`, `completar`, `cancelar`), vistas `cobros/index`, `cobros/ver`, `cobros/_acciones` | **Entregado** |
| **COB-03** | Corte de caja por día, semana, mes o rango, con filtro por cobrador y exportación CSV | `CorteCajaService`, `CobrosController::corte` y `corteCsv`, vista `cobros/corte` | **Entregado** |
| **COB-04** | Registro de un pago hecho en BanBajío a partir del comprobante, con búsqueda por referencia | `CobroService::registrarPagoBancario`, `ReferenciaBancariaProvisional`, vista `cobros/banco`, migración `000032` | **Entregado** (falta el convenio real) |
| **COB-05** | Hoja de ayuda para el pago imprimible de trámites y multas, desde panel, portal y panel del juez | `HojaPagoService`, `DocumentosPagoController`, vista `documentos/hoja_pago` | **Entregado** |
| **COB-06** | Comprobante de pago: el cobro genera y muestra su folio `REC-…` | columna `folio_recibo`, visible en `cobros/ver` y `cobros/corte` | **Parcial**: el folio existe; falta el formato del talonario oficial |
| **COB-07** | Consulta pública del adeudo por folio, sin sesión | citado como pendiente explícito en el comentario de `DocumentosPagoController::pintar()` | **Pendiente** |
| **COB-08** | Ciudadano registrado solo con nombre: CURP opcional, cuenta sin acceso y reclamo desde el autorregistro | `CuentaCiudadanoService`, migración `000029`, `SolicitudPresencialController`, `RegistroCurpOpcionalTest` | **Entregado** |
| **COB-09** | Rol `juez_calificador`, panel `/juez` y acceso rápido desde el login | `DashboardController`, filtro `role:juez_calificador`, migración `000028`, `JuezCalificadorAccessTest` | **Entregado** |
| **COB-10** | Calificación judicial de boletas y generación del cobro correspondiente | `CalificarBoletaService`, migración `000030`, `CalificarBoletaServiceTest` | **Entregado** |
| **COB-11** | Condonación de recargos con motivo escrito y autorización por rol | `CondonacionRecargo`, `CobrosController::formularioCondonar`, `condonar` y `condonaciones`, vistas `cobros/condonar` y `cobros/condonaciones` | **Entregado** |
| **COB-12** | Seguimiento y protección de boletas en el panel: qué enlaces se muestran y cuándo se cierra la corrección ordinaria | `BoletaAccionesService` (`motivoNoEditable`, `bloquear`, `paraLista`) | **Entregado** |

> **Nota de honestidad documental:** solo se describe el alcance de los tickets cuyo contenido está escrito en el código o en los comentarios del repositorio. Un tablero de proyecto que inventa alcances deja de servir para verificar el avance; por eso COB-06 y COB-07 aparecen con su estado real y no con el deseado.

#### 3.8.3. Las dos decisiones de diseño que sostienen la fase

**1. Un solo cobro para dos orígenes distintos.** Un derecho municipal y una multa se cobran igual en ventanilla, pero nacen en tablas distintas. En lugar de dos módulos, `cobros` tiene un campo `origen` (`solicitud` / `boleta`) y dos llaves foráneas excluyentes, ambas con `ON DELETE RESTRICT`: **un cobro es un registro contable y no puede desaparecer porque alguien borre el expediente que lo originó** (Anexo A.9).

**2. El dinero tiene un solo dueño.** `CobroService` es el único que crea y mueve cobros; `ResolverMontoInfraccionService` es el único que sabe de UMA, descuento por pronto pago y recargo por mora; `CondonacionRecargo` es el único que sabe cuánto puede condonarse y quién lo autoriza. Todo lo demás **consume**:

```php
// HojaPagoService: lee, nunca calcula por su cuenta
$cobro = $this->cobros->vigenteDeSolicitud($solicitudId);
$monto = round((float) ($cobro->monto_base ?? $solicitud->monto ?? 0), 2);
$calculo = $this->montos->aplicarDescuentoRecargo($montoBase, $boleta->fecha_infraccion);
```

Ese orden de dependencias fue lo que hizo posible trabajar en tres carriles sin conflictos de fusión en las reglas de importes.

#### 3.8.4. Lo que falta y de quién depende

El cierre de la fase no depende solo de programación, sino de **datos que únicamente la dependencia puede entregar**:

| Dato pendiente | Para qué se necesita | Sin él… |
|---|---|---|
| Referencia del convenio de **BanBajío** | Imprimir en la hoja una referencia que el banco reconozca | La hoja imprime «ventanilla bancaria» con la referencia provisional del cobro |
| Formato del **recibo oficial** (COB-06) | Que el folio `REC-…` coincida con el talonario que usa la dependencia | El sistema genera folio propio, válido para control interno pero no para el talonario físico |
| Texto del **oficio de comisión** al juez | Que el panel del juez pueda imprimir el documento que respalda su actuación | El juez califica y el registro queda en base, sin impreso asociado |
| **Tope de condonación** autorizado | Validar en servidor el límite que puede aplicar cada rol | La condonación queda registrada con motivo e importe, sin límite automático: `CondonacionRecargo::ROLES_AUTORIZADOS` es una configuración inicial pendiente de validación operativa |
| **Fecha de la sesión con el juez calificador** | Levantamiento del acto judicial real y validación del flujo en operación | El módulo está probado, pero no validado con el usuario final |

A eso se suma **un pendiente de programación con requisito ya definido**: COB-07, la consulta pública del adeudo por folio sin sesión. Está citada en el código y deliberadamente no enlazada desde la hoja de pago, para no publicar un enlace muerto (§2.3).

#### 3.8.5. Evidencia verificable de este corte

* **Migraciones:** `000027` (cobros), `000028` (rol del juez), `000029` (ciudadano sin acceso), `000030` (calificación judicial), `000031` (cobros de solicitudes pendientes), `000032` (referencia bancaria) y `000033` (fichas oficiales de tarifas), con sus espejos `supabase/migrations/0012` a `0018`.
* **Rutas nuevas:** `/juez`, `/juez/boletas`, `/juez/boletas/(:num)`, `/juez/boletas/(:num)/vista-previa`, `/juez/boletas/(:num)/calificar`, `/juez/cobros` (reuso del listado de cobros), `/documentos/hoja-pago/solicitud/{folio o id}`, `/documentos/hoja-pago/boleta/(:num)` y el grupo completo `/admin/cobros` (§2.2).
* **Pruebas nuevas:** `CobroServiceTest`, `CalificarBoletaServiceTest`, `HojaPagoServiceTest`, `CorteCajaServiceTest`, `CondonacionRecargoTest`, `CondonacionControllerTest`, `CuentaCiudadanoServiceTest`, `ReferenciaBancariaProvisionalTest`, `DocumentosPagoTest`, `CobrosControllerTest`, `RegistroCurpOpcionalTest`, `JuezCalificadorAccessTest`, más las ampliaciones de `SolicitudPresencialTest` y `AuthFormsRenderTest` (§12.2).
* **Suite completa:** 43 clases y 410 métodos de prueba en el repositorio (§12.1). La cifra vigente se cita desde la corrida del día, no desde este documento.
* **Verificación de migraciones:** el esquema completo se aplicó sobre una base SQLite en archivo y se comprobó además el alta de un usuario **sin contraseña** con rol de ciudadano, que es el corazón de COB-08.
* **Prueba de humo local:** `/`, `/auth/login`, `/auth/register`, `/agentes` responden 200; `/portal`, `/juez` y la hoja de pago sin sesión redirigen al acceso (302) en lugar de fallar.

> **Error de proceso que conviene dejar registrado:** al intentar verificar las migraciones con `php spark migrate -g tests` se descubrió que `MigrationRunner::setGroup()` **no reconecta** la base —el constructor ya se conectó al grupo por omisión—, de modo que el comando corrió contra la base institucional. El daño se midió (filas de historial insertadas, todas de migraciones idempotentes `CREATE TABLE IF NOT EXISTS`, sin cambio de esquema real), se revirtió con un script transaccional que verificó primero que el lote tuviera **exactamente** los registros esperados y se comprobó después que el historial volviera a su número original de filas. La verificación definitiva se hizo con un grupo de base registrado en tiempo de ejecución contra un archivo SQLite temporal. **Lección: una bandera de comando no es una garantía de aislamiento; hay que comprobarlo contra la base que se está tocando.**

---

## 4. Conclusiones Técnicas

La arquitectura construida para el **Portal Ciudadano y Panel Administrativo Web** resolvió las demandas operativas y normativas de la Dirección de Movilidad y Transporte de Uriangato:

1. **Robustez y Resiliencia:** La integración del patrón MVC con persistencia de sesiones en base de datos (`ci_sessions`) y el uso de *connection pooling* sobre PostgreSQL garantizó una operación estable en un entorno serverless (Vercel). La fachada de almacenamiento con doble backend (nube y local firmado) sumó la capacidad de operar y probarse sin salida a internet (Anexo C).
2. **Certeza Jurídica y Seguridad:** La inmutabilidad de los expedientes mediante hashing SHA-256, el almacenamiento privado con firmas temporales, la matriz de control de acceso RBAC y la **permisología configurable de grano fino** (§9.5) protegen los datos personales de la ciudadanía y blindan los actos de autoridad municipal. La restricción de que el administrador nunca se autoexcluya y de que las bajas definitivas no sean configurables cierra la superficie de error administrativo.
3. **Mantenibilidad y Escalabilidad:** El desacoplamiento mediante patrones de diseño (EAV para trámites heterogéneos, *feature flags* para control de versiones y *gateway* para pagos) proporciona al H. Ayuntamiento una base tecnológica sólida. El catálogo declarativo de campos (§5.5.2) demostró su valor concreto: agregar un trámite nuevo al canal presencial es **añadir una entrada al arreglo**, no escribir un controlador y una vista nuevos.
4. **Cobertura del Mundo Real (Canal Híbrido):** El sistema no asume un ciudadano digitalmente competente. La coexistencia del portal de autogestión (§4) y la radicación presencial con validez idéntica (§5.5), ambas escribiendo en el mismo expediente y compartiendo folio, tarifario y máquina de estados, es la decisión que convierte la plataforma en una herramienta **usable por la totalidad de la población** y no solo por su segmento conectado.
5. **Usabilidad Multidispositiva como Requisito, no como Adorno:** El diseño *mobile-first* con objetivos táctiles de 44 px, navegación inferior en el portal, gaveta lateral con áreas seguras en el panel y una portada que degrada el desenfoque para priorizar contraste (§11) responde al dispositivo real con el que la gente tramita. La corrección del servidor de borde para recursos estáticos (§11.7) dejó registrada una restricción de arquitectura que de otra forma se manifestaría como fallos silenciosos en producción.
6. **Calidad Comprobable:** La verificación no es una afirmación sino un resultado reproducible: **43 clases y 410 métodos de prueba**, sobre un harness SQLite aislado de los datos institucionales (§12), con cobertura de permisos, estados, montos dinámicos, validaciones por trámite, renderizado de vistas, candados de integridad del padrón, cotejo estático de enlaces, blindajes de almacenamiento y, desde la fase de cobros, de la creación y cobro de adeudos, la calificación judicial de boletas, el alta de ciudadano sin acceso, la condonación de recargos, el corte de caja y la hoja de pago.
7. **Medir en Lugar de Suponer:** Tres hallazgos del cierre del proyecto no habrían aparecido con una revisión visual: el título de todas las páginas administrativas se pintaba vacío (§12.3), el texto largo del panel se recortaba en silencio por debajo del *overflow-x: hidden* (§11.9) y el botón "Iniciar sesión" de la página 404 propia llevaba a otro 404 porque CI4 descartaba el atajo por colisión de nombre con la ruta (§2.3, §12.4). El cuarto caso es inverso y también vale: la queja de que el haz publicado de la app móvil seguía en inglés se descartó con evidencia —reexportar produjo salida **idéntica byte a byte** y el bundle servido en producción no contenía esa cadena—, lo que demuestra que medir también sirve para no "arreglar" algo que no está roto. Los tres defectos reales se detectaron con instrumentación —barrido de HTML producido, medición a 360/320/290 px con página de control y sondeo de la URL desplegada— y los tres se convirtieron en candados automatizados para que no puedan regresar.
8. **El Proceso También Es Resultado:** La secuencia iterativa e incremental (§2) y el levantamiento presencial en dos vertientes (§3.1) no son un preámbulo del trabajo sino parte de él: explican por qué el andamiaje soportó siete trámites sin reescribirse, por qué el esquema de datos es portable entre PostgreSQL y SQLite y por qué el panel habla el vocabulario operativo de la dependencia en lugar de imponer otro.

---

## 5. Cómo Usar Este Documento en el Informe Final

* **Capítulo de metodología (proceso por fases):** copiar y desplegar desde el **capítulo 3**, que ya está redactado en orden temporal y con sus tablas de evidencia.
* **Capítulos de arquitectura, base de datos y seguridad:** remitirse a `DOC_PORTAL_PANEL.md` §1, §10 y a los anexos A, B y C, que son el detalle técnico.
* **Capítulos de pantallas, cobros y CRUDs:** remitirse a `DOC_PORTAL_PANEL.md` §3 a §9; cada pantalla tiene su ficha con URL, controlador, vista, datos que inyecta y acciones.
* **Capítulo de resultados y análisis (datos cuantitativos):** la tabla de antes/después de la auditoría móvil (§11.9 de `DOC_PORTAL_PANEL.md`), la cifra de la suite (§12.1), la verificación de sincronía entre ramas (§12.5) y el número de migraciones y tablas (§3.3 de este documento, Anexo A del otro).
* **Antes de entregar:** volver a ejecutar `vendor/bin/phpunit --no-coverage` y citar la cifra obtenida en ese momento, no la de este documento.
