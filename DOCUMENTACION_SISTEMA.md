# Documentación Integral del Sistema: Ventanilla Digital de Movilidad y Transporte

**Municipio de Uriangato, Guanajuato**  
**Dirección de Movilidad y Transporte Municipal**  
**Versión del Sistema:** 2.0.0 (Producción / Vercel + Supabase)  
**Fecha de Actualización:** Agosto 2026

---

## 1. Resumen Ejecutivo y Propósito
La **Ventanilla Digital de Movilidad y Transporte** es una plataforma web gubernamental moderna diseñada para digitalizar, transparentar y agilizar los 7 trámites y servicios de transporte público, vialidad y carga que presta la Dirección de Movilidad del Municipio de Uriangato, Gto. 

Permite a los ciudadanos y concesionarios:
- Consultar requisitos oficiales y tarifas de derechos vigentes.
- Registrar solicitudes en línea con expedientes digitales validados.
- Agendar citas de inspección física vehicular (ej. desincorporación/despintado).
- Dar seguimiento en tiempo real al estatus de sus trámites mediante folios únicos institucionales.
- Realizar pagos de derechos de forma segura (BanBajío / ventanilla).
- Descargar documentos oficiales emitidos con firma electrónica y código QR de validación pública.

A los servidores públicos (Administradores y Operadores de Ventanilla) les permite:
- Gestionar un buzón unificado de solicitudes con filtros avanzados por trámite, fecha y estatus.
- Realizar cotejo documental, emitir observaciones o dictámenes técnicos.
- Administrar el Padrón Oficial de Concesiones y el Catálogo Dinámico de Tarifas.
- Registrar verificaciones físicas y generar resoluciones administrativas con trazabilidad y auditoría completa.

---

## 2. Pila Tecnológica (Tech Stack)

| Capa | Tecnología | Descripción |
|---|---|---|
| **Backend** | **PHP 8.2+ / CodeIgniter 4.5+** | Framework MVC robusto, rápido y seguro con tipado estricto (`strict_types=1`). |
| **Base de Datos** | **PostgreSQL 15+ (Supabase)** | Base de datos relacional principal con soporte de conexión transaccional y pooler (PgBouncer). Compatible también con MySQL/MariaDB. |
| **Frontend** | **HTML5 Semántico + Bootstrap 5.3 + Vanilla CSS** | Interfaz adaptativa *mobile-first*, moderna, accesible y de alto contraste visual. |
| **Iconografía y Tipografía** | **Bootstrap Icons 1.11+ & Google Font "Inter"** | Diseño visual institucional, claro y estilizado. |
| **Criptografía & Seguridad** | **SHA-256 + CSRF + XSS + Password Hash** | Hashing de documentos digitales, prevención contra falsificación de solicitudes e inyección SQL. |
| **Despliegue** | **Vercel Serverless + Supabase Database** | Arquitectura Serverless escalable con sesiones centralizadas en base de datos (`ci_sessions`). |
| **Pruebas Automatizadas** | **PHPUnit 10.5+** | Suite integral de pruebas unitarias y de integración para controladores, modelos y validaciones. |

---

## 3. Arquitectura del Software

El sistema implementa una arquitectura **MVC (Modelo - Vista - Controlador)** con capas de soporte especializadas:

