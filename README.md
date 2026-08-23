# Ventanilla Digital de Movilidad y Transporte — Municipio de Uriangato, Gto.

## Módulo Portal Ciudadano + Panel Admin (Alejandro)

Este repositorio contiene los trámites de **UR-TT-T-07 Permiso de Carga y Descarga** (MVP, habilitado por defecto) y **UR-TT-T-06 Cesión de Concesión** (FASE 2, deshabilitado por default). Ambos módulos incluyen:

- **Portal Ciudadano**: registro, selección de trámite, formulario dinámico, carga de documentos, cálculo de monto, pago (mock BanBajío), resumen de solicitud, consulta de estatus y descarga de documentos.
- **Panel Admin**: listado de solicitudes por trámite/estatus, cambio de estatus con flujo validado, historial de estatus, comentarios de prevención/rechazo, visualización de documentos subidos y auditoría.

## Stack

| Capa | Tecnología |
|------|------------|
| Framework | **CodeIgniter 4.5** (PHP 8.2+) |
| Base de datos | MySQL / MariaDB |
| Frontend UI | Bootstrap 5 |
| Testing | PHPUnit 10 |
| Autenticación | Sistema Auth nativo CI4 (users + user_roles) |
| Pago | Interfaz `PaymentGatewayInterface` — implementación mock BanBajío |

## Requisitos

- **PHP 8.1+** con extensiones habilitadas:
  - `mysqli`
  - `mbstring`
  - `curl`
  - `gd`
  - `fileinfo`
  - `intl`
- **MySQL / MariaDB 10.4+**
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
   app.baseURL = 'http://localhost:8080/'
   database.default.hostname = localhost
   database.default.database = ventanilla_movilidad
   database.default.username = root
   database.default.password =
   database.default.DBDriver = MySQLi
   ```

3. **Crear base de datos:**
   Crear BD vacía (collation `utf8mb4_spanish_ci`.

4. **Ejecutar migraciones (10 migraciones):**
   ```bash
   php spark migrate
   ```
   Se crearán las tablas: `roles`, `users`, `user_roles`, `concesiones`, `solicitudes`, `solicitud_datos`, `documentos`, `tarifas`, `historial_estatus`, `auditoria`.

5. **Ejecutar seeders (demo):**
   ```bash
   php spark db:seed DatabaseSeeder
   ```
   Se cargan: roles de sistema, 3 usuarios demo, tarifas placeholder (con `placeholder_oficial=1`) y 3 concesiones de ejemplo (stub).

6. **Arrancar servidor:**
   ```bash
   php spark serve
   ```
   Acceder a http://localhost:8080

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

> ⚠️ **PENDIENTES DE INTEGRACIÓN**
>
> 1. **Pasarela BanBajío REAL:**
>    Reemplazar `app/Libraries/BanbajioMockGateway.php` por una implementación real de `app/Interfaces/PaymentGatewayInterface`. La clase mock devuelve transacciones exitosas falsas y una referencia `MOCK-REF`.
>
> 2. **Autenticación Ventanilla Digital existente:**
>    Este módulo trae su propio sistema Auth CI4 nativo (users / user_roles + login/register). Cuando se fusione con la Ventanilla existente, **borrar este sistema auth** y adaptar los Controllers/Filters para usar el servicio de auth ya implementado por el equipo de Caleb. Las interfaces `app/Interfaces/AuthInterface.php` (si existe placeholder) pueden ayudar como contrato.
>
> 3. **Tarifario REAL (placeholder_oficial):**
>    El seeder `TarifasSeeder` carga valores **NO OFICIALES** marcados con `placeholder_oficial=1`. El equipo de Movilidad debe validar y cambiar los montos desde Panel Admin. En el frontend se muestra un badge naranja de advertencia **"Monto no verificado"**.
>
> 4. **Padrón de Concesiones REAL (stub):**
>    La tabla `concesiones` es un **CATÁLOGO PROVISIONAL (stub)** con 3 registros demo. El módulo real será entregado por Caleb (UR-TT-T-01/02/03). En `/admin/concesiones` se muestra una alerta roja permanente recordándolo.
>
> 5. **Solicitud verbal UR-TT-T-07:**
>    TODO: la ficha oficial permite "solicitud verbal" además de escrita. Queda PENDIENTE de definir por el equipo si se implementa como una pantalla de **"Captura asistida por operador de ventanilla"** en el panel admin.
>
> 6. **Roles nomenclatura:**
>    El rol `operador_ventanilla` podría renombrarse a **"Juez Calificador"** / **"Operador"** cuando la Dirección de Movilidad confirme. El nombre está centralizado en la tabla `roles` — fácilmente renombrar desde el seeder `RolesSeeder` o desde el panel admin.

---

## Tests

Después de ejecutar `composer install`:

```bash
./vendor/bin/phpunit
```

Los tests se organizados:
- `tests/app/Libraries/TarifarioServiceTest.php` — cálculo de montos T-07 (particular/empresa, límite 15 camiones, placeholder, tarifa fija T-06.
- `tests/app/Libraries/EstadoSolicitudServiceTest.php` — transiciones válidas/inválidas T07 y T06, comentario obligatorio en Prevención/Rechazado, cálculo de vigencia 1 año.
- `tests/app/Controllers/Portal/TramiteCesionConcesionValidationTest.php` — validaciones condicionales de documentos según `tipo_cesion`, y validación de concesión vigente vs vencida.

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
| Alejandro | Portal Ciudadano + Panel Admin | 06 / 07 |

## MVP Prioritario

1. Infracciones de tránsito
2. **UR-TT-T-05** Cierre de calle
3. **UR-TT-T-07** Permiso de Carga y Descarga (este repo)

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
