# scripts/partes_manual/parte4_transito.py
# -*- coding: utf-8 -*-
import docx
from generar_guias_word import (
    add_h1, add_h2, add_h3, add_paragraph, add_bullet, add_callout, add_styled_table
)

def agregar_parte4(doc, add_part_header, add_figure_image, add_figure_double_images):
    add_part_header(
        doc,
        part_num="IV",
        part_title="Guía de Agentes de Tránsito y Mandos de Turno (App Móvil)",
        part_description="Manual operativo de campo: captura digital de boletas de infracción, arquitectura offline-first, toma pericial de 3 fotografías obligatorias, catálogo de 78 faltas, garantías retenidas y sincronización segura con el servidor central."
    )

    # CAPÍTULO 26
    add_h1(doc, "26. Filosofía de Campo 'Offline-First' y Operación con una Mano")
    add_paragraph(doc, "La Aplicación Móvil para Agentes de Tránsito sustituye las boletas manuales en papel por un entorno digital inviolable diseñado específicamente para el trabajo policial en la vía pública de Uriangato. La aplicación está construida para operar con una sola mano, bajo condiciones climáticas adversas y en zonas urbanas o rurales sin cobertura de datos móviles.")

    # CAPÍTULO 27
    add_h1(doc, "27. Preparación del Dispositivo y Permisos Requeridos")
    add_paragraph(doc, "Antes de iniciar el servicio de patrullaje o vialidad, el oficial debe cerciorarse de contar con los permisos habilitados:")
    add_bullet(doc, "Indispensable para fijar las coordenadas exactas de la infracción.", "Permiso de Ubicación Precisa (GPS): ")
    add_bullet(doc, "Requerido para la toma de las tres fotografías de evidencia pericial.", "Permiso de Cámara Fotográfica: ")
    add_bullet(doc, "Mínimo 60% de carga para cubrir la jornada operativa.", "Nivel de Batería: ")

    # CAPÍTULO 28
    add_h1(doc, "28. Inicio de Turno con Placa Oficial y Contraseña/PIN")
    add_paragraph(doc, "El acceso a la app móvil no requiere correos electrónicos complejos; el oficial se autentica directamente con su Número de Placa Oficial asignado y su Contraseña o PIN personal:")

    add_figure_double_images(doc, "figura_01.png", "Figura 1. Pantalla de bienvenida.",
                             "figura_02.png", "Figura 2. Acceso de agentes con placa y PIN.",
                             subfolder="transito", col_width=3.1)

    # CAPÍTULO 29
    add_h1(doc, "29. Centro de Mando (Dashboard Móvil) y Semáforo de Conexión")
    add_paragraph(doc, "Al ingresar, la pantalla de Inicio muestra el valor de la UMA vigente, el botón principal 'Registrar Infracción' y los contadores de boletas almacenadas en la memoria del dispositivo:")

    add_figure_image(doc, "figura_03.png", "transito",
                     "Figura 3. Inicio y sincronización de boletas con UMA vigente y contadores locales.", width_inches=5.8)

    # CAPÍTULO 30
    add_h1(doc, "30. Procedimiento Paso a Paso de Captura de Boleta Digital")
    add_paragraph(doc, "Al pulsar 'Registrar Infracción', se abre el formulario de captura estructurado en secciones ergonómicas:")

    add_h2(doc, "A. Momento y Ubicación Geográfica de los Hechos")
    add_paragraph(doc, "El sistema fija automáticamente la fecha, hora y coordenadas satelitales GPS, permitiendo al oficial anotar la calle, número y referencias viales:")

    add_figure_image(doc, "figura_04.png", "transito",
                     "Figura 4. Momento y ubicación geográfica de los hechos.", width_inches=5.8)

    add_h2(doc, "B. Identificación del Vehículo y Datos del Conductor")
    add_paragraph(doc, "Se capturan las placas, entidad federativa, marca, modelo, tipo de unidad (particular/público/carga) y la identidad del infractor o la indicación de conductor ausente:")

    add_figure_double_images(doc, "figura_05.png", "Figura 5. Identificación del vehículo.",
                             "figura_06.png", "Figura 6. Datos del conductor infractor.",
                             subfolder="transito", col_width=3.1)

    add_h2(doc, "C. Selección de Faltas en el Catálogo Normativo")
    add_paragraph(doc, "El catálogo municipal cuenta con 78 casillas normativas divididas en 15 categorías reglamentarias. Al marcar las casillas correspondientes, la aplicación muestra de manera instantánea el rango referencial en UMA y su aproximación en pesos para información del infractor:")

    add_figure_double_images(doc, "figura_07.png", "Figura 7. Selección de infracciones en el catálogo.",
                             "figura_08.png", "Figura 8. Faltas seleccionadas e importe estimado en UMA/pesos.",
                             subfolder="transito", col_width=3.1)

    # CAPÍTULO 31
    add_h1(doc, "31. Protocolo Pericial de las Tres Evidencias Fotográficas Obligatorias")
    add_paragraph(doc, "Para garantizar la validez jurídica de la boleta y evitar impugnaciones, la aplicación exige capturar hasta tres evidencias fotográficas mediante la cámara integrada:")
    add_bullet(doc, "Foto de la placa de circulación y parte trasera o delantera del vehículo.", "1. Foto de Placa: ")
    add_bullet(doc, "Toma panorámica que muestre el lugar de los hechos, señalética vial o cruce peatonal.", "2. Foto de Contexto: ")
    add_bullet(doc, "Detalle de la falta observada, alcoholímetro o documento retenido.", "3. Foto de Detalle/Falta: ")

    add_figure_image(doc, "figura_09.png", "transito",
                     "Figura 9. Captura obligatoria de evidencias fotográficas periciales.", width_inches=5.8)

    # CAPÍTULO 32
    add_h1(doc, "32. Registro de Garantías Retenidas y Guardado Seguro")
    add_paragraph(doc, "El oficial debe marcar con precisión qué objeto o documento retiene en garantía para asegurar el interés fiscal (placa delantera/trasera, tarjeta de circulación, licencia de conducir o remisión de vehículo a corralón con grúa):")

    add_figure_image(doc, "figura_10.png", "transito",
                     "Figura 10. Opciones de garantía retenida por el oficial de tránsito.", width_inches=5.8)

    add_paragraph(doc, "Al presionar 'Emitir Boleta', el sistema genera un folio correlativo único asignado a la placa del oficial (ejemplo: AGT-042-2026-00015) y guarda los datos de forma cifrada en la memoria local:")

    add_figure_image(doc, "figura_11.png", "transito",
                     "Figura 11. Resumen de emisión de la boleta con folio correlativo oficial.", width_inches=5.8)

    # CAPÍTULO 33
    add_h1(doc, "33. Sincronización Automática y Manual con el Servidor Municipal")
    add_paragraph(doc, "En la sección 'Boletas' (/app/boletas.tsx), el oficial puede consultar la cola de boletas y su estado de sincronización (Pendiente, Sincronizando, Sincronizada o Error):")

    add_figure_double_images(doc, "figura_12.png", "Figura 12. Búsqueda y filtros de boletas locales.",
                             "figura_13.png", "Figura 13. Resultados y estado de sincronización.",
                             subfolder="transito", col_width=3.1)

    add_paragraph(doc, "Al pulsar sobre cualquier boleta, se abre su ficha detallada para verificar el estado de transmisión hacia el servidor municipal:")

    add_figure_image(doc, "figura_14.png", "transito",
                     "Figura 14. Detalle de una boleta sincronizada con el servidor central.", width_inches=5.8)

    # CAPÍTULO 34
    add_h1(doc, "34. Consulta de Historial, Perfil y Cierre de Turno")
    add_paragraph(doc, "Antes de entregar el teléfono móvil en comandancia, el oficial debe entrar a 'Mi Perfil' (/app/perfil.tsx), verificar que no queden boletas pendientes de sincronizar y presionar 'Cerrar Sesión':")

    add_figure_image(doc, "figura_15.png", "transito",
                     "Figura 15. Perfil y datos operativos del agente de tránsito para cierre de turno.", width_inches=5.8)

