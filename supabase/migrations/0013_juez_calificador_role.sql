-- COB-09: rol del juez calificador. No crea usuarios ni altera asignaciones.
INSERT INTO public.roles (nombre, descripcion, created_at, updated_at)
VALUES (
    'juez_calificador',
    'Consulta y calificación de boletas de infracción',
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
)
ON CONFLICT (nombre) DO NOTHING;
