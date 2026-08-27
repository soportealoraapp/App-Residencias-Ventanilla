# Ventanilla Digital de Movilidad y Transporte — Municipio de Uriangato, Gto.

## Módulo Portal Ciudadano + Panel Admin (Alejandro + Caleb)

Este repositorio ya contiene **todos los trámites del módulo de Movilidad y Transporte** integrados:

- **UR-TT-T-01 Concesión de Transporte** (Backend + Frontend: Caleb)
- **UR-TT-T-02 Constancia de Despintado** (Backend + Frontend: Caleb)
- **UR-TT-T-03 Orden de Plaqueo** (Backend + Frontend: Caleb)
- **UR-TT-T-07 Permiso de Carga y Descarga** (MVP, habilitado por defecto: Alejandro)
- **UR-TT-T-06 Cesión de Concesión** (FASE 2, deshabilitado por default: Alejandro)

Ambos módulos comparten:
- **Portal Ciudadano**: registro, selección de trámite, formularios dinámicos, carga de documentos, cálculo de monto, pago (mock BanBajío), resumen de solicitud, consulta de estatus y descarga de documentos.
- **Panel Admin**: listado de solicitudes por trámite/estatus, cambio de estatus con flujo validado, historial de estatus, comentarios de prevención/rechazo, visualización de documentos subidos, catálogos de concesiones/tarifas y evaluación de convocatorias UR-01.

## Stack

| Capa | Tecnología |
|------|------------|
| Framework | **CodeIgniter 4.5** (PHP 8.2+) |
| Base de datos | **PostgreSQL 15+** (Supabase, Vercel-compatible) / MySQL / MariaDB |
| Frontend UI | Bootstrap 5 |
| Testing | PHPUnit 10 |
| Autenticación | Sistema Auth nativo CI4 (users + user_roles) — compatible con nombres de roles `administrador`, `operador_ventanilla`, `ciudadano` |
| Pago | Interfaz `PaymentGatewayInterface` — implementación mock BanBajío |
| Deploy | Vercel serverless + Supabase |

## Requisitos

- **PHP 8.1+** con extensiones habilitadas:
  - `pdo_pgsql`, `pgsql` (recomendado) o `mysqli`
  - `mbstring`
  - `curl`
  - `gd`
  - `fileinfo`
  - `intl`
- **PostgreSQL 15+ (recomendado: Supabase)** o **MySQL / MariaDB 10.4+**
- **Composer 2.x**

## Instalación paso a paso

1. **Instalar dependencias:**
   ```bash
   composer install
   ```
   Esto descargará `codeigniter4/framework`, PHPUnit 10 y el resto del vendor.

2. **Configurar entorno:**
   Copiar el archivo existente `.env` (o `.env.example si existe`) y revisar:
   ```env
   APP_BASEURL = 'http://localhost:8080/'
   # Conexión PostgreSQL (Supabase/Vercel, recomendado)
   POSTGRES_HOST = db.supabase.example.com
   POSTGRES_PORT = 6543
   POSTGRES_DATABASE = postgres
   POSTGRES_USERNAME = postgres
   POSTGRES_PASSWORD = tu_password
   DATABASE_DEFAULT_DBDRIVER = Postgre
   # -- O conexión MySQL (legacy)
   # database.default.hostname = localhost
   # database.default.database = ventanilla_movilidad
   # database.default.username = root
   # database.default.password =
   # database.default.DBDriver = MySQLi
   ```

3. **Crear base de datos:**
   Crear BD vacía (PostgreSQL o MySQL).

4. **Ejecutar migraciones (14 migraciones):**
   ```bash
   php spark migrate
   ```
   Se crearán las tablas: `roles`, `users`, `user_roles`, `concesiones`, `solicitudes`, `solicitud_datos`, `documentos`, `tarifas`, `historial_estatus`, `auditoria`, `convocatorias`, `verificaciones_fisicas` + 2 migraciones de relación.

5. **Ejecutar seeders (demo):**
   ```bash
   php spark db:seed DatabaseSeeder
   ```
   Se cargan: roles de sistema, usuarios demo, tarifas placeholder (con `placeholder_oficial=1`), concesiones reales con `tipo_persona` y convocatorias demo para UR-01.

