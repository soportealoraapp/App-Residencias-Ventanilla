-- =====================================================
-- Migracion: Actualizar contrasenas de usuarios demo
-- Contrasenas:
--   admin        -> Admin2024!
--   operador1    -> Operador2024!
--   ciudadano1   -> 12345678
-- =====================================================

-- Asegurar que los roles existen
INSERT INTO public.roles (id, nombre, descripcion) VALUES
    (1, 'administrador',       'Acceso total al panel administrativo'),
    (2, 'operador_ventanilla', 'Revision y cobro de solicitudes'),
    (3, 'ciudadano',           'Usuario publico que solicita tramites')
ON CONFLICT (id) DO NOTHING;

-- Insertar o actualizar usuarios demo con hashes PHP correctos
INSERT INTO public.users (id, username, email, nombre_completo, password_hash, rfc, telefono, domicilio, activo) VALUES
    (1, 'admin',      'admin@uriangato.gob.mx',     'Administrador del Sistema', '$2y$10$kArQEDyJkStuG2R7PK5.aOkdxl1sDQVQOsSW6Imwh4JCaQrhDaBU6', NULL,           NULL,         NULL,                                  1),
    (2, 'operador1',  'operador1@uriangato.gob.mx', 'Operador Ventanilla Uno',   '$2y$10$8JgiL8f5q.9iiCqMgVAVl.BgH1odCA0Jd08aCgwJ50UoDvLnkcIxS', NULL,           NULL,         NULL,                                  1),
    (3, 'ciudadano1', 'ciudadano@example.com',      'Juan Perez Garcia',         '$2y$10$DRHMKEenbewacYmHnpXj7.Pf1sZs7XHPV8s9/MNbJoCGkdK3gxcEm', 'PEGJ800101XXX','4711234567','Calle Madero #123, Uriangato, Gto.', 1)
ON CONFLICT (id) DO UPDATE SET
    password_hash   = EXCLUDED.password_hash,
    username        = EXCLUDED.username,
    email           = EXCLUDED.email,
    nombre_completo = EXCLUDED.nombre_completo,
    activo          = EXCLUDED.activo;

SELECT setval(pg_get_serial_sequence('public.users', 'id'), COALESCE((SELECT MAX(id) FROM public.users), 3));

-- Asegurar asignacion de roles
INSERT INTO public.user_roles (id, user_id, role_id) VALUES
    (1, 1, 1),
    (2, 2, 2),
    (3, 3, 3)
ON CONFLICT (id) DO NOTHING;

SELECT setval(pg_get_serial_sequence('public.user_roles', 'id'), COALESCE((SELECT MAX(id) FROM public.user_roles), 3));
