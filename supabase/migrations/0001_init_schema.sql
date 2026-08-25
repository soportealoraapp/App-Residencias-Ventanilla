-- =====================================================
-- App Residencias - Supabase PostgreSQL 15+ Schema
-- Proyecto: kuwxjtwjjefqpzubtrlc
-- =====================================================

-- =====================================================
-- TABLA: roles
-- =====================================================
CREATE TABLE IF NOT EXISTS public.roles (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(250),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- =====================================================
-- TABLA: users
-- =====================================================
CREATE TABLE IF NOT EXISTS public.users (
    id BIGSERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE,
    email VARCHAR(255) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(180),
    rfc VARCHAR(13),
    telefono VARCHAR(20),
    domicilio TEXT,
    activo SMALLINT NOT NULL DEFAULT 1,
    reset_token VARCHAR(255),
    reset_expira TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- =====================================================
-- TABLA: user_roles
-- =====================================================
CREATE TABLE IF NOT EXISTS public.user_roles (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES public.users(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    role_id BIGINT NOT NULL REFERENCES public.roles(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE (user_id, role_id)
);

-- =====================================================
-- TABLA: concesiones
-- =====================================================
CREATE TABLE IF NOT EXISTS public.concesiones (
    id BIGSERIAL PRIMARY KEY,
    numero_titulo VARCHAR(50) NOT NULL UNIQUE,
    titular_actual VARCHAR(180) NOT NULL,
    vehiculo_placas VARCHAR(10),
    vehiculo_num_serie VARCHAR(20),
    vigencia_inicio DATE,
    vigencia_fin DATE,
    estatus VARCHAR(30) NOT NULL DEFAULT 'vigente',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- =====================================================
-- TABLA: solicitudes
-- =====================================================
CREATE TABLE IF NOT EXISTS public.solicitudes (
    id BIGSERIAL PRIMARY KEY,
    folio VARCHAR(20) NOT NULL UNIQUE,
    tramite VARCHAR(20) NOT NULL,
    ciudadano_id BIGINT NOT NULL REFERENCES public.users(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    estatus VARCHAR(50) NOT NULL,
    monto DECIMAL(12,2),
    fecha_solicitud TIMESTAMP NOT NULL,
    fecha_resolucion TIMESTAMP NULL,
    fecha_pago TIMESTAMP NULL,
    fecha_vigencia_inicio DATE,
    fecha_vigencia_fin DATE,
    comentario_rechazo TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS idx_solicitudes_ciudadano_id ON public.solicitudes(ciudadano_id);
CREATE INDEX IF NOT EXISTS idx_solicitudes_tramite ON public.solicitudes(tramite);
CREATE INDEX IF NOT EXISTS idx_solicitudes_estatus ON public.solicitudes(estatus);

-- =====================================================
-- TABLA: solicitud_datos
-- =====================================================
CREATE TABLE IF NOT EXISTS public.solicitud_datos (
    id BIGSERIAL PRIMARY KEY,
    solicitud_id BIGINT NOT NULL REFERENCES public.solicitudes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    clave VARCHAR(100) NOT NULL,
    valor TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS idx_solicitud_datos_solicitud_clave ON public.solicitud_datos(solicitud_id, clave);

-- =====================================================
-- TABLA: documentos
-- =====================================================
CREATE TABLE IF NOT EXISTS public.documentos (
    id BIGSERIAL PRIMARY KEY,
    solicitud_id BIGINT NOT NULL REFERENCES public.solicitudes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    tipo_documento VARCHAR(100),
    nombre_original VARCHAR(255),
    ruta_interna VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100),
    tamano_bytes BIGINT,
    hash_sha256 VARCHAR(64),
    usuario_id BIGINT NOT NULL REFERENCES public.users(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    fecha_carga TIMESTAMP NOT NULL,
    validado SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS idx_documentos_solicitud_id ON public.documentos(solicitud_id);

-- =====================================================
-- TABLA: tarifas
-- =====================================================
CREATE TABLE IF NOT EXISTS public.tarifas (
    id BIGSERIAL PRIMARY KEY,
    tramite VARCHAR(20) NOT NULL,
    criterio VARCHAR(50) NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    vigente_desde DATE NOT NULL,
    vigente_hasta DATE,
    descripcion VARCHAR(250),
    placeholder_oficial SMALLINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS idx_tarifas_tramite_criterio_vigente ON public.tarifas(tramite, criterio, vigente_desde);

-- =====================================================
-- TABLA: historial_estatus
-- =====================================================
CREATE TABLE IF NOT EXISTS public.historial_estatus (
    id BIGSERIAL PRIMARY KEY,
    solicitud_id BIGINT NOT NULL REFERENCES public.solicitudes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    estatus_anterior VARCHAR(50),
    estatus_nuevo VARCHAR(50) NOT NULL,
    usuario_id BIGINT REFERENCES public.users(id) ON DELETE SET NULL ON UPDATE SET NULL,
    fecha TIMESTAMP NOT NULL,
    comentario TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS idx_historial_estatus_solicitud_id ON public.historial_estatus(solicitud_id);

-- =====================================================
-- TABLA: auditoria
-- =====================================================
CREATE TABLE IF NOT EXISTS public.auditoria (
    id BIGSERIAL PRIMARY KEY,
    entidad VARCHAR(50) NOT NULL,
    entidad_id BIGINT,
    accion VARCHAR(50) NOT NULL,
    usuario_id BIGINT REFERENCES public.users(id) ON DELETE SET NULL ON UPDATE SET NULL,
    fecha TIMESTAMP NOT NULL,
    detalle JSON,
    created_at TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS idx_auditoria_entidad ON public.auditoria(entidad, entidad_id);
CREATE INDEX IF NOT EXISTS idx_auditoria_fecha ON public.auditoria(fecha);

-- =====================================================
-- DATOS INICIALES: roles
-- =====================================================
INSERT INTO public.roles (id, nombre, descripcion) VALUES
    (1, 'administrador', 'Acceso total al panel administrativo'),
    (2, 'operador_ventanilla', 'Revision y cobro de solicitudes'),
    (3, 'ciudadano', 'Usuario publico que solicita tramites')
ON CONFLICT (id) DO NOTHING;
SELECT setval(pg_get_serial_sequence('public.roles', 'id'), COALESCE((SELECT MAX(id) FROM public.roles), 1));

-- =====================================================
-- DATOS INICIALES: users demo (password: 12345678)
-- =====================================================
-- NOTA: El hash debe recalcularse segun el algoritmo PHP password_hash()
-- Estos hashes son genericos de 12345678 con PASSWORD_DEFAULT
INSERT INTO public.users (id, username, email, nombre_completo, password_hash, rfc, telefono, domicilio, activo) VALUES
    (1, 'admin', 'admin@uriangato.gob.mx', 'Administrador del Sistema', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1),
    (2, 'operador1', 'operador1@uriangato.gob.mx', 'Operador Ventanilla Uno', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, 1),
    (3, 'ciudadano1', 'ciudadano@example.com', 'Juan Perez Garcia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PEGJ800101XXX', '4711234567', 'Calle Madero #123, Uriangato, Gto.', 1)
ON CONFLICT (id) DO NOTHING;
SELECT setval(pg_get_serial_sequence('public.users', 'id'), COALESCE((SELECT MAX(id) FROM public.users), 1));

INSERT INTO public.user_roles (id, user_id, role_id) VALUES
    (1, 1, 1),
    (2, 2, 2),
    (3, 3, 3)
ON CONFLICT (id) DO NOTHING;
SELECT setval(pg_get_serial_sequence('public.user_roles', 'id'), COALESCE((SELECT MAX(id) FROM public.user_roles), 1));

-- =====================================================
-- DATOS INICIALES: concesiones
-- =====================================================
INSERT INTO public.concesiones (numero_titulo, titular_actual, vehiculo_placas, vehiculo_num_serie, vigencia_inicio, vigencia_fin, estatus) VALUES
    ('CONC-URI-2024-0001', 'Maria Gonzalez Lopez', 'GTO-123-45', '3VWSK7AN1RM000001', '2024-01-15', '2029-01-15', 'vigente'),
    ('CONC-URI-2024-0002', 'Jose Martinez Ruiz', 'GTO-678-90', '3VWSK7AN1RM000002', '2023-06-01', '2028-06-01', 'vigente'),
    ('CONC-URI-2022-0099', 'Carlos Rodriguez Hernandez', 'GTO-000-99', '3VWSK7AN1RM000099', '2022-03-10', '2025-03-10', 'vencida')
ON CONFLICT (numero_titulo) DO NOTHING;
SELECT setval(pg_get_serial_sequence('public.concesiones', 'id'), COALESCE((SELECT MAX(id) FROM public.concesiones), 1));

-- =====================================================
-- DATOS INICIALES: tarifas
-- =====================================================
INSERT INTO public.tarifas (tramite, criterio, monto, vigente_desde, placeholder_oficial, descripcion) VALUES
    ('UR-TT-T-07', 'particular_dia', 50.00, CURRENT_DATE, 1, 'TODO: valor no verificado - Tarifa particular por dia'),
    ('UR-TT-T-07', 'particular_mes', 400.00, CURRENT_DATE, 1, 'TODO: valor no verificado - Tarifa particular por mes'),
    ('UR-TT-T-07', 'particular_semestre', 2200.00, CURRENT_DATE, 1, 'TODO: valor no verificado - Tarifa particular por semestre'),
    ('UR-TT-T-07', 'particular_anio', 4000.00, CURRENT_DATE, 1, 'TODO: valor no verificado - Tarifa particular por anio'),
    ('UR-TT-T-07', 'empresa_dia', 120.00, CURRENT_DATE, 1, 'TODO: valor no verificado - Tarifa empresa por dia'),
    ('UR-TT-T-07', 'empresa_mes', 1000.00, CURRENT_DATE, 1, 'TODO: valor no verificado - Tarifa empresa por mes'),
    ('UR-TT-T-07', 'empresa_semestre', 5500.00, CURRENT_DATE, 1, 'TODO: valor no verificado - Tarifa empresa por semestre'),
    ('UR-TT-T-07', 'empresa_anio', 10000.00, CURRENT_DATE, 1, 'TODO: valor no verificado - Tarifa empresa por anio'),
    ('UR-TT-T-06', 'cesion_concesion_base', 9055.20, CURRENT_DATE, 1, 'TODO: valor dudoso, revisar con Movilidad')
ON CONFLICT DO NOTHING;
SELECT setval(pg_get_serial_sequence('public.tarifas', 'id'), COALESCE((SELECT MAX(id) FROM public.tarifas), 1));