```
                      ┌───────────────────────────┐
                      │    Navegador / Cliente    │
                      │  (Ciudadano / Operador)   │
                      └─────────────┬─────────────┘
                                    │ HTTP / HTTPS (CSRF Protegido)
                                    ▼
                      ┌───────────────────────────┐
                      │    Filtros de Seguridad   │
                      │ (AuthFilter, AdminFilter) │
                      └─────────────┬─────────────┘
                                    │
                                    ▼
                      ┌───────────────────────────┐
                      │        Controlador        │
                      │   (Portal / Admin / Auth) │
                      └──────┬─────────────┬──────┘
                             │             │
              ┌──────────────┘             └──────────────┐
              ▼                                           ▼
┌───────────────────────────┐               ┌───────────────────────────┐
│     Librerías / Servicios │               │    Modelos de Entidad     │
│ - DocumentoUploader       │               │ - SolicitudModel          │
│ - TarifarioService        │               │ - DocumentoModel          │
│ - FeatureFlags            │               │ - ConcesionModel          │
│ - AuditoriaModel          │               │ - TarifaModel             │
└─────────────┬─────────────┘               └─────────────┬─────────────┘
              │                                           │
              └─────────────────────┬─────────────────────┘
                                    ▼
                      ┌───────────────────────────┐
                      │ Base de Datos PostgreSQL  │
                      │  (Supabase / Pooler 6543) │
                      └───────────────────────────┘
```

### Roles de Acceso (RBAC)
1. **`ciudadano`**: Usuario particular o concesionario que registra y consulta sus propios trámites, sube documentación, agenda citas y realiza pagos.
2. **`operador_ventanilla`**: Servidor público que recibe expedientes, revisa documentos, agenda inspecciones, registra dictámenes de verificación y valida pagos.
3. **`administrador`**: Funcionario con facultades totales: resuelve solicitudes, emite oficios finales, administra el catálogo de tarifas, gestiona el padrón de concesiones y consulta registros de auditoría.

---

## 4. Los 7 Trámites Municipales (UR-TT-T-01 al UR-TT-T-07)

El sistema organiza los 7 trámites en una secuencia numérica estricta:

```
┌───────────┐  ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌───────────┐
│ UR-TT-T-01│  │ UR-TT-T-02│  │ UR-TT-T-03│  │ UR-TT-T-04│  │ UR-TT-T-05│  │ UR-TT-T-06│  │ UR-TT-T-07│
│ Concesión │─▶│Despintado │─▶│  Plaqueo  │─▶│  Permiso  │─▶│ Cierre de │─▶│ Cesión de │─▶│  Carga y  │
│Transporte │  │y Desincorp│  │  Oficial  │  │ Eventual  │  │   Calle   │  │ Concesión │  │ Descarga  │
└───────────┘  └───────────┘  └───────────┘  └───────────┘  └───────────┘  └───────────┘  └───────────┘
```

### 1. `UR-TT-T-01`: Concesión de Transporte Público
- **Propósito**: Postulación y evaluación técnica comparativa ante convocatorias públicas para nuevas concesiones o rutas de transporte urbano/suburbano.
- **Tabla específica**: `postulaciones_concesion` y `convocatorias`.
- **Documentos**: Identificación, comprobante de domicilio, propuesta técnica, capacidad financiera, constancia de no antecedentes.
- **Flujo**: Publicación de Convocatoria → Postulación Ciudadana → Evaluación Comparativa → Dictamen → Asignación de Concesión.

### 2. `UR-TT-T-02`: Constancia de Despintado y Retiro de Franjas
- **Propósito**: Trámite obligatorio para desincorporar un vehículo del servicio de transporte público (baja de unidad, sustitución o venta).
- **Tabla específica**: `tramites_despintados` y `verificaciones_fisicas`.
- **Documentos**: Identificación oficial, factura/título del vehículo.
- **Flujo**: Registro de solicitud → Agendado de cita física en patio municipal → Inspección visual de retiro de cromática → Dictamen Aprobado → Emisión de Constancia.

### 3. `UR-TT-T-03`: Orden de Plaqueo
- **Propósito**: Emisión del oficio de autorización dirigido a la Secretaría de Finanzas para el alta de placas del servicio público de transporte.
- **Tabla específica**: `tramites_orden_plaqueo`.
- **Documentos**: Título de concesión, factura de la unidad, póliza de seguro de viajero vigente, verificación vehicular, INE del titular.
- **Flujo**: Solicitud → Cotejo contra Padrón Oficial de Concesiones → Validación de vigencia de póliza → Emisión de Orden de Plaqueo.