6. **Arrancar servidor local:**
   - **Opción recomendada (Doble clic o en terminal):**
     ```cmd
     iniciar-servidor.bat
     ```
   - **O mediante PowerShell / CLI:**
     ```bash
     .\php82\php.exe spark serve --php="php82/php.exe"
     ```
   Acceder en el navegador a: [http://localhost:8080](http://localhost:8080)

## Usuarios demo (password de todos: `12345678`)

| Correo | Rol |
|--------|-----|
| `admin@uriangato.gob.mx` | administrador |
| `operador1@uriangato.gob.mx` | operador_ventanilla |
| `ciudadano@example.com` | ciudadano |

## Cómo habilitar UR-TT-T-06 (Cesión de Concesión — FASE 2)

Editar el archivo `.env` en la raíz y cambiar la feature flag:

```env
APP_ENABLE_UR_TT_T_06 = true
```

Por defecto su valor es `false` (trámite deshabilitado en portal y admin). La clase `App\Libraries\FeatureFlags` lee esta variable.

---

> ## ✅ Estado de Integración — Fusión Alejandro (UR-06/07) + Caleb (UR-01/02/03)
>
> ### ✅ Resueltos (ya integrados y funcionando)
>
> 1. **Módulos Caleb (UR-TT-T-01/02/03) + Alejandro (UR-TT-T-06/07) fusionados en el mismo repo:**
>    - 14 migraciones compartidas (no duplicadas)
>    - Sidebar admin consolidado con filtros por trámite (UR-01/02/03/06/07)
>    - Catálogo de Concesiones unificado (1 sola tabla `concesiones` con campo `tipo_persona` para UR-01/02/03/06)
>    - Pantalla `/portal/tramites` y `/portal/dashboard` incluyen los 5 trámites
>    - Controladores unificados en `Admin\AdminController` y `Portal\PortalController` con acciones compartidas
>
> 2. **Padrón de Concesiones (tabla `concesiones`):**
>    ✅ Ya no es un stub/provisorio. La tabla se convirtió en **fuente de verdad** para Caleb (UR-01/02/03) y Alejandro (UR-06). Incluye campos reales: `tipo_persona`, `vigencia_inicio/fin`, `estatus`, `vehiculo_placas`, `vehiculo_num_serie`.
>    - Se eliminaron alertas "Catálogo provisional (stub)" en `/admin/concesiones`
>    - El endpoint `cesion-concesion/validar-concesion/{numero}` del T-06 ya valida contra este padrón real
>
> 3. **Sistema de autenticación (placeholder nativo CI4) extendido y usado por ambos módulos:**
>    - Caleb mantuvo `AuthController`, `AuthFilter`, `RoleFilter`, `UserModel`, `RoleModel` de Alejandro y los extendió:
>      - `RoleFilter` ahora acepta múltiples roles separados por coma como argumento
>      - `Filters.php` salta CSRF en entorno de testing (para PHPUnit)
>      - Nombres de roles confirmados: `administrador`, `operador_ventanilla`, `ciudadano`
>    - Protección de rutas `/portal/*` y `/admin/*` funciona uniformemente para UR-01/02/03/06/07
>
> 4. **Suite de tests PHPUnit 100% unificada:**
>    - 33 tests / 113 assertions: UR-01 (TramiteConcesionTransporte), UR-02 (TramiteDespintado), UR-03 (TramiteOrdenPlaqueo), TramitesController, TarifarioService, EstadoSolicitudService y TramiteCesionConcesionValidation
>
> ### ❌ Pendientes de confirmación externa (no bloquean la fusión actual)
>
> Estos siguen abiertos según confirmación pendiente de Movilidad y Transporte o equipos externos:
>
> 1. **Pasarela BanBajío REAL:**
>    Sigue usándose `app/Libraries/BanbajioMockGateway.php` como implementación `PaymentGatewayInterface`. La clase mock devuelve transacciones exitosas falsas y una referencia `MOCK-REF`.
>
> 2. **Autenticación Ventanilla Digital externa (si existe):**
>    El sistema Auth unificado actualmente es el nativo CI4. Si en el futuro se integra una instancia global "Ventanilla Digital" con SSO, se deberá sustuir este sistema por el servicio de auth externo (los Controllers/Filters ya están centralizados).
>
> 3. **Tarifario OFICIAL (placeholder_oficial=1):**
>    El seeder `TarifasSeeder` incluye tarifas para UR-01/02/03/06/07, pero **los montos de UR-TT-T-07 siguen marcados `placeholder_oficial=1` (NO OFICIALES)**. El equipo de Movilidad debe validar y cambiar los montos desde Panel Admin. En el frontend se muestra un badge naranja de advertencia "Monto no verificado".
>
> 4. **Solicitud verbal UR-TT-T-07:**
>    TODO: la ficha oficial permite "solicitud verbal" además de escrita. Queda PENDIENTE de definir por el equipo si se implementa como una pantalla de **"Captura asistida por operador de ventanilla"** en el panel admin.
>
> 5. **Roles nomenclatura:**
>    El rol `operador_ventanilla` podría renombrarse a **"Juez Calificador"** / **"Operador"** cuando la Dirección de Movilidad confirme. El nombre está centralizado en la tabla `roles` — fácilmente renombrar desde el seeder `RolesSeeder` o desde el panel admin.

---

## Tests

Después de ejecutar `composer install`:

```bash
./vendor/bin/phpunit
```

Los tests se organizan:
- `tests/app/Libraries/TarifarioServiceTest.php` — cálculo de montos T-07 (particular/empresa, límite 15 camiones, placeholder, tarifa fija T-06 + tarifas UR01/02/03)
- `tests/app/Libraries/EstadoSolicitudServiceTest.php` — transiciones válidas/inválidas T07, T06, T01, T02, T03; comentario obligatorio en Prevención/Rechazado, cálculo de vigencia
- `tests/app/Controllers/Portal/TramiteCesionConcesionValidationTest.php` — validaciones condicionales de documentos según `tipo_cesion`, y validación de concesión vigente vs vencida
- `tests/app/Controllers/Portal/TramiteConcesionTransporteControllerTest.php` — flujo UR-TT-T-01
- `tests/app/Controllers/Portal/TramiteDespintadoControllerTest.php` — flujo UR-TT-T-02
- `tests/app/Controllers/Portal/TramiteOrdenPlaqueoControllerTest.php` — flujo UR-TT-T-03
- `tests/app/Controllers/Portal/TramitesControllerTest.php` — endpoints compartidos (crear/consultar/agendar/seleccionar convocatoria)

La configuración PHPUnit (`phpunit.xml.dist`) usa el grupo `tests` (SQLite en memoria por defecto) con migraciones automáticas.

## Seguridad de documentos

- Archivos subidos a `writable/uploads/documentos/` (directorio **privado**, NO accesible desde público).
- Nombre interno: UUID v4 (nunca el nombre original).
- Validación MIME type 2 veces: reglas CI4 + `App\Libraries\DocumentoUploader`.
- Hash de integridad SHA-256 almacenado en tabla `documentos`.
- Descarga sólo vía Controller + `AuthFilter` (panel admin) o portal (pertenece al ciudadano).
- Cabecera `Content-Disposition: attachment` fuerza nombre original.
- **NUNCA acceder a documentos vía URL directa.

## Equipo

| Nombre | Área | Trámites |
|--------|------|----------|
| Caleb | Backend + Integración | 01 / 02 / 03 |
| Juan Carlos | App móvil | 04 / 05 |
| Alejandro | Portal Ciudadano + Panel Admin + Fusión módulos | 06 / 07 + integración 01/02/03 |

## MVP Prioritario

1. Infracciones de tránsito
2. **UR-TT-T-05** Cierre de calle
3. **UR-TT-T-01** Concesión de Transporte
4. **UR-TT-T-02** Constancia de Despintado
5. **UR-TT-T-03** Orden de Plaqueo
6. **UR-TT-T-07** Permiso de Carga y Descarga

→ **UR-TT-T-06** Cesión de Concesión es **FASE 2** (deshabilitado por default).

## Feature flags

Implementado en `app/Libraries/FeatureFlags.php`:

```php
FeatureFlags::enabled('UR_TT_T_06')
```

Lee variables de entorno con el prefijo `APP_ENABLE_`. Ejemplo en `.env`:

```env
APP_ENABLE_UR_TT_T_06 = true
```
