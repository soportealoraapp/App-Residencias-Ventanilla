# Plan de atención — Auditoría de código de Félix (17 nov 2026)

Documento original: [`2026-11-17_Auditoria_Felix.docx`](./2026-11-17_Auditoria_Felix.docx)

La auditoría trae 19 hallazgos: 3 críticos (C), 6 altos (A), 7 medios (M) y 6 bajos (B).
Cada uno se revisó contra el código del repositorio el 17 nov 2026. **Todos se confirman en el código.**
Lo que depende del proyecto de Supabase en vivo (Data API expuesta, accesos previos) se tiene que revisar en el panel.

| ID | Confirmación en el repositorio |
|---|---|
| C1 | Solo 3 `ENABLE ROW LEVEL SECURITY` en `supabase/migrations/` (0005 y 0007). La URL `kuwxjtwjjefqpzubtrlc` y la llave anon están en `public/agentes/_expo/static/js/web/entry-55ad….js`. |
| C2 | `app/Views/auth/login.php:39-52`: los botones de acceso rápido no dependen del entorno. `0001_init_schema.sql` y `0004_fix_demo_passwords.sql` insertan usuarios. |
| C3 | `0005_agentes_transito_app.sql:166-168`: `password_verify('password', hash)` da **true** y `'transito2026'` da **false**. |
| A2 | `AuthController.php:361` compara con `'post'` en minúsculas. |
| A3 | `Config\Services::paymentGateway()` siempre devuelve `BanbajioMockGateway`. |
| A5 | El filtro `permisoOperador` solo cubre `admin` y `admin/*`. Las rutas de `Routes.php:216-218` quedan fuera. |
| M4 | `tarifas_index.php:171` y `concesiones_index.php:186`: formularios POST sin `csrf_field()`. |
| M5 | `AuthController.php:72`: si `password_hash` es NULL, se valida contra el hash señuelo. |
| M7 | `Config/App.php:41`: `'0.0.0.0/0' => 'X-Forwarded-For'`. |
| B6 | `public/.htaccess:17` ("PLEASE REMOVE THIS"). `transito2026` en el bundle web. `README.md:73` clona `soportealoraapp/…`. |

---

## Fase 0 — Contención inmediata (hoy, sin desplegar código)

Lo hace quien administra Supabase y Vercel. Cierra los riesgos que ya están expuestos en producción.

| # | Acción | Hallazgo |
|---|---|---|
| 0.1 | En Supabase, activar RLS en **todas** las tablas de `public` sin políticas para `anon` ni `authenticated`. Se conservan las políticas `anon_read_catalogo` y `anon_read_parametros`, que usa la app de agentes. El backend PHP entra por conexión directa y no se ve afectado. Dejarlo como migración `0020_rls_todas_las_tablas.sql`. | C1 |
| 0.2 | Revisar el Security Advisor y los logs de la API de Supabase en busca de lecturas o escrituras con la llave anon sobre `users`, `user_roles`, `cobros` y `ci_sessions`. | C1 |
| 0.3 | Vaciar `ci_sessions`, lo que cierra todas las sesiones activas, por si alguna fue copiada. | C1 |
| 0.4 | Cambiar las contraseñas de `admin`, `operador1`, `juez1` y `ciudadano1`, y de los usuarios con id 1–3, en la base de producción. | C2 |
| 0.5 | Desactivar o cambiar la contraseña de los agentes `AGT-204`, `AGT-107` y `JEF-001`, y revocar sus tokens vigentes en `agentes_transito_tokens`. | C3 |

> **Decisión pendiente:** app-residencias.vercel.app se usa como **demo** para Movilidad y para Félix.
> Si se cambian las contraseñas demo, hay que avisar a quienes prueban.
> Otra opción es separar un despliegue "demo" (con botones y datos de prueba) de uno "producción" (sin ellos).

---

## Fase 1 — Críticos y altos fáciles (1–2 días)

Cambios chicos de código, cada uno con su prueba.

