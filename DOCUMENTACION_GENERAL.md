# Sistema Integral de Ventanilla Única de Movilidad y Transporte de Uriangato

## Documentación técnica y operativa general

> **Estado del documento:** actualizado al 29 de octubre de 2026
> **Alcance:** visión macro del sistema: propósito, actores, glosario, las tres ramas funcionales, catálogo de trámites, ciclo de vida del expediente, esquema de datos en resumen, instalación y despliegue.
> **Fuente de verdad:** código del repositorio (`app/`, `mobile/agentes-transito/`), cifras medidas el 29-oct-2026.
> **Documentos relacionados:** [README.md](./README.md) · [DOCUMENTACION_BACKEND.md](./DOCUMENTACION_BACKEND.md) · [DOC_PORTAL_PANEL.md](./DOC_PORTAL_PANEL.md) · [DOCUMENTACION_APP_TRANSITO.md](./DOCUMENTACION_APP_TRANSITO.md) · [DOC_METODOLOGIA.md](./DOC_METODOLOGIA.md)

Este documento explica **qué hace el sistema y por qué**. El detalle técnico de clases, rutas y tablas vive en [DOCUMENTACION_BACKEND.md](./DOCUMENTACION_BACKEND.md); el detalle pantalla por pantalla de la web, en [DOC_PORTAL_PANEL.md](./DOC_PORTAL_PANEL.md); el de la app de campo, en [DOCUMENTACION_APP_TRANSITO.md](./DOCUMENTACION_APP_TRANSITO.md).

---

## Índice

