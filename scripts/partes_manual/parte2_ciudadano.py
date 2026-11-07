# scripts/partes_manual/parte2_ciudadano.py
# -*- coding: utf-8 -*-
import docx
from generar_guias_word import (
    add_h1, add_h2, add_h3, add_paragraph, add_bullet, add_callout, add_styled_table
)

def agregar_parte2(doc, add_part_header, add_figure_image, add_figure_double_images):
    add_part_header(
        doc,
        part_num="II",
        part_title="Guía de Operación del Ciudadano (Portal Web y Celular)",
        part_description="Manual completo para autorregistro, radicación en línea de los siete trámites de movilidad, seguimiento por semáforo de estatus, atención a prevenciones, pago digital/bancario y descarga de documentos resolutivos."
    )

    # CAPÍTULO 7
    add_h1(doc, "7. Entorno del Ciudadano y Preparación de Documentos Digitales")
    add_paragraph(doc, "El Portal Ciudadano de Uriangato está diseñado para que cualquier persona física o moral pueda gestionar sus trámites de movilidad desde una computadora o desde su teléfono celular. La portada pública permite conocer la oferta de trámites y los requisitos antes de iniciar sesión.")

    add_figure_image(doc, "figura_01.jpg", "ciudadano",
                     "Figura 1. Portada de la Ventanilla Digital de Uriangato.", width_inches=6.2)

    add_paragraph(doc, "Antes de iniciar una solicitud, el solicitante debe digitalizar sus requisitos conforme a los siguientes estándares oficiales:")
    add_bullet(doc, "Aceptado para todos los documentos oficiales, actas, comprobantes y contratos.", "Archivos PDF (.pdf): ")
    add_bullet(doc, "Permitido para fotografías de identificaciones (INE), tarjetas de circulación y placas.", "Imágenes JPG / PNG (.jpg, .png): ")
    add_bullet(doc, "Hasta 10 Megabytes por archivo individual. El sistema verifica el contenido real del archivo, no solo la extensión.", "Tamaño Máximo: ")

    add_callout(doc, "tip", "RECOMENDACIONES PARA CAPTURAR DOCUMENTOS CON EL CELULAR",
                "• Coloque la identificación o documento sobre una superficie plana y bien iluminada.\n• Evite sombras de manos o reflejos de flash sobre micas plastificadas.\n• Asegúrese de que las cuatro esquinas del documento sean visibles y los textos sean 100% legibles.")

    # CAPÍTULO 8
    add_h1(doc, "8. Creación de Cuenta Ciudadana, Acceso y Recuperación de Contraseña")
    add_paragraph(doc, "Para realizar trámites por primera vez, el ciudadano debe registrarse en la plataforma:")
    
    add_paragraph(doc, "Pasos para crear la cuenta ciudadana:")
    add_bullet(doc, "1. Ingrese a la portada y presione 'Registrarse' (/auth/register).")
    add_bullet(doc, "2. Capture su Clave Única de Registro de Población (CURP) de 18 caracteres.")
    add_bullet(doc, "3. Ingrese su nombre completo, apellidos, correo electrónico personal y teléfono de contacto.")
    add_bullet(doc, "4. Defina una contraseña segura y confirme la aceptación del Aviso de Privacidad.")
    add_bullet(doc, "5. Presione 'Crear Cuenta'. La cuenta quedará activa inmediatamente.")

    add_figure_image(doc, "figura_02.jpg", "ciudadano",
                     "Figura 2. Formulario para crear tu cuenta ciudadana.", width_inches=6.0)

    add_paragraph(doc, "Inicio de Sesión y Olvido de Contraseña:")
    add_paragraph(doc, "Para visitas posteriores, ingrese su correo y contraseña en /auth/login. Si no recuerda su clave, use la opción '¿Olvidaste tu contraseña?' (/auth/olvide) para recibir un enlace de recuperación seguro en su correo registrado.")

    add_figure_image(doc, "figura_03.jpg", "ciudadano",
                     "Figura 3. Pantalla de inicio de sesión en la Ventanilla Digital.", width_inches=5.8)

    add_figure_image(doc, "figura_04.jpg", "ciudadano",
                     "Figura 4. Pantalla para recuperación de contraseña olvidada.", width_inches=5.8)

    # CAPÍTULO 9
    add_h1(doc, "9. Reclamo y Activación de Cuenta Presencial (COB-08)")
    add_paragraph(doc, "Si usted acudió en persona a la ventanilla de la Dirección de Movilidad y el personal le dio de alta un trámite presencial, el sistema creó un expediente a su nombre pero con una cuenta 'sin acceso al portal'. No necesita crear una cuenta nueva.")

    add_paragraph(doc, "Procedimiento para reclamar y activar su cuenta en línea:")
    add_bullet(doc, "1. En la pantalla de inicio de sesión, pulse 'Reclamar cuenta previa' (/auth/reclamar).")
    add_bullet(doc, "2. Ingrese su CURP y el correo electrónico proporcionado al operador en ventanilla.")
    add_bullet(doc, "3. El sistema vinculará automáticamente todos los expedientes que hayan sido radicados previamente con esa CURP.")
    add_bullet(doc, "4. Defina su contraseña personal y confirme.")

    add_figure_image(doc, "figura_05.jpg", "ciudadano",
                     "Figura 5. El campo CURP liga los trámites que registraste en ventanilla física.", width_inches=5.8)

    add_figure_image(doc, "figura_06.jpg", "ciudadano",
                     "Figura 6. Mensaje de confirmación al vincular tu cuenta con éxito.", width_inches=5.8)

    # CAPÍTULO 10
    add_h1(doc, "10. Navegación en Computadora y Celular (Barra Inferior)")
    add_paragraph(doc, "Al ingresar al portal (/portal/dashboard), el solicitante accede a su panel de control. El sistema adapta su interfaz de forma responsiva: en computadoras muestra una barra de navegación superior, mientras que en teléfonos móviles ofrece una barra de navegación inferior fija diseñada ergonómicamente para ser pulsada con el pulgar.")

    add_figure_double_images(doc, "figura_07.jpg", "Figura 7. Panel de inicio en computadora.",
                             "figura_08.jpg", "Figura 8. El portal en celular con barra inferior.",
                             subfolder="ciudadano", col_width=3.1)

    add_paragraph(doc, "En la sección 'Mi Perfil' (/portal/mi-perfil), el ciudadano puede actualizar su teléfono de contacto, cambiar su correo electrónico o renovar su contraseña de acceso.")

    add_figure_image(doc, "figura_09.jpg", "ciudadano",
                     "Figura 9. Pantalla 'Mi Perfil' para actualización de datos y contraseña.", width_inches=5.8)

    # CAPÍTULO 11
    add_h1(doc, "11. Catálogo de Trámites y Radicación en Línea Paso a Paso")
    add_paragraph(doc, "En 'Trámites Disponibles' (/portal/tramites), se despliegan las fichas de los siete procedimientos municipales con sus costos vigentes, requisitos, fundamento normativo y tiempos estimados de resolución.")

    add_figure_image(doc, "figura_10.jpg", "ciudadano",
                     "Figura 10. Catálogo Oficial de Trámites con buscador dinámico y filtros.", width_inches=6.2)

    add_paragraph(doc, "Procedimiento paso a paso para enviar una solicitud:")
    add_bullet(doc, "1. Seleccione el trámite deseado y pulse 'Iniciar Trámite'.")
    add_bullet(doc, "2. Capture los datos solicitados en el formulario específico (vehículo, domicilio, motivo).")
    add_bullet(doc, "3. Adjunte los archivos requeridos en los campos de carga correspondientes.")
    add_bullet(doc, "4. Revise el resumen de la información y pulse 'Confirmar y Enviar'.")
    add_bullet(doc, "5. El sistema generará su Folio Único de Expediente (ejemplo: SOL-2026-000185).")

    add_figure_image(doc, "figura_11.jpg", "ciudadano",
                     "Figura 11. Formulario de solicitud con la carga de documentos probatorios.", width_inches=6.2)

    add_figure_image(doc, "figura_12.jpg", "ciudadano",
                     "Figura 12. Resumen de la solicitud enviada con su Folio Oficial.", width_inches=6.0)

    # CAPÍTULO 12
    add_h1(doc, "12. Bandeja 'Mis Solicitudes' y Semáforo de Estatus")
    add_paragraph(doc, "En la pestaña 'Mis Solicitudes' (/portal/mis-solicitudes), el solicitante puede monitorear en tiempo real la evolución de todos sus trámites mediante insignias de color:")

    add_styled_table(doc,
                     ["Distintivo Visual", "Estado del Trámite", "Significado Operativo para el Ciudadano"],
                     [
                         ["🔵 Azul", "Recibido", "Solicitud ingresada con éxito. En espera de turno de revisión."],
                         ["🟡 Amarillo", "En Revisión / En Estudio", "El personal técnico está analizando la validez de los documentos."],
                         ["🟠 Naranja", "Prevención", "ACCIÓN REQUERIDA: Hay documentos observados que deben subsanarse."],
                         ["🟣 Morado", "Pago Pendiente", "¡Trámite autorizado! Listo para realizar el pago de derechos."],
                         ["🟢 Verde", "Pagado", "Pago confirmado por Tesorería. En proceso de firma y emisión."],
                         ["🟢 Verde Éxito", "Emitido / Concluido", "¡Trámite concluido! Documento oficial disponible para descarga."],
                         ["🔴 Rojo", "Rechazado", "Solicitud no procedente. Consulte el fundamento legal en el detalle."]
                     ],
                     [1.5, 2.0, 3.0])

    add_figure_image(doc, "figura_13.jpg", "ciudadano",
                     "Figura 13. Bandeja 'Mis solicitudes', con folio, estatus, fecha, monto y acciones.", width_inches=6.2)

    # CAPÍTULO 13
    add_h1(doc, "13. Cómo Atender y Subsanar una Prevención Documental")
    add_paragraph(doc, "Si un expediente es marcado en color naranja 'Prevención', el ciudadano no debe iniciar un trámite nuevo ni pagar nada adicional. Significa que la autoridad detectó que un documento estaba ilegible, incompleto o vencido.")

    add_paragraph(doc, "Pasos para subsanar:")
    add_bullet(doc, "1. Abra el detalle de la solicitud en 'Mis Solicitudes'.")
    add_bullet(doc, "2. Lea la observación específica asentada por el funcionario en el recuadro rojo de alerta y en el 'Historial de estatus'.")
    add_bullet(doc, "3. Pulse el botón 'Reemplazar archivo' únicamente sobre el documento observado.")
    add_bullet(doc, "4. Suba la nueva versión nítida y presione 'Reenviar Documentación'. El expediente volverá a revisión técnica.")

    add_figure_image(doc, "figura_14.jpg", "ciudadano",
                     "Figura 14. Solicitud en Prevención, con el aviso rojo y la evolución del trámite.", width_inches=6.2)

    add_figure_image(doc, "figura_15.jpg", "ciudadano",
                     "Figura 15. El comentario de la Dirección aparece en el Historial de estatus.", width_inches=6.0)

    # CAPÍTULO 14
    add_h1(doc, "14. Métodos de Pago: En Línea o en Ventanilla / BanBajío")
    add_paragraph(doc, "Una vez autorizado el trámite, el expediente cambia a estado morado 'Pago Pendiente'. El ciudadano dispone de dos vías de pago:")
    add_bullet(doc, "Presione 'Pagar en Línea', ingrese su tarjeta de crédito o débito y el sistema acreditará el pago de inmediato.", "Opción A: Pago Electrónico: ")
    add_bullet(doc, "Presione 'Hoja de Pago' para descargar el documento oficial con folio COB-AAAA-NNNNNN, desglose de tarifas y número de referencia. Puede pagar en cajas municipales o en cualquier sucursal BanBajío.", "Opción B: Pago en Banco / Ventanilla: ")

    add_figure_image(doc, "figura_16.jpg", "ciudadano",
                     "Figura 16. Solicitud en Pago pendiente, con los botones 'Hoja de pago' y 'Pagar en línea'.", width_inches=6.2)

    add_figure_image(doc, "figura_17.jpg", "ciudadano",
                     "Figura 17. Hoja de ayuda para el pago, con desglose y lugares autorizados.", width_inches=6.0)

    # CAPÍTULO 15
    add_h1(doc, "15. Descarga de Documentos Oficiales Emitidos")
    add_paragraph(doc, "Al confirmarse la liquidación de derechos, el expediente transita a 'Emitido / Concluido'. El solicitante encontrará habilitado el botón 'Ver Permiso' o 'Descargar Documento Oficial' para obtener su resolución en formato PDF imprimible con plena validez jurídica ante las autoridades viales.")

    add_figure_image(doc, "figura_18.jpg", "ciudadano",
                     "Figura 18. Resumen de un permiso emitido, con el botón 'Ver permiso'.", width_inches=6.0)

    add_figure_image(doc, "figura_19.jpg", "ciudadano",
                     "Figura 19. Permiso para Cierre de Calle emitido por la Dirección de Movilidad listo para imprimir.", width_inches=5.8)