| # | Acción | Archivos | Hallazgo |
|---|---|---|---|
| 1.1 | Mostrar los botones de acceso rápido solo con una bandera `APP_DEMO_LOGIN=true` en `.env`. No basta con `ENVIRONMENT`, porque Vercel corre en `production` y también es la demo. | `Views/auth/login.php`, `Libraries/FeatureFlags.php` | C2 |
| 1.2 | Mover los usuarios y agentes de prueba de las migraciones a los seeders y scripts de `supabase/demo/`. Las migraciones ya aplicadas no se reescriben: se agrega una migración nueva que desactiva lo sembrado. | `supabase/migrations/0001`, `0004`, `0005`, nueva `0021`, `supabase/demo/` | C2, C3 |
| 1.3 | Corregir el POST de restablecer contraseña con `strtoupper($this->request->getMethod()) === 'POST'`, o separarlo en `attemptReset`. Agregar una prueba de extremo a extremo. | `AuthController::reset`, `tests/` | A2 |
| 1.4 | Agregar la bandera `APP_PAGO_EN_LINEA`. Si está apagada, se ocultan los botones "Pagar" de T-04, T-05 y T-07 y solo queda la hoja de pago para caja. Además, `pagar()` debe validar que la solicitud esté en "Pago pendiente". | `FeatureFlags`, `Config/Services.php`, controladores de T-04, T-05 y T-07 | A3 |
| 1.5 | Aplicar `PermisosOperador::activo()` en `registrarResultado`, `listarConvocatoria` y `seleccionar`, o pasar esas rutas a `admin/`. | `Ciudadano\TramitesController`, `Routes.php` | A5 |
| 1.6 | Rechazar el login si `password_hash` está vacío, después de la verificación de tiempo constante. | `AuthController::attemptLogin` | M5 |
| 1.7 | Agregar `csrf_field()` a los formularios de eliminar tarifa y concesión, y una prueba con CSRF activo. | `tarifas_index.php`, `concesiones_index.php` | M4 |
| 1.8 | Verificar la propiedad de la solicitud en la cita UR-02 (`ciudadano_id === user_id`, salvo personal), y validar la transición **antes** de borrar las verificaciones. | `TramiteDespintadoController::agendarCitaForm`, `guardarCita` | M1 |

---

## Fase 2 — Altos que requieren diseño (1 semana)

| # | Acción | Hallazgo | Decisión necesaria |
|---|---|---|---|
| 2.1 | **Reclamo de cuenta con segundo factor.** Al dar de alta en ventanilla se genera un **código de activación** impreso en el acuse (y opcionalmente enviado al correo o teléfono capturado). El registro con esa CURP exige el código. El reclamo no sobrescribe correo ni teléfono. | A1 | Elegir código impreso en el acuse (no depende del correo) o código por correo/SMS. Se recomienda el impreso. |
| 2.2 | **Boletas inmutables después de "recibida".** `sincronizar()` rechaza (409) cualquier cambio a boletas calificadas o cobradas. El fundamento y el rango UMA se resuelven en el servidor desde `catalogo_infracciones_app`. `fecha_infraccion` se valida: no futura y no más de N días atrás. | A6 | Definir N, el margen permitido para sincronizar boletas capturadas sin señal. |
| 2.3 | **Evidencias en el bucket privado de Supabase**, igual que los documentos. Servirlas con URL firmada desde el panel del juez. Validar el tipo real (`finfo`) y el tamaño, también en la rama base64. | A4 | Confirmar el nombre del bucket. Migrar las evidencias que ya existan en `public/uploads/infracciones/`. |
| 2.4 | Aceptar en `foto_*_url` solo URLs `https://` del propio almacenamiento, y validarlo al guardar y al pintar. | M2 | — (se resuelve junto con 2.3) |

---

## Fase 3 — Medios restantes (2–3 días)

| # | Acción | Hallazgo |
|---|---|---|
| 3.1 | `AuthFilter` revisa `users.activo` en cada petición. Si la cuenta está desactivada, destruye la sesión. | M3 |
| 3.2 | En el login de agentes: límite por IP y por placa (el mismo `Throttler` del login web), mensaje único "Placa o contraseña incorrectas" y registro en `auditoria`. | M6 |
| 3.3 | `proxyIPs` con comodín solo cuando la variable `VERCEL` esté definida. En el servidor municipal, lista con la IP del proxy real. Anotarlo en `GUIA_MIGRACION.md`. | M7 |

