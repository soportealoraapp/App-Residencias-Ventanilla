# scripts/partes_manual/parte6_administrador.py
# -*- coding: utf-8 -*-
import docx
from generar_guias_word import (
    add_h1, add_h2, add_h3, add_paragraph, add_bullet, add_callout, add_styled_table
)

def agregar_parte6(doc, add_part_header, add_figure_image):
    add_part_header(
        doc,
        part_num="VI",
        part_title="Guía de Operación del Administrador General (Panel Completo)",
        part_description="Manual maestro de gobernanza institucional: tablero central, desglose analítico en 11 subpaneles, supervisión de los siete trámites, conciliación bancaria BanBajío, matriz de 14 permisos para operadores, catálogos normativos, módulo de tránsito y bitácora de auditoría inmutable."
    )

    # CAPÍTULO 44
    add_h1(doc, "44. Atribuciones Exclusivas y Gobernanza Integral del Sistema")
    add_paragraph(doc, "El Administrador General ostenta la máxima responsabilidad sobre la configuración, operación y fiscalización del Sistema Integral de Movilidad y Transporte de Uriangato. Sus atribuciones incluyen la supervisión de expedientes, asignación de permisos al personal de ventanilla, administración de catálogos normativos, alta de agentes de tránsito, calibración de la UMA y consulta de la bitácora inmutable de auditoría.")

    add_paragraph(doc, "Inicio de Sesión y Dashboard General:")
    add_paragraph(doc, "El administrador accede con sus credenciales maestras en /auth/login y es dirigido al panel central /admin:")

    add_figure_image(doc, "figura_01.png", "administrador",
                     "Figura 1. Inicio de sesión en la Ventanilla Digital para el Administrador General.", width_inches=6.0)

    add_figure_image(doc, "figura_02.png", "administrador",
                     "Figura 2. Pantalla de inicio del panel administrativo con indicadores y accesos directos.", width_inches=6.2)

    # CAPÍTULO 45
    add_h1(doc, "45. Desglose Global del Sistema (11 Subpaneles Analíticos)")
    add_paragraph(doc, "El módulo 'Desglose Global' (/admin/desglose) es exclusivo del rol administrador y reúne métricas exhaustivas del desempeño municipal:")

    add_figure_image(doc, "figura_03.png", "administrador",
                     "Figura 3. Desglose del sistema: visión panorámica de indicadores operacionales.", width_inches=6.2)

    add_figure_image(doc, "figura_04.png", "administrador",
                     "Figura 4. Resumen general del sistema con estadísticas acumuladas.", width_inches=6.2)

    add_figure_image(doc, "figura_05.png", "administrador",
                     "Figura 5. Distribución analítica de solicitudes por estatus operativo.", width_inches=6.2)

    # CAPÍTULO 46
    add_h1(doc, "46. Gestión Integral de Expedientes y Dictámenes Resolutivos")
    add_paragraph(doc, "En 'Solicitudes' (/admin/solicitudes), el administrador supervisa los expedientes de los siete trámites municipales, utilizando herramientas avanzadas de filtrado por folio, solicitante, estatus y tipo de trámite:")

    add_figure_image(doc, "figura_06.png", "administrador",
                     "Figura 6. Filtros avanzados del listado de solicitudes.", width_inches=6.2)

    add_figure_image(doc, "figura_07.png", "administrador",
                     "Figura 7. Resultados del listado de solicitudes con semáforos de estatus.", width_inches=6.2)

    add_paragraph(doc, "Al ingresar al expediente, el administrador examina la información capturada, valida la integridad criptográfica de los documentos y ejecuta dictámenes técnicos, prevenciones, autorizaciones de pago o rechazos fundados:")

    add_figure_image(doc, "figura_08.png", "administrador",
                     "Figura 8. Detalle del expediente y acciones de seguimiento resolutivo.", width_inches=6.2)

    add_figure_image(doc, "figura_09.png", "administrador",
                     "Figura 9. Historial cronológico de estatus del expediente con sellos de tiempo y autor.", width_inches=6.2)

    # CAPÍTULO 47
    add_h1(doc, "47. Radicación Presencial Avanzada y Supervisión de Identidad")
    add_paragraph(doc, "El administrador supervisa el módulo de radicación presencial (/admin/solicitudes-presenciales) asegurando la correcta identificación ciudadana por CURP y la no duplicación de expedientes:")

    add_figure_image(doc, "figura_10.png", "administrador",
                     "Figura 10. Búsqueda y validación del ciudadano por CURP en ventanilla.", width_inches=6.0)

    add_figure_image(doc, "figura_11.png", "administrador",
                     "Figura 11. Selección oficial del trámite de movilidad a radicar.", width_inches=6.0)

    add_figure_image(doc, "figura_12.png", "administrador",
                     "Figura 12. Captura asistida de los datos de la solicitud presencial.", width_inches=6.2)

    add_figure_image(doc, "figura_13.png", "administrador",
                     "Figura 13. Cotejo documental y registro final de la solicitud presencial.", width_inches=6.2)

    # CAPÍTULO 48
    add_h1(doc, "48. Módulo Central de Cobros y Conciliación Bancaria BanBajío")
    add_paragraph(doc, "En 'Cobros' (/admin/cobros), el administrador fiscaliza las órdenes de cobro originadas tanto por trámites como por multas de tránsito:")

    add_figure_image(doc, "figura_14.png", "administrador",
                     "Figura 14. Listado maestro de cobros con acciones de cobro en ventanilla y tesorería.", width_inches=6.2)

    add_paragraph(doc, "Conciliación de Pagos BanBajío (COB-04):")
    add_paragraph(doc, "En /admin/cobros/banco, el administrador busca y valida los pagos efectuados en ventanillas bancarias de BanBajío utilizando el número de referencia:")

    add_figure_image(doc, "figura_15.png", "administrador",
                     "Figura 15. Búsqueda y validación de pago bancario en BanBajío por referencia.", width_inches=6.0)

    # CAPÍTULO 49
    add_h1(doc, "49. Matriz Granular de los 14 Permisos para Operadores")
    add_paragraph(doc, "Desde /admin/permisos-operador, el Administrador General configura con total flexibilidad las secciones que el rol 'operador_ventanilla' puede visualizar y operar en el panel:")

    add_figure_image(doc, "figura_16.png", "administrador",
                     "Figura 16. Matriz de configuración de permisos para el operador de ventanilla.", width_inches=6.2)

    add_figure_image(doc, "figura_17.png", "administrador",
                     "Figura 17. Confirmación de guardado de permisos para el operador de ventanilla.", width_inches=6.2)

    # CAPÍTULO 50
    add_h1(doc, "50. Padrón y Administración de Usuarios, Roles y Seguridad")
    add_paragraph(doc, "En 'Usuarios' (/admin/usuarios), se administra el padrón de cuentas de ciudadanos y personal administrativo, permitiendo altas, actualización de datos de contacto y restablecimiento de claves:")

    add_figure_image(doc, "figura_18.png", "administrador",
                     "Figura 18. Padrón general de usuarios y ciudadanos registrados.", width_inches=6.2)

    # CAPÍTULO 51
    add_h1(doc, "51. Administración de Catálogos Normativos (Tarifas y Concesiones)")
    add_paragraph(doc, "Los catálogos permiten mantener actualizada la operación conforme a las reformas legales del H. Ayuntamiento:")
    add_bullet(doc, "Actualización de costos de los 7 trámites ante cambios en la Ley de Ingresos.", "Catálogo de Tarifas (/admin/tarifas): ")
    add_bullet(doc, "Padrón oficial de títulos de concesión de transporte público, rutas, unidades y concesionarios.", "Padrón de Concesiones (/admin/concesiones): ")

    add_figure_image(doc, "figura_19.png", "administrador",
                     "Figura 19. Catálogo oficial de tarifas municipales de movilidad.", width_inches=6.2)

    add_figure_image(doc, "figura_20.png", "administrador",
                     "Figura 20. Padrón oficial de títulos de concesiones de transporte público.", width_inches=6.2)

    # CAPÍTULO 52
    add_h1(doc, "52. Gobernanza del Módulo de Tránsito y Monitoreo de Sesiones")
    add_paragraph(doc, "La supervisión de la app móvil se realiza en /admin/app-transito, permitiendo configurar el valor diario de la UMA, la versión mínima obligatoria de la app, el catálogo de 78 faltas y el alta formal de los oficiales de tránsito:")

    add_figure_image(doc, "figura_21.png", "administrador",
                     "Figura 21. Vista general del módulo de tránsito municipal.", width_inches=6.2)

    add_figure_image(doc, "figura_22.png", "administrador",
                     "Figura 22. Parámetros operativos del sistema, valor de la UMA y agentes operativos.", width_inches=6.2)

    add_figure_image(doc, "figura_23.png", "administrador",
                     "Figura 23. Monitoreo de las últimas boletas recibidas desde campo.", width_inches=6.2)

    # CAPÍTULO 53
    add_h1(doc, "53. Agenda de Verificaciones Físicas e Inspecciones Mecánicas")
    add_paragraph(doc, "En /admin/verificaciones, el administrador supervisa el calendario de inspecciones físico-mecánicas vehiculares de las unidades de transporte (UR-02 y UR-04):")

    add_figure_image(doc, "figura_24.png", "administrador",
                     "Figura 24. Resumen estadístico y filtros de verificaciones físicas.", width_inches=6.2)

    add_figure_image(doc, "figura_25.png", "administrador",
                     "Figura 25. Listado de citas de inspección mecánico-visual.", width_inches=6.2)

    # CAPÍTULO 54
    add_h1(doc, "54. Bitácora Inmutable de Auditoría y Trazabilidad Histórica")
    add_paragraph(doc, "En 'Auditoría' (/admin/auditoria), se registra de forma inviolable e inalterable cada acción sensible ejecutada en el sistema (quién, fecha y hora exacta, IP, módulo y detalle del cambio):")

    add_figure_image(doc, "figura_26.png", "administrador",
                     "Figura 26. Indicadores y filtros del módulo de auditoría del sistema.", width_inches=6.2)

    add_figure_image(doc, "figura_27.png", "administrador",
                     "Figura 27. Eventos registrados y detalle pericial de un evento en bitácora.", width_inches=6.2)