### 4. `UR-TT-T-04`: Permiso Eventual de Transporte
- **Propósito**: Autorización temporal para prestar servicio extraordinario de transporte ante contingencias, eventos especiales o reparación de unidades titulares.
- **Tabla específica**: `tramites_permisos_eventuales`.
- **Documentos**: Solicitud por escrito, proyecto de unidades, propuesta de frecuencia, acta constitutiva/identificación, póliza de seguro.
- **Flujo**: Registro de justificación → Dictamen de necesidad de servicio → Determinación de vigencia → Emisión de Permiso.

### 5. `UR-TT-T-05`: Permiso para Cierre de Calle
- **Propósito**: Permiso municipal para el cierre temporal total o parcial de vías públicas por obras, eventos cívicos, deportivos, culturales o particulares.
- **Tabla específica**: `tramites_cierre_calle`.
- **Documentos**: Identificación oficial del responsable, solicitud por escrito con croquis de ubicación.
- **Flujo**: Registro de tramo, colonia, fecha y horario → Evaluación de vialidad y rutas alternas → Pago de derechos → Emisión de Permiso.

### 6. `UR-TT-T-06`: Cesión de Derechos de Concesión
- **Propósito**: Transmisión legal de la titularidad de una concesión de transporte público entre particulares ante la autoridad municipal.
- **Tabla específica**: `tramites_cesion_concesion`.
- **Documentos**: Título original de concesión, identificación y RFC de cedente y cesionario, no antecedentes penales del cesionario, contrato o carta de cesión.
- **Flujo**: Solicitud con validación de padrón → Revisión documental → Ratificación presencial de firmas → Pago de derechos de transmisión → Actualización en el Padrón Oficial.

### 7. `UR-TT-T-07`: Permiso de Carga y Descarga
- **Propósito**: Autorización para vehículos de carga comercial en zonas urbanas restringidas, con vigencia por Día, Mes, Semestre o Año.
- **Tabla específica**: `tramites_carga_descarga`.
- **Documentos**: Identificación oficial, tarjeta de circulación, comprobante de domicilio comercial, póliza de seguro vigente.
- **Flujo**: Cotización automática según periodo y tipo de solicitante → Registro → Validación documental → Pago en línea (BanBajío) o ventanilla → Emisión instantánea con código QR.

---

## 5. Diccionario de Datos y Tablas en PostgreSQL (Supabase)

### Tabla Principal: `solicitudes`
Concentrador central de todos los trámites registrados en la ventanilla.
- `id` (SERIAL / BIGINT PK)
- `folio` (VARCHAR(50) UNIQUE) — Ej: `CD-20260830-1234`, `DESP-2026-0001`
- `usuario_id` (INT FK `users.id`)
- `tramite` (VARCHAR(20)) — Código oficial: `UR-TT-T-01` a `UR-TT-T-07`
- `estatus` (VARCHAR(40)) — `Borrador`, `Pendiente de revisión`, `En revisión`, `Cita agendada`, `Verificado`, `Aprobada`, `Rechazada`, `Pagado`, `Vigente`, `Concluido`
- `monto` (DECIMAL(10,2))
- `notas_admin` (TEXT)
- `created_at`, `updated_at` (TIMESTAMP)

### Tabla: `documentos`
Expediente digital seguro y auditable de cada solicitud.
- `id` (SERIAL PK)
- `solicitud_id` (INT FK `solicitudes.id`)
- `usuario_id` (INT FK `users.id`)
- `tipo_documento` (VARCHAR(100)) — Ej. `identificacion_oficial`, `factura_vehiculo`, `poliza_seguro`
- `nombre_original` (VARCHAR(255))
- `ruta_interna` (VARCHAR(255)) — Nombre único UUID en el almacenamiento
- `mime_type` (VARCHAR(100)) — `application/pdf`, `image/jpeg`, `image/png`
- `tamano_bytes` (BIGINT) — Máx. 10 MB (10,485,760 bytes)
- `hash_sha256` (VARCHAR(64)) — Hash criptográfico SHA-256 para validación de inmutabilidad
- `fecha_carga` (TIMESTAMP)