---

## Fase 4 — Bajos y limpieza antes de la entrega (2 días)

| # | Acción | Hallazgo |
|---|---|---|
| 4.1 | Lista fija de orígenes CORS: el dominio de producción, el de la demo y `localhost` en desarrollo. Quitar el `*.vercel.app` y el reflejo de `Origin`. | B1 |
| 4.2 | Agregar `POST /api/agentes/logout`, que marca `revocado_en`. La app lo llama al cerrar sesión. | B2 |
| 4.3 | `validar-concesion` devuelve solo `{vigente: true/false}`, y la vista usa `textContent`. | B3 |
| 4.4 | `subirFormato` usa `getMimeType()` y `guessExtension()`, igual que `DocumentoUploader`. | B4 |
| 4.5 | Recuperar contraseña: límite por IP y por correo, y vigencia del enlace de 1 h. | B5 |
| 4.6 | Limpieza: quitar `transito2026` del bundle (recompilar `/agentes`), el bloque "REMOVE" de `public/.htaccess`, corregir el repositorio en `README.md` y borrar `php-8.2.25-…zip`, `spark-server*.log` y `database.sqlite` sueltos en la raíz si no se usan. | B6 |

---

## Resumen: qué se quita, se corrige y se agrega

**Se quita**
- Los botones de acceso rápido fuera de la demo.
- Los usuarios y agentes de prueba de las migraciones.
- El pago simulado en producción.
- El `*.vercel.app` en CORS.
- Las evidencias en `public/`.
- Los restos de desarrollo (B6).

**Se corrige**
- Restablecer contraseña.
- El login con hash vacío.
- CSRF en los formularios de eliminar.
- Los permisos del operador en las rutas del portal.
- La propiedad de la cita UR-02.
- `proxyIPs`.
- El MIME de los formatos.
- La respuesta de `validar-concesion`.

**Se agrega**
- RLS en todas las tablas.
- Banderas `APP_DEMO_LOGIN` y `APP_PAGO_EN_LINEA`.
- Código de activación de cuenta.
- Boletas inmutables y montos calculados en el servidor.
- Bucket privado para evidencias.
- Revisión de `activo` en sesión.
- Límite de intentos en el login de agentes y en recuperar contraseña.
- Logout de agentes.
- Pruebas: reset de contraseña, CSRF activo, permisos del operador, propiedad de la cita, sincronizar boleta calificada.

## Orden sugerido de commits

1. `fix(seguridad): RLS en todas las tablas de Supabase` (0.1, migración 0020; la aplica quien administra Supabase)
2. `fix(auth): restablecer contrasena con POST en mayusculas` (1.3)
3. `fix(auth): rechazar login de cuentas sin contrasena` (1.6)
4. `feat(demo): bandera APP_DEMO_LOGIN para los accesos rapidos` (1.1)
5. `fix(seguridad): desactivar usuarios y agentes de prueba sembrados` (1.2)
6. `feat(pagos): bandera APP_PAGO_EN_LINEA y validar estatus antes de pagar` (1.4)
7. `fix(permisos): permisos del operador en rutas del portal y cita UR-02` (1.5, 1.8)
8. `fix(admin): csrf en eliminar tarifa y concesion` (1.7)
9. Fase 2: un commit por punto (2.1–2.4)
10. Fases 3 y 4

## Seguimiento

