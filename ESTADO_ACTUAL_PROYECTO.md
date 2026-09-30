# Estado Actual del Proyecto — Ventanilla Digital de Movilidad y Transporte de Uriangato, Gto.

**Creado:** 17/09/2026 — Resumen de avance y estado del sistema (complementa la DOCUMENTACION_SISTEMA.md oficial).

---

## 1. Resumen ejecutivo — estado actual

El sistema de Ventanilla Digital de Movilidad y Transporte del H. Ayuntamiento de Uriangato se encuentra en **estado avanzado con los 7 trámites integrados en la aplicación web y desplegado en Vercel + Supabase**. Este documento es un resumen de la evolución, componentes, arquitectura, módulos y pendientes, extraído de la DOCUMENTACION_SISTEMA.md, la estructura de carpetas actual y el historial del repositorio.

Aspectos visibles del desarrollo:
- 7 trámites del módulo de Movilidad y Transporte integrados en formularios de portal, controladores, modelos y flujos de estatus.
- Panel admin con banda unificada, resolución de solicitudes, histórico, auditoría y catálogos (tarifas y concesiones) más evaluación de convocatorias.
- Migración completa de uploads y descargas a Supabase Storage (documentos, INE, formatos).
- Envío real de correo de recuperación de contraseña (SMTP) en la configuración actual.
- Estabilización de campos de registro y perfil (CURP, INE, teléfono, ciudad, dirección, términos; INE movido a “Mi Perfil”).
- Páginas legales (Términos y Condiciones, Aviso de Privacidad) implementadas.
- Tests automatizados en verde y migraciones listas para reconstruir el esquema en entornos nuevos.
- Módulo móvil `agentes-transito` iniciado sobre Expo/React Native para los trámites 04/05 (en construcción).

Carpetas más relevantes en esta publicación:
- `app/` (backend CodeIgniter), `mobile/agentes-transito/` (módulo móvil), `supabase/migrations/` (SQL de base), `api/`, `scripts/`, `tests/`.

## 2. Qué incluye esta versión del proyecto (resumen de alcance)

Esta rama consolida el módulo de Movilidad y Transporte con dos caras: Portal Ciudadano (CIUDADANO/autenticado) y Panel Admin (operador/admin). Está listo para ejecutar los trámites desde formularios digitales y para que el personal municipal revise, apruebe, rechace o ponga en libertad las solicitudes.

Trámites integrados (código y frontend):
- UR-TT-T-01 Concesión de Transporte
- UR-TT-T-02 Constancia de Despintado
- UR-TT-T-03 Orden de Plaqueo
- UR-TT-T-07 Permiso de Carga y Descarga (habilitado por defecto, MVP)
- UR-TT-T-06 Cesión de Concesión (FASE 2, deshabilitado por defecto mediante FeatureFlags)
- (Trámites 04 / 05 referenciados en diseño; su aplicación móvil está en `mobile/agentes-transito`.)
## 3. Flujo de trabajo de datos y estatus (resumen operacional)

El ciclo de vida de una solicitud está normalizado:
- Creación y datos del ciudadano en portal → archivo de documentos (hash SHA-256, UUID de nombre, MIME validado) → cálculo de monto/tarifa mock (consulta al catálogo oficial/placeholder) → pago mock (interfaz abstraída para futuros gateways) → resumen y consulta de estatus.
- Admin: apertura del expediente → revisión documental y físico (cuando aplica) → cambio de estatus según flujo permitido, con bloqueo de transiciones inválidas y comentario obligatorio en prevención/rechazo → registro en historial y auditoría.
- Descarga exclusivamente por controlador + filtro de autorización (no por URL directa), con `Content-Disposition: attachment` y nombre original mostrado.

Archivos privados en almacenamiento externo; las descargas son vía endpoint, nunca accesibles por ruta pública.

## 4. Estado de despliegue y configuración

Con la configuración de entorno y el script de despliegue, el proyecto está preparado para funcionamiento serverless en Vercel con base de datos Supabase (PostgreSQL vía Pooler). En local o en servidor tradicional Apache/Nginx, las sesiones se guardan en base de datos (`ci_sessions`), lo que estabiliza la sesión entre recargas de contenedor.

Lo esencial para reproducir el servidor actual:
- PHP 8.2+ con extensiones recomendadas (pdo_pgsql/pdo_mysql, mbstring, curl, gd, fileinfo, intl).
- Composer para vendor.
- `.env` con base de datos, URL base, driver de sesión y flags de características.
- Migraciones CI4 y seeders de demostración para roles, usuarios, tarifas, concesiones y convocatorias.

Enlaces de administración en esta publicación:
- Panel admin en `/admin/*`.
- Portal ciudadano en `/portal/*`.
- Rutas de API mínimas: `api/` (según estructura presente).

## 5. Sistema de roles y acceso (resumen RBAC)

El esquema de autorización se basa en 3 roles nombrados en la tabla `roles`:
- `administrador` → panel admin completo: dashboard, solicitudes, catálogos, formatos, convocatorias y auditoría.
- `operador_ventanilla` → bandeja de solicitudes, resolución, cambio de estatus y comentarios de prevención/rechazo; catálogos administrativos según scope.
- `ciudadano` → portal: registro, selección de trámite, formularios, mis solicitudes, Mi Perfil y descarga de sus documentos.

El middleware/filtro centralizado protege rutas admin/portal y evita accesos cruzados (por ejemplo, ocultar portal a admin/operador cuando no corresponde). La nomenclatura del rol `operador_ventanilla` puede renombrarse (p. ej. “Juez Calificador”/“Operador”) mediante la tabla `roles` o el seeder cuando la Dirección de Movilidad confirme el nombre oficial.