### Tablas de Datos Específicos de Trámites:
- `tramites_concesiones`: Convocatoria, ruta, tipo de servicio, propuesta del concesionario.
- `tramites_despintados`: Número de título, placas anteriores, serie/VIN, motivo del despintado.
- `tramites_orden_plaqueo`: Concesión, placas asignadas, datos de la aseguradora y póliza.
- `tramites_permisos_eventuales`: Solicitante, RFC, unidades, ruta, justificación operativa.
- `tramites_cierre_calle`: Calle, entre calles, colonia, tipo de cierre (parcial/total), fecha y horarios.
- `tramites_cesion_concesion`: Datos del titular cedente, datos del cesionario adquirente, parentesco/motivo.
- `tramites_carga_descarga`: Tipo de solicitante (persona física/moral/foráneo), placas, periodo de vigencia.

### Tablas de Control y Catálogos:
- `concesiones`: Padrón oficial de títulos vigentes en el municipio.
- `tarifas`: Catálogo oficial de importes de derechos configurables por ejercicio fiscal.
- `verificaciones_fisicas`: Registro de citas e inspecciones físicas con resultado y observaciones.
- `historial_estatus`: Bitácora temporal de cambios de estado de cada folio.
- `auditoria`: Registro inmutable de acciones administrativas (usuario, IP, acción, payload anterior y nuevo).
- `users`, `roles`, `user_roles`: Control de acceso e identidad basado en roles.
- `ci_sessions`: Almacén de sesiones seguras persistentes para Vercel Serverless.

---

## 6. Seguridad y Buenas Prácticas

1. **Protección CSRF y Filtros**:
   - Todas las peticiones `POST` cuentan con tokens CSRF regenerados.
   - Filtros `auth` y `role` impiden acceso no autorizado a rutas privadas o administrativas.
2. **Validación Exhaustiva en Español**:
   - Reglas estrictas de validación con etiquetas amigables en [Validation.php](file:///c:/Users/Alejandro/Downloads/App%20Residencias/app/Language/es/Validation.php).
   - Sanitización de entradas mediante `esc()` contra ataques XSS.
3. **Inmutabilidad y Criptografía de Documentos**:
   - Los archivos subidos no se sobrescriben; se renombran con identificadores UUID y se verifican mediante `hash_file('sha256')`.
4. **Tarifario Centralizado**:
   - [TarifarioService.php](file:///c:/Users/Alejandro/Downloads/App%20Residencias/app/Libraries/TarifarioService.php) calcula los costos de manera unificada basándose en las tarifas vigentes aprobadas en la Ley de Ingresos Municipal.
5. **Feature Flags**:
   - [FeatureFlags.php](file:///c:/Users/Alejandro/Downloads/App%20Residencias/app/Libraries/FeatureFlags.php) permite habilitar o deshabilitar módulos (como `UR-TT-T-06`) mediante variables de entorno sin modificar el código fuente.

---

## 7. Comandos de Mantenimiento y Validación

### Ejecución de Pruebas Automatizadas
```bash
.\php82\php.exe vendor/bin/phpunit
```
*Resultado actual: 41 pruebas, 154 aserciones pasando al 100%.*

### Análisis de Sintaxis PHP (Lint)
```bash
.\php82\php.exe -l app/Controllers/Portal/PortalController.php
.\php82\php.exe -l app/Controllers/Admin/AdminController.php
```

### Conexión a Base de Datos en Producción
Asegúrese de definir las variables en el archivo `.env`:
```ini
POSTGRES_HOST = aws-0-us-west-1.pooler.supabase.com
POSTGRES_PORT = 6543
POSTGRES_DATABASE = postgres
POSTGRES_USER = postgres.your_project_id
POSTGRES_PASSWORD = your_secure_password
```