| Fase | Estado |
|---|---|
| 0 — Contención | **Aplicada en Supabase el 17 nov 2026.** Se corrieron `supabase/migrations/0020_rls_todas_las_tablas.sql` (0.1) y `supabase/seguridad/fase0_contencion.sql` (0.3–0.5). Verificado con la llave anon pública: `users`, `user_roles`, `cobros`, `ci_sessions`, `agentes_transito` y `solicitudes` responden 401, y el INSERT en `user_roles` se rechaza. `catalogo_infracciones_app` y `parametros_app` siguen respondiendo 200. Pendiente: revisar el Security Advisor y los logs (0.2). |
| 1 — Críticos y altos fáciles | **Hecha en código (17 nov 2026).** Banderas `APP_ENABLE_DEMO_LOGIN` y `APP_ENABLE_PAGO_EN_LINEA`, apagadas por defecto (1.1, 1.4). Reset de contraseña con `POST` (1.3). Login rechazado sin hash (1.6). Permisos del operador en `portal/tramites/ur-01/convocatorias/*` y `ur-02/solicitudes/*` (1.5). Cita UR-02 solo para el titular o el personal, y reprogramar sin borrar (1.8). CSRF en eliminar tarifa y concesión (1.7). 1.2: el servidor municipal ya no crea usuarios por migración; en los SQL 0001/0004/0005 de Supabase quedó la advertencia de correr `fase0_contencion.sql`. Pruebas en `AuditoriaFaseUnoTest` y `SeguridadAuthTest`. |
| 2 — Altos con diseño | **Hecha en código (17 nov 2026).** 2.1: acuse imprimible con código de activación (`XXXX-XXXX`, 30 días, solo se guarda su hash). Cada impresión invalida el anterior. Sin código no se reclama la cuenta y no se sobrescriben datos de ventanilla. 2.2: boletas calificadas no se tocan desde la app. Fundamento y UMA salen de `catalogo_infracciones_app`. Fecha válida, no futura y no más de 7 días atrás. 2.3: evidencias en el bucket privado `evidencias`, nombre aleatorio, tipo real validado, máximo 5 MB, servidas en `/evidencias/…` solo al personal. 2.4: solo URLs de evidencia propias del folio. Falta correr `supabase/migrations/0021_codigo_activacion_y_evidencias.sql`. Pruebas en `AuditoriaFaseDosTest`. |
| 3 — Medios | **Hecha en código (17 nov 2026).** 3.1: `AuthFilter` y `RoleFilter` revisan en cada petición que la cuenta exista y esté activa; si no, vacían la sesión, dejan registro `sesion_cerrada_cuenta_inactiva` y mandan al login. 3.2: login de agentes con mensaje único "Placa o contraseña incorrectas.", hash señuelo, bloqueo (429) tras 5 fallos por placa o 15 por IP en 15 minutos, y registros `login_agente_fallido` (con motivo), `login_agente_bloqueado` y `login_agente_exitoso`. La app muestra el aviso de demasiados intentos. 3.3: `proxyIPs` con comodín solo si existe `VERCEL`; fuera de Vercel solo las IPs de `APP_PROXY_IPS` (anotado en `GUIA_MIGRACION.md` y `.env.example`). No requiere SQL en Supabase. Pruebas en `AuditoriaFaseTresTest`. |
| 4 — Bajos y limpieza | **Hecha en código (17 nov 2026).** 4.1: `Config/Cors.php` arma la lista con el dominio de `APP_BASEURL`, `APP_CORS_ORIGINS` y, fuera de producción, los `localhost` de Expo; sin `*.vercel.app`. Los controladores de la API y `AgenteTokenFilter` ya no reflejan el `Origin`. 4.2: `POST /api/agentes/logout` marca `revocado_en` y deja registro `logout_agente`; la app lo llama al cerrar sesión. 4.3: `validar-concesion` responde solo `vigente`; la vista arma el aviso con `textContent` y el solicitante captura placas y serie. 4.4: `subirFormato` valida el tipo real con `getMimeType()` y toma la extensión de ese tipo. 4.5: recuperar contraseña limitado a 5 por IP y 3 por correo por hora (del correo solo se guarda el hash) y enlace de 1 h. 4.6: la contraseña de los botones de prueba sale de `EXPO_PUBLIC_DEMO_PASSWORD` y el script de exportación la deja fuera del bundle (verificado: ya no aparece `transito2026` en `public/agentes`). Se quitó el comentario "REMOVE" de `public/.htaccess` (la regla es la estándar de CI4). Se sacaron del repo el zip de PHP, los logs de `spark serve` y el `database.sqlite` vacío de la raíz (siguen en disco y quedan en `.gitignore`). El `README.md` no se cambió: `soportealoraapp/App-Residencias-Ventanilla` es el remoto real. No requiere SQL en Supabase. Pruebas en `AuditoriaFaseCuatroTest`. |