1. [Identificación y propósito del proyecto](#1-identificación-y-propósito-del-proyecto)
   - [Glosario canónico](#glosario-canónico)
   - [Actores del sistema](#actores-del-sistema)
2. [Visión integral y arquitectura de las tres ramas](#2-visión-integral-y-arquitectura-de-las-tres-ramas)
3. [PARTE I: Portal ciudadano y panel administrativo web](#parte-i-portal-ciudadano-y-panel-administrativo-web)
4. [PARTE II: App móvil para agentes de tránsito](#parte-ii-app-móvil-para-agentes-de-tránsito)
5. [PARTE III: Base de datos unificada y esquema de seguridad](#parte-iii-base-de-datos-unificada-y-esquema-de-seguridad)
6. [PARTE IV: Guía de instalación, configuración y despliegue](#parte-iv-guía-de-instalación-configuración-y-despliegue)
7. [Metodología de trabajo y división del equipo](#5-metodología-de-trabajo-y-división-del-equipo)
8. [Documentos complementarios](#6-documentos-complementarios)

---

## 1. Identificación y propósito del proyecto

**Proyecto:** Sistema Integral de Ventanilla Única de Movilidad y Transporte del municipio de Uriangato, Guanajuato.
**Proyecto de origen:** Residencia profesional.
**Estado:** en operación. La fase de cobros está cerrada (COB-01 a COB-12).

### 1.1 El problema que resuelve

Antes del sistema, un trámite de movilidad exigía que la persona acudiera físicamente a la Dirección de Movilidad y Transporte, entregara documentación en papel, esperara una revisión manual y volviera a pagar en otra ventanilla. El expediente se seguía en carpetas físicas y el ciudadano dependía de una llamada o de una visita para saber en qué iba su trámite. Las boletas de infracción se levantaban a mano y su cobro se registraba en libros de caja distintos según el lugar de pago.

El sistema resuelve tres cosas a la vez:

1. **El ciudadano inicia y sigue su trámite en línea**, con documentos digitales, folio único y estado visible en todo momento.
2. **La Dirección opera desde un solo panel**: revisa expedientes, dictamina, cobra, condona, cierra caja y audita.
3. **El agente de tránsito levanta boletas en campo, sin conexión**, y las sincroniza cuando recupera señal; el juez calificador las evalúa y genera el cobro correspondiente.

### 1.2 Alcance funcional

| Función | Dónde se ejecuta |
|---|---|
| Inicio, seguimiento y pago de siete trámites de movilidad | Portal del ciudadano |
| Revisión documental, dictamen, evaluación técnica y validación | Panel administrativo |
| Cobro de trámites y multas, condonación y corte de caja | Panel administrativo y panel del juez |
| Calificación de boletas de infracción | Panel del juez |
| Levantamiento de boletas en campo, con evidencia fotográfica y funcionamiento sin conexión | App móvil de agentes |
| Administración de catálogos, tarifas, concesiones, convocatorias, usuarios y agentes | Panel administrativo |
| Bitácora de todas las acciones sensibles | Panel administrativo |

### Glosario canónico

Se define aquí una sola vez y se enlaza desde el resto de los documentos.

| Término | Definición precisa |
|---|---|
| **Solicitud** / **expediente** | El registro de un trámite iniciado por un ciudadano. Tiene folio, trámite, estatus, monto y fechas. «Solicitud» y «expediente» se usan como sinónimos en todo el corpus. |
| **Folio de solicitud** | Identificador público del expediente. Es el número que el ciudadano cita al preguntar por su trámite. |
| **Trámite** | Cada uno de los siete procedimientos oficiales, identificados como `UR-TT-T-01` a `UR-TT-T-07`. La clave combina la Unidad Responsable (UR) con el tipo de trámite (TT). |
| **Boleta** | La infracción levantada por un agente de tránsito en campo. Tiene folio propio y datos del infractor, del vehículo, de las faltas y de las evidencias fotográficas. |
| **Calificación** | El acto del juez calificador de determinar el monto de una boleta, asignando UMA a cada falta. Genera el cobro. |
| **Cobro** | El documento de pago que puede originarse en una solicitud (derechos de trámite) o en una boleta (multa). Tiene folio `COB-AAAA-NNNNNN`. |
| **Movimiento de cobro** | Cada acción registrada sobre un cobro: creación, cobro, envío a completar, cancelación o condonación. Forma el historial del cobro. |
| **Folio de recibo** | El identificador del pago, con formato `REC-AAAA-NNNNNN`. Se entrega al pagador. |
| **UMA** | Unidad de Medida y Actualización. Es la base de cálculo de las multas: cada falta tiene un mínimo y un máximo en UMA, y el valor vigente de la UMA los convierte a pesos. |
| **Pronto pago** | Descuento del **40 %** aplicable cuando la multa se paga dentro de los primeros **10 días hábiles** y antes de un mes desde la infracción. |
| **Recargo por mora** | Cargo del **5 %** mensual acumulado cuando la multa se paga después de un mes desde la infracción. |
| **Condonación** | Renuncia total o parcial al recargo por mora. Requiere autorización de un usuario facultado y queda registrada. |
| **Permiso de operador** | Cada una de las **14** secciones del panel administrativo que el administrador puede habilitar o retirar al rol de operador de ventanilla. |
| **Convocatoria** | Publicación oficial de la UR-01 por la que se abre un periodo de postulación y se selecciona al ganador de una concesión de transporte. |
| **Concesión** | El título que autoriza a una persona física o moral a prestar servicio de transporte público. Se identifica por `numero_titulo`. |
| **Corte de caja** | El resumen de todos los cobros de un periodo, agrupados por lugar de pago, cobrador y concepto, con sus condonaciones. |
| **ORIGEN PRESENCIAL** | Expediente iniciado en ventanilla para una persona que no usa el portal. Se registra con la misma estructura que un trámite en línea. |

### Actores del sistema

| Actor | Rol técnico | Qué puede hacer | Superficie |
|---|---|---|---|
| **Ciudadano** | `ciudadano` | Registrarse, iniciar los siete trámites, subir documentos, pagar, consultar el estado de sus expedientes y descargar sus documentos. Solo ve lo propio. | Portal |
| **Operador de ventanilla** | `operador_ventanilla` | Todo lo operativo del panel que el administrador le haya habilitado: expedientes, cobros, usuarios, catálogos, formatos, auditoría. **No** puede eliminar registros ni cambiar permisos. | Panel administrativo |
| **Administrador** | `administrador` | Acceso total: lo del operador más las bajas definitivas, los permisos del operador y el desglose global. Nunca se le filtra por permiso. | Panel administrativo |
| **Juez calificador** | `juez_calificador` | Revisar boletas, calificarlas, cobrar multas, condonar recargos y cerrar su propia caja. No administra catálogos ni usuarios. | Panel del juez |
| **Agente de tránsito** | `agente` (tabla aparte) | Iniciar sesión con placa y contraseña, levantar boletas sin conexión, fotografiar evidencias y sincronizar. | App móvil |
| **Jefe de turno** | `jefe_turno` (tabla aparte) | Las mismas funciones del agente con visibilidad de su sector. | App móvil |

Nota de diseño: los agentes de tránsito **no** son usuarios del portal. Viven en la tabla `agentes_transito`, con su propio mecanismo de autenticación por placa y token Bearer, porque su operación es de campo y no requiere acceso al portal ni al panel.

---

## 2. Visión integral y arquitectura de las tres ramas

El sistema tiene **una sola base de datos y tres superficies de acceso**. No son tres aplicaciones independientes: comparten el mismo esquema, el mismo catálogo de trámites y el mismo módulo de cobros.

```
                         ┌──────────────────────────┐
                         │  PostgreSQL 15 (Supabase)│
                         │  26 tablas · RLS activo  │
                         └────────────┬─────────────┘
                                      │
                    ┌─────────────────┴─────────────────┐
                    │     Backend CodeIgniter 4.7.4     │
                    │   173 rutas · 30 controladores    │
                    │  22 librerías · 24 modelos        │
                    └──┬──────────┬──────────┬──────────┘
                       │          │          │
          ┌────────────┴──┐  ┌────┴─────┐  ┌─┴──────────────┐
          │ Portal        │  │ Panel    │  │ API REST       │
          │ ciudadano     │  │ admin +  │  │ /api           │
          │ /portal       │  │ juez     │  │ (token Bearer) │
          └───────────────┘  └──────────┘  └────────┬───────┘
                                                   │
                                          ┌────────┴────────┐
                                          │ App móvil       │
                                          │ Expo / RN       │
                                          │ offline-first   │
                                          └─────────────────┘
```

### 2.1 Las tres ramas

| Rama | Qué resuelve | Superficie | Usuarios |
|---|---|---|---|
| **Rama ciudadana** | Trámites de movilidad de principio a fin | Portal web | Ciudadanos |
| **Rama administrativa** | Operación, dictamen, cobro y control | Panel web | Administrador, operador, juez |
| **Rama de campo** | Infracciones y evidencia | App móvil | Agentes y jefes de turno |

### 2.2 Subsistemas, autenticación y persistencia

| Subsistema | URL | Autenticación | Persistencia de sesión o credencial |
|---|---|---|---|
| Portal del ciudadano | `/portal`, `/auth` | Sesión con contraseña bcrypt | Sesión en base de datos (`ci_sessions`) |
| Panel administrativo | `/admin` | Sesión + rol + permiso de sección | Sesión en base de datos |
| Panel del juez | `/juez` | Sesión + rol `juez_calificador` | Sesión en base de datos |
| API móvil | `/api` | Token Bearer de agente | Hash del token en `agentes_transito_tokens` |
| Archivos locales firmados | `/archivos` | Firma HMAC-SHA256 con vigencia de 1 hora | No aplica |
| Hojas de pago | `/documentos/hoja-pago` | Sesión + permiso de propiedad del expediente | Sesión en base de datos |

### 2.3 Stack técnico

| Capa | Tecnología | Versión |
|---|---|---|
| Servidor | PHP | 8.2.25 |
| Framework | CodeIgniter | 4.7.4 |
| Base de datos | PostgreSQL (Supabase) | 15+ |
| Pruebas | PHPUnit | 10.5 |
| Despliegue | Vercel serverless | `vercel-php@0.7.4` |
| App móvil | React Native / Expo | 0.86.3 / 57.0.30 |
| App móvil | React / TypeScript | 19.2.3 / 6.0.3 |
| App móvil | expo-router | 57 |
| App móvil | @supabase/supabase-js | 2.117.2 |
| App móvil | netinfo | 12.0.1 |
| Interfaz web | Bootstrap 5 + Bootstrap Icons | — |

---

## PARTE I: Portal ciudadano y panel administrativo web

### 1. Arquitectura y tecnologías web

El portal y el panel son **la misma aplicación CodeIgniter**, diferenciadas por el prefijo de ruta y el filtro de rol. Ambas comparten:

- Los cinco layouts: `layouts/portal` (ciudadano), `layouts/admin` (panel), `layouts/juez` (juez), `auth/layout_auth` (formularios de acceso) y dos parciales reutilizables (`layouts/_ayuda`, `layouts/_estatus_badge`).
- El paginador propio `pager_bootstrap_full.php`, para que la paginación respete el estilo institucional.
- El helper `url_helper_custom.php`, que resuelve enlaces correctamente tanto en local como en Vercel.
- El catálogo de estatus y las insignias de color de `_estatus_badge.php`, para que un estado se vea igual en el portal, en el panel y en el panel del juez.

El panel administrativo tiene **barra lateral con jerarquía** y oculta las entradas que el operador no tiene habilitadas. El portal tiene **navegación inferior** en móvil, con `viewport-fit=cover` para pantallas con muesca.

### 2. Catálogo oficial de trámites digitalizados

Siete trámites, cada uno con su ficha oficial, su UR asociada y su controlador propio. Los montos provienen de las fichas oficiales actualizadas el 29-ene-2026 y aplicados por la migración `000033`; las tarifas del T-07 se multiplican por el número de camiones solicitado.

| Clave | Trámite | UR | Monto oficial | Criterio de la tarifa | Formulario | Controlador |
|---|---|---|---|---|---|---|
| `UR-TT-T-01` | Concesión de transporte público de ruta fija | UR-01 | $9,055.20 | `base` | `concesion_transporte_form` | `TramiteConcesionTransporteController` |
| `UR-TT-T-02` | Constancia de despintado | UR-02 | $64.90 | `base` | `constancia_despintado_form` | `TramiteDespintadoController` |
| `UR-TT-T-03` | Orden de plaqueo para cambio de unidad | UR-03 | $0.00 (sin costo) | `base` | `orden_plaqueo_form` | `TramiteOrdenPlaqueoController` |
| `UR-TT-T-04` | Permiso eventual de transporte | UR-04 | $156.94 | `base` | `permiso_eventual_form` | `TramitePermisoEventualController` |
| `UR-TT-T-05` | Permiso para cierre parcial o total de calle | UR-05 | $287.00 | `base` | `cierre_calle_form` | `TramiteCierreCalleController` |
| `UR-TT-T-06` | Cesión de concesión | — | $9,055.20 | `cesion_concesion_base` | `cesion_concesion_form` | `TramiteCesionConcesionController` |
| `UR-TT-T-07` | Permiso de carga y descarga | — | Desde $191.00 | `{tipo}_{periodo}` | `carga_descarga_form` | `TramiteCargaDescargaController` |

**Tarifas detalladas del UR-TT-T-07** (por un camión salvo indicación)

| Tipo de solicitante | Día | Mes | Semestre | Año |
|---|---|---|---|---|
| Particular | $191.00 | $1,151.00 | $5,752.00 | $11,503.00 |
| Empresa establecida (hasta 15 camiones) | $573.00 | $5,732.00 | $28,662.00 | $57,325.00 |

El monto final se obtiene multiplicando la tarifa del periodo por el número de camiones declarado (mínimo uno). Para empresa establecida, la tarifa ya cubre hasta 15 camiones y el factor se aplica sobre ese bloque.

**Documentación que solicita cada trámite**

| Trámite | Documentos obligatorios |
|---|---|
| UR-TT-T-01 | Acta de nacimiento o acta constitutiva; Constancia de Situación Fiscal; escritura; constancia de residencia municipal; constancia de no antecedentes penales; acta constitutiva y poder notarial cuando es persona moral |
| UR-TT-T-02 | Identificación oficial del titular; factura o documento de propiedad del vehículo |
| UR-TT-T-03 | Identificación oficial vigente; factura o carta factura del vehículo; comprobante de revista físico-mecánica; acta constitutiva y poder notarial cuando es persona moral |
| UR-TT-T-04 | Fondo de garantía o póliza de seguro, más los documentos de la unidad |
| UR-TT-T-05 | Identificación oficial del solicitante |
| UR-TT-T-06 | Según el supuesto: identificación del cedente y del cesionario; acta de nacimiento de ambos; acta de defunción o sentencia cuando el supuesto es sucesión; identificación y acta de nacimiento del beneficiario o del interesado |
| UR-TT-T-07 | Identificación oficial; tarjeta de circulación; comprobante de domicilio o documento de carga y descarga |

Todos los documentos se aceptan en PDF, JPG o PNG y hasta 10 MB. El sistema determina el tipo real por el contenido del archivo, no por su extensión; un archivo renombrado se rechaza.

**Bandera del UR-TT-T-06.** El trámite de cesión está condicionado por la variable `APP_ENABLE_UR_TT_T_06`. Mientras esté apagada, el portal no ofrece su acceso, aunque el panel conserva y muestra los expedientes históricos. La bandera existe porque el procedimiento de pago de los derechos de cesión depende de una confirmación de la Tesorería Municipal.

### 3. Flujo operativo y ciclo de vida de las solicitudes

Todos los expedientes siguen el mismo patrón: un estado de recepción, una etapa de revisión o dictamen, una autorización de pago, el pago y la emisión del documento final. Lo que cambia entre trámites es el número de estaciones intermedias.

**Patrón general**

```
Recibido → revisión o dictamen → (prevención ⇄ revisión) → autorización
        → Pago pendiente → Pagado → documento emitido → vigencia → vencimiento
```

**Etapas y quién las provoca**

| Etapa | Quién la provoca | Efecto en el expediente | Efecto en cobros |
|---|---|---|---|
| Recepción | Ciudadano (portal) u operador (ventanilla) | Crea el expediente en estado inicial del trámite | Ninguno |
| Revisión documental | Operador o administrador | Puede pedir prevención o avanzar | Ninguno |
| Prevención | Operador | Devuelve al ciudadano para subsanar; es **regresable** | Ninguno |
| Dictamen o evaluación técnica | Operador o administrador | Avanza a la siguiente estación o rechaza | Ninguno |
| Autorización de pago | Operador o administrador | Deja el expediente en «Pago pendiente» | **Se crea el cobro pendiente** |
| Pago | Ciudadano (en línea) o personal (ventanilla, tesorería, juez) | Pasa a «Pagado» | El cobro pasa a aprobado y se emite recibo |
| Emisión | Operador o administrador | Emite constancia, permiso o título | Ninguno |
| Vigencia y vencimiento | El sistema, por fecha | «Vigente» → «Vencido» | Ninguno |
| Rechazo | Operador o administrador | Estado terminal, con comentario obligatorio | El cobro pendiente **se cancela** |

**Máquina de estados por trámite**

| Trámite | Estados por los que pasa |
|---|---|
| UR-TT-T-01 | Recibido → En revisión → Evaluación comparativa → Seleccionado → Pago pendiente → Pagado → Título emitido. También puede caer en No seleccionado. |
| UR-TT-T-02 | Recibido → Cita agendada → Verificado → Pago pendiente → Pagado → Constancia emitida |
| UR-TT-T-03 | Recibido → En revisión → Aprobado |
| UR-TT-T-04 | Recibido → En revisión documental → Documentos completos → En estudio técnico → Dictamen favorable → Pendiente de inspección → Pendiente de revista mecánica → Seguro pendiente de validación → Autorizado para pago → Pago pendiente → Pagado → Permiso emitido |
| UR-TT-T-05 | Recibido → En validación → Pago pendiente → Pagado → Permiso emitido |
| UR-TT-T-06 | Recibido → En revisión documental → Dictaminado aprobado → Pago pendiente → Concluido |
| UR-TT-T-07 | Recibido → Pago pendiente → Pagado → Permiso emitido → Vigente → Vencido |

Reglas que valen para todos: **una transición no declarada es imposible** y el sistema la rechaza aunque llegue por un formulario manipulado; «Rechazado» es terminal y exige comentario; «No seleccionado» del T-01 es terminal y no genera cobro; la prevención del T-04 y del T-06 sí puede regresar a revisión documental, porque el ciudadano puede subsanar.

**Flujo presencial.** Un expediente puede nacer en ventanilla en tres pasos: identificar al ciudadano por CURP, elegir el trámite y capturar el formulario con los datos precargados. Si el ciudadano no existe se crea una cuenta **sin acceso al portal**; si ya existe, el sistema vincula el expediente a su cuenta. Antes de crear una cuenta nueva, el sistema advierte de homonimia para evitar duplicados. El alta presencial recibe el mismo tratamiento que un trámite en línea: mismo folio, misma máquina de estados, mismo cobro.

### 4. Módulos del panel administrativo web

El panel se organiza en una barra lateral con **14 entradas principales**, algunas con subenlaces. El detalle de cada pantalla —qué muestra, qué campos tiene, qué botones ofrece y qué mensajes emite— está en [DOC_PORTAL_PANEL.md](./DOC_PORTAL_PANEL.md) §5.

| # | Entrada del menú | Subenlaces | Qué se hace ahí | Permiso del operador |
|---|---|---|---|---|
| 1 | Panel | — | Indicadores, alertas y últimos movimientos | siempre disponible |
| 2 | Solicitudes | Bandeja, Nueva en ventanilla | Revisar, dictaminar, evaluar, validar y cambiar estatus | `solicitudes` |
| 3 | Cobros | Listado, Banco, Corte de caja, Condonaciones | Cobrar trámites y multas, registrar pagos bancarios, condonar y cerrar caja | `cobros` |
| 4 | Usuarios | Listado, Nuevo | Alta y edición de cuentas de ciudadano | `usuarios` |
| 5 | Tarifas | — | Catálogo de tarifas por trámite y criterio | `tarifas` |
| 6 | Concesiones | — | Padrón de concesiones de transporte | `concesiones` |
| 7 | Convocatorias | — | Publicar, evaluar y seleccionar ganadores de la UR-01 | `convocatorias` |
| 8 | Formatos | — | Formatos descargables del portal | `formatos` |
| 9 | Documentos | — | Almacén de archivos recibidos | `documentos` |
| 10 | Verificaciones | — | Agenda de inspecciones físicas | `verificaciones` |
| 11 | App Tránsito | Configuración, Boletas, Catálogo | Parámetros y UMA, agentes, boletas de campo y catálogo de faltas | `app_transito_config`, `app_transito_boletas`, `app_transito_catalogo` |
| 12 | Sesiones App | — | Estado de los accesos de la app móvil | `app_transito_sesiones` |
| 13 | Auditoría | — | Bitácora de acciones del sistema | `auditoria` |
| 14 | *(solo administrador)* | Permisos del operador, Desglose global | Habilitar secciones al operador y ver todas las métricas del sistema | exclusivo del administrador |

**El panel del juez** es una superficie distinta con su propio diseño y solo tres entradas: boletas pendientes, cobros y corte de caja. El juez **califica y cobra**; no administra catálogos, usuarios ni configuración. Su detalle está en [DOC_PORTAL_PANEL.md](./DOC_PORTAL_PANEL.md) §6.

**El módulo de cobros**, presente en el panel administrativo y en el del juez con el mismo código, cubre doce capacidades funcionales: cobro de trámites y de multas desde un solo listado; folios correlativos de cobro y de recibo; historial de movimientos por cobro; envío a completar cuando falta documentación; cancelación con motivo; registro de pagos en BanBajío por referencia bancaria; búsqueda por referencia; condonación del recargo con autorización; bitácora de condonaciones; corte de caja por rango de fechas; exportación del corte en CSV; y hoja de pago imprimible con desglose y lugares de pago.

**El desglose global** es exclusivo del administrador: reúne todas las métricas del sistema —expedientes por estatus y por trámite, origen del trámite, serie mensual, tiempos de atención, vigencias, documentos, cuentas, auditoría, actividad de la app de tránsito, verificaciones, estado de los catálogos y alertas operativas— con navegación directa a la lista ya filtrada de cada una.

---

## PARTE II: App móvil para agentes de tránsito

La especificación completa de la app —siete pantallas, cinco componentes, tres contextos, cuatro servicios y los contratos de sincronización— está en [DOCUMENTACION_APP_TRANSITO.md](./DOCUMENTACION_APP_TRANSITO.md). Aquí se resume qué hace y por qué existe.

### 1. Arquitectura y tecnologías móviles

La app está construida con **Expo SDK 57.0.30** sobre **React Native 0.86.3**, **React 19.2.3** y **TypeScript 6.0.3**, con `expo-router 57` para la navegación por archivos. Se compila para Android mediante EAS y también se exporta a **web** para servirse bajo `/agentes`, de modo que un agente con navegador pero sin tienda de aplicaciones pueda usarla.

### 2. Flujo operativo del agente en campo

```
Inicio de sesión (placa + contraseña) → token Bearer
        │
        ▼
Descarga y cachea catálogo de faltas y parámetros
        │
        ▼
Levanta la boleta: datos del vehículo, del infractor,
selección de faltas del catálogo y hasta tres fotografías
        │
        ▼
Guarda en el dispositivo (funciona sin red)
        │
        ▼
Al recuperar señal: sube evidencias y sincroniza la boleta
        │
        ▼
La boleta llega al panel como «recibida»
        │
        ▼
El juez la califica y se genera el cobro
```

### 3. Características clave

| Característica | Por qué importa |
|---|---|
| **Funciona sin conexión** | En calle la señal es intermitente. La app guarda la boleta en el dispositivo y la envía después; nunca se pierde una infracción por falta de red. |
| **Catálogo local de respaldo** | El catálogo de faltas viaja compilado en la app además de descargarse del servidor, de modo que un primer arranque sin red sigue permitiendo levantar una boleta. |
| **Fotografías obligatorias** | Cada boleta exige hasta tres evidencias: placa, contexto y documento. El parámetro `fotos_obligatorias` y el modo estricto deciden cuántas y si se puede guardar sin ellas. |
| **Sondeo de conectividad** | La app pregunta `HEAD /` para saber si hay red. El backend respondió a ese sondeo con una ruta explícita porque CI4 no resuelve `HEAD` contra las rutas `GET`, y sin ella la app se declaraba sin conexión para siempre. |
| **Sesión en almacenamiento seguro** | El token se guarda cifrado en el dispositivo (`expo-secure-store`), no en texto plano. |
| **Almacenamiento en caché con versiones** | Catálogo y parámetros se cachean con claves versionadas, de modo que un cambio de estructura no lee datos del formato anterior. |
| **Respaldo directo a Supabase** | Si la API no responde, la app intenta leer el catálogo y los parámetros directamente de Supabase. Esto depende por completo de que las políticas RLS estén activas: la clave pública de Supabase no protege nada por sí sola. |
| **El servidor manda sobre el estado** | La app puede decir lo que quiera sobre el estado de una boleta; el backend siempre la deja en «recibida». |

---

## PARTE III: Base de datos unificada y esquema de seguridad

El diccionario completo de las 26 tablas, con columnas, llaves y políticas, está en [DOCUMENTACION_BACKEND.md](./DOCUMENTACION_BACKEND.md) §11. Aquí se presenta el modelo por dominios y el resumen.

### 1. Modelo de entidad-relación por dominios

**Identidad** — quién es quién y qué puede hacer

```
roles ◄── user_roles ──► users
                          ▲
                          │ ciudadano_id, usuario_id, cobrado_por, created_by
                          └──────────────┐
agentes_transito ◄── agentes_transito_tokens
```

**Expediente** — el trámite y su historia

```
users ◄──── solicitudes ────► concesiones
              │      └──────► convocatorias
              ├── solicitud_datos        (formulario clave-valor)
              ├── documentos             (archivos + hash)
              ├── historial_estatus      (traza de cada cambio)
              └── verificaciones_fisicas (agenda de inspección)
```

**Catálogos** — lo que se administra y no cambia todos los días

```
tarifas · concesiones · convocatorias · formatos_tramite
catalogo_infracciones · catalogo_infracciones_app · boleta_checklist_item
```

**Cobros** — el dinero y su rastro

```
solicitudes ──┐
              ├──► cobros ──► cobro_movimientos
boletas_infracciones ─┘   │
        │                 └──► boleta_calificaciones ──► boleta_calificacion_detalles
        └──► agentes_transito
```

**Campo** — tránsito

```
agentes_transito ──► boletas_infracciones ──► boleta_calificaciones
                          │
                          └──► agentes_transito_tokens
```

**Plataforma** — configuración y fiscalización

```
parametros_app · parametros_sistema · auditoria · ci_sessions
```

### 2. Resumen de las 26 tablas

| Tabla | Propósito en una línea | PK |
|---|---|---|
| `roles` | Catálogo de los cinco roles del sistema | `id` |
| `users` | Cuentas de ciudadano y de personal interno | `id` |
| `user_roles` | Asignación de roles por usuario | `id` |
| `concesiones` | Padrón de títulos de concesión de transporte | `id` |
| `solicitudes` | Expediente del trámite con folio, estatus y monto | `id` |
| `solicitud_datos` | Respuestas del formulario de cada trámite | `id` |
| `documentos` | Archivos del expediente con su huella SHA-256 | `id` |
| `tarifas` | Catálogo de precios por trámite y criterio | `id` |
| `historial_estatus` | Traza completa de cambios de estado | `id` |
| `auditoria` | Bitácora de acciones sensibles del sistema | `id` |
| `convocatorias` | Publicaciones de la UR-01 con sus bases | `id` |
| `verificaciones_fisicas` | Agenda de inspecciones y sus resultados | `id` |
| `formatos_tramite` | Formatos descargables del portal | `id` |
| `catalogo_infracciones` | Catálogo normativo de faltas | `id` |
| `parametros_sistema` | Parámetros técnicos no editables | `id` |
| `boleta_checklist_item` | Casillas del checklist de la boleta física | `id` |
| `agentes_transito` | Agentes y jefes de turno de la app móvil | `id` |
| `parametros_app` | Parámetros operativos de la app y permisos del operador | `id` |
| `catalogo_infracciones_app` | Catálogo de faltas que consume la app | `id` |
| `boletas_infracciones` | Boletas levantadas en campo | `id` |
| `agentes_transito_tokens` | Hashes de los tokens de acceso de la app | `id` |
| `cobros` | Cobros de trámites y multas con su desglose | `id` |
| `cobro_movimientos` | Historial de acciones sobre cada cobro | `id` |
| `boleta_calificaciones` | Calificación judicial de una boleta | `id` |
| `boleta_calificacion_detalles` | Cada falta calificada con su UMA asignada | `id` |
| `ci_sessions` | Sesiones web, necesarias en serverless | `id` |

Además existen tres tablas heredadas de una etapa anterior (`ciudadanos`, `administradores`) y la tabla de control de migraciones de Supabase (`migrations`). El código actual no las usa.

### 3. Infraestructura de archivos y almacenamiento

El sistema usa **almacenamiento dual**, decidido en tiempo de ejecución:

| Modo | Cuándo se usa | Dónde guarda | Cómo se sirve |
|---|---|---|---|
| **Supabase Storage** | Producción con Supabase configurado | Buckets `documentos`, `formatos`, `ine` | URL firmada con vigencia de 1 hora |
| **Disco local** | Desarrollo, o respaldo cuando Supabase falla | `writable/uploads/storage/{bucket}/` | `/archivos/{token}` con firma HMAC-SHA256 |

Tres buckets cubren todos los archivos del sistema. Los documentos de expediente, los formatos descargables y las imágenes de INE van a Supabase Storage; solo las fotografías de evidencia de las boletas se guardan en `public/uploads/infracciones/{folio}/`, porque la app las referencia directamente y necesita una URL estable.

**Reglas de seguridad de los archivos**

- El tipo se determina por el contenido, nunca por la extensión del nombre.
- Los documentos se almacenan con un nombre UUID; el nombre original solo se conserva para mostrarlo.
- Cada documento guarda el hash SHA-256 de su contenido, lo que permite detectar si el archivo cambió después de subirse.
- Una descarga nunca expone la ruta interna: el controlador genera la URL firmada y redirige.
- La URL local caduca en una hora y la firma cubre bucket, ruta y vencimiento; no se puede adivinar ni reutilizar en otro archivo.

### 4. Control de acceso: roles y permisos configurables

El control de acceso ocurre en **cinco filtros** encadenados, no en la vista:

| Filtro | Decide |
|---|---|
| `auth` | Si hay sesión iniciada |
| `role` | Si el usuario tiene alguno de los roles exigidos por la ruta |
| `permisoOperador` | Si el operador de ventanilla tiene habilitada esa sección |
| `adminRedirect` | Si el personal interno debe ser devuelto a su propia superficie |
| `agenteToken` | Si el token de la app móvil es válido |

**Los 14 permisos configurables del operador** se agrupan en cuatro bloques funcionales:

| Bloque | Permisos |
|---|---|
| Operación de expedientes | `solicitudes`, `documentos`, `verificaciones` |
| Dinero | `cobros` |
| Administración | `usuarios`, `tarifas`, `concesiones`, `convocatorias`, `formatos`, `auditoria` |
| App de tránsito | `app_transito_config`, `app_transito_boletas`, `app_transito_catalogo`, `app_transito_sesiones` |

**Cuatro invariantes que definen el modelo de autorización**

1. **El administrador nunca se filtra.** Tiene acceso total por rol, sin excepción.
2. **Las bajas definitivas no son configurables.** Eliminar usuarios, agentes, boletas, faltas o convocatorias es exclusivo del administrador, al margen de los permisos.
3. **Cobrar no es administrar.** La ventanilla puede cobrar, completar y cancelar un cobro, pero condonar un recargo exige además una autorización verificada en el servicio.
4. **Sin configuración guardada, todo está permitido.** Es el comportamiento inicial: un despliegue nuevo no deja al operador sin acceso.

El efecto de retirar un permiso es doble: la entrada desaparece del menú **y** el filtro devuelve al operador a su panel si escribe la dirección a mano. Nunca es solo cosmético.

### 5. Interfaz web responsiva

El portal y el panel están construidos sobre Bootstrap 5 con un sistema de tokens propio (colores institucionales, tipografía, espaciados). El portal incorpora **navegación inferior en móvil** y `viewport-fit=cover` para pantallas con muesca; el panel usa una **gaveta lateral** que se colapsa en pantallas pequeñas.

Se respetan las preferencias de accesibilidad del sistema operativo: `prefers-reduced-motion` desactiva animaciones, y las hojas de pago tienen una hoja de estilo de impresión que oculta la navegación y respeta los saltos de página.

---

## PARTE IV: Guía de instalación, configuración y despliegue

### 1. Requisitos previos

| Requisito | Versión mínima | Nota |
|---|---|---|
| PHP | 8.1 (probado con 8.2.25) | El repositorio incluye `php82/` para Windows, de modo que no hace falta instalar PHP en el sistema |
| Extensiones PHP | `pdo_pgsql`, `pgsql`, `mbstring`, `intl`, `json`, `curl` | Imprescindibles para Supabase y para los acentos |
| Composer | 2.x | Instalación de dependencias del backend |
| Node.js | 20 LTS | Compilación de la app móvil |
| PostgreSQL | 15+ | Provisto por Supabase; no requiere instalación local |
| Git | 2.x | Control de versiones |

### 2. Instalación local

**Paso 1 — Clonar el repositorio**

```
git clone https://github.com/soportealoraapp/App-Residencias-Ventanilla.git
cd App-Residencias-Ventanilla
```

**Paso 2 — Instalar dependencias del backend**

```
./php82/php.exe composer.phar install
```

En un entorno con PHP y Composer en el `PATH`, basta `composer install`.

**Paso 3 — Configurar variables de entorno**

Copiar `.env.example` a `.env` y completar las secciones `DATABASE`, `SUPABASE PROJECT` y `ENCRYPTION`. El archivo de ejemplo está comentado por sección y acepta tres formatos de variable de base de datos, de modo que el mismo archivo sirve en local y en Vercel.

Dos advertencias:

- **Nunca versionar `.env`.** Las claves de servicio de Supabase omiten las políticas RLS.
- **La clave de cifrado debe generarse localmente** con `./php82/php.exe spark key:generate` y conservarse: si cambia, los valores cifrados previamente dejan de descifrarse.

**Paso 4 — Aplicar migraciones y datos base**

```
./php82/php.exe spark migrate
./php82/php.exe spark db:seed DatabaseSeeder
./php82/php.exe scripts/verificar_esquema_bd.php
```

El tercer comando compara el esquema real con el del repositorio. Si reporta tablas faltantes, no se debe continuar: un esquema divergente es la causa más común de fallos intermitentes.

**Paso 5 — Compilar la app móvil para `/agentes` (opcional)**

```
powershell -File scripts/exportar_agentes_web.ps1
```

Usar el script, no `expo export` suelto: además de exportar, vuelve a copiar las tipografías `.ttf` y traduce el shell HTML al español. Si queda texto en inglés, el script falla a propósito.

**Paso 6 — Levantar el servidor de desarrollo**

```
./php82/php.exe spark serve
```

El sistema queda disponible en `http://localhost:8080`.

### 3. Despliegue en producción (Vercel)

El despliegue es serverless. Tres archivos gobiernan el comportamiento:

| Archivo | Papel |
|---|---|
| `vercel.json` | Tabla de reescritura de rutas y cabeceras de seguridad |
| `api/index.php` | Punto de entrada de la función serverless; equivalente a `public/index.php` |
| `scripts/vercel-install.sh` | Instalación de dependencias en el entorno de compilación |

**Tres diferencias obligadas respecto de un servidor tradicional**

1. **Puerto de base de datos 6543, no 5432.** Vercel bloquea las conexiones salientes al puerto directo de PostgreSQL. La variable `POSTGRES_URL` debe apuntar al *connection pooler* de Supabase (PgBouncer). Un puerto equivocado no produce un error de credenciales: produce un tiempo de espera agotado.
2. **Sesiones en base de datos.** El contenedor serverless no conserva el sistema de archivos entre invocaciones, así que la sesión se guarda en la tabla `ci_sessions`.
3. **Lista blanca de archivos estáticos.** Cualquier recurso nuevo que no esté contemplado en la tabla de reescritura de `vercel.json` no se sirve desde el sistema de archivos: lo recibe CodeIgniter y responde 404. Cuando una imagen o una tipografía «desaparece» en producción pero se ve en local, el problema está en esa tabla, no en el HTML.

Además, `api/index.php` corrige `SCRIPT_NAME` y `PHP_SELF` antes de arrancar el framework: Vercel los entrega como `/api/index.php` y CodeIgniter recortaría ese prefijo de todas las direcciones, rompiendo el enrutado.

**Advertencia sobre escritura en disco.** En serverless el disco es efímero. Cualquier archivo escrito en `writable/` o en `public/uploads/` desde el contenedor puede perderse en el siguiente despliegue. Por eso los documentos de expediente van a Supabase Storage y solo las evidencias de boleta usan la ruta pública local.

### 4. Datos de demostración

| Cuenta | Usuario | Contraseña | Superficie |
|---|---|---|---|
| Administrador | `admin` | `12345678` | Panel administrativo |
| Operador de ventanilla | `operador1` | `12345678` | Panel administrativo |
| Ciudadano | `ciudadano` | `12345678` | Portal |
| Juez calificador | `juez1` | `12345678` | Panel del juez |
| Agente de tránsito | `AGT-204` | `transito2026` | App móvil |
| Agente de tránsito | `AGT-107` | `transito2026` | App móvil |
| Jefe de turno | `JEF-001` | `transito2026` | App móvil |
| Ciudadanos de demostración | los genera `DemoFuncionamientoSeeder` | `Demo2026` | Portal |

**Estas cuentas existen solo para demostración.** Antes de un despliegue real deben eliminarse o rotarse sus contraseñas.

El seeder del juez calificador adicional (`JuezCalificadorDemoSeeder`) no crea nada si no se le proporcionan las variables `JUEZ_DEMO_USERNAME`, `JUEZ_DEMO_EMAIL` y `JUEZ_DEMO_PASSWORD`; la contraseña debe tener al menos ocho caracteres con letra y dígito.

### 5. Verificación con pruebas automatizadas

| Bloque | Comando | Resultado medido el 29-oct-2026 |
|---|---|---|
| Backend PHP | `./php82/php.exe vendor/bin/phpunit` | **438 casos, 1 876 aserciones, 43 clases**: OK |
| Catálogo de rutas | `./php82/php.exe spark routes` | **173 rutas** registradas |
| Esquema de base | `./php82/php.exe scripts/verificar_esquema_bd.php` | 26 tablas definidas, 29 existentes, resultado correcto |
| App móvil, flujo de autenticación | `node tests/auth-flow.test.cjs` | **18 de 18** correctos |
| App móvil, tipos | `npx tsc --noEmit` | sin errores |
| Verificación integral local | `scripts/test_local.ps1` | ejecuta los bloques anteriores en orden |

La suite del backend cubre cobros, condonación, corte de caja, calificación de boletas, hoja de pago, máquina de estados de los siete trámites, cálculo de montos con UMA, tarifario, permisos del operador, alta presencial, validación UR-05, panel del juez, API móvil, renderizado de vistas y dos pruebas de infraestructura: que todo enlace estático de las vistas resuelva contra el mapa de rutas, y que el enrutado serverless funcione.

El desglose por clase está en [DOCUMENTACION_BACKEND.md](./DOCUMENTACION_BACKEND.md) §13.3.

### 6. Estado conocido y pendientes

| Tema | Estado | Acción prevista |
|---|---|---|
| Desfase de migraciones | CI4 en `000033`, Supabase SQL en `0018` | Documentado con procedimiento de sincronización; no se corrige en esta actualización porque el esquema coincide |
| Pago en línea | `BanbajioMockGateway` es un simulador | Se sustituirá cuando la Tesorería Municipal formalice la integración; los controladores no cambiarán |
| Trámite de cesión (UR-TT-T-06) | Deshabilitado por bandera | Se habilita cuando se confirme el procedimiento de pago de derechos |
| Tablas heredadas | `ciudadanos`, `administradores` sin uso | Se conservan por sus datos históricos |
| Mediciones responsivas | Registradas con su fecha de medición | Deben repetirse con capturas nuevas si se exige evidencia actualizada |

---

## 5. Metodología de trabajo y división del equipo

El proyecto se desarrolló por fases con entregables verificables: análisis del marco normativo, diseño del modelo de datos, construcción del backend, construcción de las tres superficies, módulo de cobros y documentación. Cada fase cerró con pruebas automatizadas y evidencia de ejecución.

La metodología completa —fases, criterios de aceptación por entrega, evidencia por commit y trazabilidad con el informe de residencia— está en [DOC_METODOLOGIA.md](./DOC_METODOLOGIA.md). Ese documento es la referencia para cualquier evaluación que requiera reconstruir qué se hizo, cuándo y con qué comprobación.

Resumen del estado por fase:

| Fase | Estado | Evidencia |
|---|---|---|
| Análisis y diseño | cerrada | Modelo de datos y catálogo de trámites |
| Backend y base de datos | cerrada | 26 tablas, 173 rutas, suite de 438 casos |
| Portal ciudadano | cerrada | Siete trámites operables de principio a fin |
| Panel administrativo | cerrada | 14 módulos con permisos configurables |
| Panel del juez | cerrada | Calificación y cobro de multas |
| App móvil de tránsito | cerrada | Offline-first, sincronización y respaldo local |
| Módulo de cobros (COB-01 a COB-12) | cerrada | Matriz de doce capacidades con evidencia de ruta |
| Documentación integral | cerrada | Seis documentos con cabecera de estado y cifras medidas |

**División del trabajo.** El proyecto se organizó en cuatro frentes: base de datos y backend, portal y panel web, app móvil de tránsito, y documentación y pruebas. Los frentes compartieron un único contrato: el catálogo de trámites y la máquina de estados, definidos una sola vez en el backend para que las tres superficies no pudieran divergir.

---

## 6. Documentos complementarios

| Documento | Qué responde | A quién sirve |
|---|---|---|
| [README.md](./README.md) | Qué es el sistema, cómo arrancarlo, cuentas de demostración, pruebas e índice documental | Todo el equipo y el evaluador |
| [DOCUMENTACION_GENERAL.md](./DOCUMENTACION_GENERAL.md) | Este documento: visión macro, actores, glosario, trámites, ciclo de vida e instalación | Dirección, jurado, nuevo desarrollador |
| [DOCUMENTACION_BACKEND.md](./DOCUMENTACION_BACKEND.md) | Única fuente de verdad técnica: configuración, controladores, modelos, librerías, filtros, API, rutas, migraciones y diccionario de datos | Backend y mantenimiento |
| [DOC_PORTAL_PANEL.md](./DOC_PORTAL_PANEL.md) | Ficha funcional de cada pantalla web: qué muestra, campos, acciones, validaciones, estados, mensajes y casos límite | Operación, experiencia de usuario y control de calidad |
| [DOCUMENTACION_APP_TRANSITO.md](./DOCUMENTACION_APP_TRANSITO.md) | App móvil: pantallas, componentes, contextos, offline-first, contratos de sincronización y compilación | Móvil y operación en campo |
| [DOC_METODOLOGIA.md](./DOC_METODOLOGIA.md) | Metodología, fases, evidencia y trazabilidad con el informe de residencia | Residencia profesional |
| [REPORTE_COB-10_2026-10-24.md](./REPORTE_COB-10_2026-10-24.md) | Reporte técnico de la fase COB-10 | Referencia histórica de la fase de cobros |

### Cómo leer esta documentación

Si busca **entender el sistema**, lea este documento en orden.
Si busca **modificar el backend**, vaya directo a [DOCUMENTACION_BACKEND.md](./DOCUMENTACION_BACKEND.md).
Si busca **operar una pantalla**, consulte la ficha correspondiente en [DOC_PORTAL_PANEL.md](./DOC_PORTAL_PANEL.md).
Si busca **levantar el sistema**, vaya a la PARTE IV de este documento y luego al [README.md](./README.md).
Si busca **reconstruir la historia del proyecto**, consulte [DOC_METODOLOGIA.md](./DOC_METODOLOGIA.md).

En todo el corpus se aplica la misma regla: **el código es la fuente de verdad**, y toda cifra lleva su método de medición y su fecha.
