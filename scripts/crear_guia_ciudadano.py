# scripts/crear_guia_ciudadano.py
# -*- coding: utf-8 -*-
import os
import docx
from generar_guias_word import (
    apply_document_styles, add_cover_page, add_h1, add_h2, add_h3,
    add_paragraph, add_bullet, add_callout, add_image_placeholder, add_styled_table
)

def generar_guia_ciudadano(output_path):
    doc = docx.Document()
    apply_document_styles(doc)

    # PORTADA
    add_cover_page(
        doc,
        role_title="Ciudadano y Solicitante",
        subtitle="Guía Paso a Paso para la Realización de Trámites de Movilidad en Línea, Pagos Digitales y Descarga de Documentos",
        target_audience="Ciudadanos, Concesionarios, Choferes y Transportistas de Uriangato",
        version="1.0"
    )

    # ÍNDICE GENERAL
    add_h1(doc, "ÍNDICE GENERAL DE LA GUÍA")
    add_bullet(doc, "1. Bienvenido a la Ventanilla Única de Movilidad de Uriangato")
    add_bullet(doc, "2. Preparación de Documentos Digitales (Formatos y Consejos)")
    add_bullet(doc, "3. Creación de Cuenta, Activación y Recuperación de Contraseña")
    add_bullet(doc, "4. Reclamo de Cuenta para Trámites Iniciados en Ventanilla (COB-08)")
    add_bullet(doc, "5. Conociendo el Portal Ciudadano (Computadora y Teléfono Celular)")
    add_bullet(doc, "6. Catálogo de los Siete Trámites Municipales Oficiales")
    add_bullet(doc, "7. Procedimiento Paso a Paso: Cómo Iniciar una Solicitud en Línea")
    add_bullet(doc, "8. Seguimiento de Solicitudes y Comprensión del Semáforo de Estatus")
    add_bullet(doc, "9. Cómo Subsanar una Observación o Prevención Documental")
    add_bullet(doc, "10. Métodos de Pago: En Línea, Ventanilla y Sucursales BanBajío")
    add_bullet(doc, "11. Descarga e Impresión de Documentos Oficiales Emitidos")
    add_bullet(doc, "12. Canales de Ayuda, Ubicación y Preguntas Frecuentes")

    # CAPÍTULO 1
    add_h1(doc, "1. Bienvenido a la Ventanilla Única de Movilidad de Uriangato")
    add_paragraph(doc, "El Gobierno Municipal de Uriangato pone a tu disposición la Ventanilla Única Digital de Movilidad y Transporte. Esta plataforma te permite realizar todos tus trámites de movilidad desde la comodidad de tu hogar, negocio o teléfono celular, sin necesidad de hacer filas ni acudir repetidamente a oficinas de gobierno.")
    
    add_paragraph(doc, "Ventajas principales para el ciudadano:")
    add_bullet(doc, "Inicia solicitudes y sube documentos las 24 horas del día, los 365 días del año.", "Acceso Continuo: ")
    add_bullet(doc, "Conoce en tiempo real en qué etapa se encuentra tu expediente con un folio único de seguimiento.", "Transparencia Total: ")
    add_bullet(doc, "Paga con tarjeta en línea o imprime tu orden de pago para ventanilla o bancos.", "Facilidad de Pago: ")
    add_bullet(doc, "Descarga tus permisos, autorizaciones y constancias en formato digital con sellos oficiales.", "Entrega Digital: ")

    # CAPÍTULO 2
    add_h1(doc, "2. Preparación de Documentos Digitales (Formatos y Consejos)")
    add_paragraph(doc, "Antes de iniciar tu trámite en línea, ten a la mano los documentos requeridos en formato digital. El sistema acepta los siguientes formatos oficiales:")
    add_bullet(doc, "Para actas, pólizas, facturas, comprobantes y contratos.", "Archivos PDF (.pdf): ")
    add_bullet(doc, "Para fotografías claras de credencial de elector (INE), tarjeta de circulación o placas.", "Imágenes JPG o PNG (.jpg, .jpeg, .png): ")
    add_bullet(doc, "Cada archivo individual puede pesar hasta 10 Megabytes (10 MB), espacio suficiente para documentos en alta resolución.", "Tamaño Máximo: ")

    add_callout(doc, "tip", "CONSEJOS PARA TOMAR BUENAS FOTOS A TUS DOCUMENTOS CON EL CELULAR",
                "• Coloca el documento sobre una mesa plana con buena iluminación natural.\n• Evita el reflejo del flash sobre credenciales plastificadas.\n• Asegúrate de que las cuatro esquinas del documento sean visibles y los textos sean completamente legibles.\n• Un documento borroso o incompleto causará que la autoridad te pida volverlo a subir, demorando tu trámite.")

    # CAPÍTULO 3
    add_h1(doc, "3. Creación de Cuenta, Activación y Recuperación de Contraseña")
    add_paragraph(doc, "Para realizar trámites por primera vez, debes registrarte en el portal:")
    
    add_h2(doc, "Paso a Paso para Crear una Cuenta:")
    add_bullet(doc, "1. Abre tu navegador de internet e ingresa a la portada municipal o al enlace directo: http://[servidor-municipal]/register")
    add_bullet(doc, "2. Ingresa tu Clave Única de Registro de Población (CURP) de 18 caracteres.")
    add_bullet(doc, "3. Captura tu nombre completo, apellidos, correo electrónico personal y número de teléfono celular.")
    add_bullet(doc, "4. Crea una contraseña segura (mínimo 8 caracteres, combinando letras y números).")
    add_bullet(doc, "5. Lee y acepta el Aviso de Privacidad y los Términos del Servicio.")
    add_bullet(doc, "6. Presiona el botón verde 'Crear mi Cuenta'. Tu cuenta quedará lista al instante.")

    add_image_placeholder(doc, "Registro de Ciudadano (/auth/register)",
                          "Formulario de autorregistro ciudadano en el portal municipal.",
                          "Campos de CURP, Nombre, Correo, Teléfono, Contraseña, casilla de Términos y botón 'Crear mi Cuenta'.")

    add_h2(doc, "¿Olvidaste tu contraseña?")
    add_paragraph(doc, "Si no recuerdas tu contraseña, haz clic en '¿Olvidaste tu contraseña?' en la pantalla de inicio de sesión (/auth/olvide). Ingresa tu correo registrado y el sistema te enviará las instrucciones para restablecerla de forma segura.")

    # CAPÍTULO 4
    add_h1(doc, "4. Reclamo de Cuenta para Trámites Iniciados en Ventanilla (COB-08)")
    add_callout(doc, "info", "CASO ESPECIAL: SI ACUDISTE PRIMERO A LAS OFICINAS MUNICIPALES",
                "Si fuiste a la Dirección de Movilidad y el personal te dio de alta un trámite en mostrador, el sistema generó un expediente a tu nombre pero sin contraseña web. ¡No necesitas crear una cuenta nueva ni duplicar tus datos!")

    add_paragraph(doc, "Cómo reclamar y activar tu cuenta en línea:")
    add_bullet(doc, "1. En la pantalla de inicio de sesión, haz clic en el enlace 'Reclamar cuenta previa de ventanilla' (/auth/reclamar).")
    add_bullet(doc, "2. Digita tu CURP y el correo electrónico que proporcionaste al funcionario municipal.")
    add_bullet(doc, "3. El sistema verificará tu identidad y te permitirá establecer una contraseña personal.")
    add_bullet(doc, "4. Al ingresar, verás de inmediato el trámite que iniciaste en ventanilla con todo su historial.")

    add_image_placeholder(doc, "Reclamo de Cuenta Presencial (/auth/reclamar)",
                          "Módulo para que solicitantes de ventanilla física activen su acceso al portal web.",
                          "Validador de CURP, correo de contacto y formulario para definir contraseña personal.")

    # CAPÍTULO 5
    add_h1(doc, "5. Conociendo el Portal Ciudadano (Computadora y Teléfono Celular)")
    add_paragraph(doc, "Al ingresar al portal (/portal/dashboard), verás tu Centro de Trámites:")
    add_bullet(doc, "Acceso directo a 'Iniciar Nuevo Trámite', 'Mis Solicitudes' y 'Avisos Oficiales'.", "Pantalla de Inicio: ")
    add_bullet(doc, "En tu computadora encontrarás el menú en la parte superior; en tu teléfono celular, encontrarás una barra de navegación inferior fácil de tocar con el pulgar.", "Diseño Adaptado a Celulares: ")
    add_bullet(doc, "Te permite cambiar tu teléfono, actualizar tu correo o modificar tu contraseña.", "Mi Perfil (/portal/mi-perfil): ")

    add_image_placeholder(doc, "Dashboard del Ciudadano en PC y Móvil (/portal/dashboard)",
                          "Vista principal del portal con accesos rápidos a trámites y estado de solicitudes.",
                          "Tarjetas de trámites activos, botón 'Iniciar Nuevo Trámite' y barra de navegación inferior móvil.")

    # CAPÍTULO 6
    add_h1(doc, "6. Catálogo de los Siete Trámites Municipales Oficiales")
    add_paragraph(doc, "En la sección 'Trámites Disponibles' (/portal/tramites), encontrarás las fichas informativas de los siete procedimientos que puedes gestionar:")

    add_styled_table(doc,
                     ["Clave", "Trámite de Movilidad", "Costo Oficial Vigente", "Documentos Básicos Requeridos"],
                     [
                         ["UR-TT-T-01", "Concesión de transporte público de ruta fija", "$9,055.20", "Acta constitutiva/nacimiento, Constancia Fiscal, poder notarial y no antecedentes."],
                         ["UR-TT-T-02", "Constancia de despintado de unidad", "$64.90", "Identificación oficial (INE) y factura o documento de propiedad del vehículo."],
                         ["UR-TT-T-03", "Orden de plaqueo para cambio de unidad", "$0.00 (Gratuito)", "Factura o carta factura del nuevo vehículo y comprobante de revista mecánica."],
                         ["UR-TT-T-04", "Permiso eventual de transporte", "$156.94", "Póliza de seguro o fondo de garantía vigente y documentos de la unidad."],
                         ["UR-TT-T-05", "Permiso de cierre parcial o total de calle", "$287.00", "Identificación oficial, croquis de ubicación y motivo fundado del evento."],
                         ["UR-TT-T-06", "Cesión de concesión de transporte", "$9,055.20", "Identificaciones de cedente y cesionario, actas de nacimiento y título original."],
                         ["UR-TT-T-07", "Permiso de carga y descarga de mercancías", "Desde $191.00 hasta $57,325.00 según días y camiones", "Tarjeta de circulación vigente, identificación y comprobante de domicilio."]
                     ],
                     [1.1, 2.4, 1.4, 1.6])

    add_image_placeholder(doc, "Catálogo Oficial de Trámites (/portal/tramites)",
                          "Catálogo con tarjetas interactivas de cada trámite municipal.",
                          "Fichas con costo oficial, requisitos, tiempo estimado de respuesta y botón 'Iniciar Trámite'.")

    # CAPÍTULO 7
    add_h1(doc, "7. Procedimiento Paso a Paso: Cómo Iniciar una Solicitud en Línea")
    add_paragraph(doc, "Iniciar un trámite toma menos de 5 minutos siguiendo estos pasos:")
    add_bullet(doc, "1. En el catálogo de trámites, localiza el trámite que necesitas y presiona 'Iniciar Trámite'.")
    add_bullet(doc, "2. Completa los campos del formulario: El sistema precargará tus datos personales y te pedirá los datos del vehículo, ruta o evento según corresponda.")
    add_bullet(doc, "3. Sube tus documentos: Haz clic en el botón 'Seleccionar archivo' de cada requisito obligatorio y adjunta el PDF o foto correspondiente.")
    add_bullet(doc, "4. Revisa el resumen de tu solicitud y verifica que todos los datos sean correctos.")
    add_bullet(doc, "5. Presiona 'Confirmar y Enviar Solicitud'.")
    add_bullet(doc, "6. ¡Listo! El sistema te mostrará tu Folio Oficial de Solicitud (ejemplo: SOL-2026-000312). Guárdalo para cualquier consulta.")

    add_image_placeholder(doc, "Formulario de Solicitud y Carga de Archivos (/portal/tramites/formulario)",
                          "Pantalla de captura donde el ciudadano llena campos y sube sus archivos probatorios.",
                          "Campos específicos del trámite, botones de subida de archivos con validador de formato y botón 'Enviar'.")

    # CAPÍTULO 8
    add_h1(doc, "8. Seguimiento de Solicitudes y Comprensión del Semáforo de Estatus")
    add_paragraph(doc, "En cualquier momento puedes consultar el avance de tus solicitudes en la pestaña 'Mis Solicitudes' (/portal/mis-solicitudes). Cada expediente tiene asignado un distintivo de color (badge) que indica exactamente qué está ocurriendo:")

    add_styled_table(doc,
                     ["Distintivo Visual", "Nombre del Estado", "Qué Significa para Ti"],
                     [
                         ["🔵 Azul", "Recibido", "Tu solicitud ingresó con éxito al sistema y está en fila de revisión."],
                         ["🟡 Amarillo", "En Revisión / Estudio", "El personal técnico de la Dirección está cotejando tus documentos."],
                         ["🟠 Naranja", "Prevención", "ATENCIÓN REQUERIDA: Algún documento fue observado y debes corregirlo."],
                         ["🟣 Morado", "Pago Pendiente", "¡Trámite autorizado! Ya puedes pagar tus derechos en línea o en banco."],
                         ["🟢 Verde", "Pagado", "Tu pago fue confirmado y tu documento final está en proceso de emisión."],
                         ["🟢 Verde Éxito", "Emitido / Concluido", "¡Trámite terminado! Tu permiso o constancia está lista para descargar."],
                         ["🔴 Rojo", "Rechazado", "La solicitud no fue procedente. Puedes ver la justificación legal en el detalle."]
                     ],
                     [1.5, 1.8, 3.2])

    add_image_placeholder(doc, "Bandeja 'Mis Solicitudes' (/portal/mis-solicitudes)",
                          "Listado de trámites del ciudadano con insignias de color según su estado de avance.",
                          "Tabla con Folio, Trámite, Fecha de ingreso, Estatus con distintivo de color y botón 'Ver Detalle'.")

    # CAPÍTULO 9
    add_h1(doc, "9. Cómo Subsanar una Observación o Prevención Documental")
    add_paragraph(doc, "Si tu solicitud cambia a color naranja 'Prevención', no te preocupes: esto significa que un funcionario revisó tu expediente y notó que algún archivo estaba borroso, incompleto o vencido.")
    
    add_paragraph(doc, "Pasos para corregir tu trámite:")
    add_bullet(doc, "1. Entra a 'Mis Solicitudes' y haz clic en 'Ver Detalle'.")
    add_bullet(doc, "2. Lee con atención el recuadro de 'Observaciones de la Dirección' (ejemplo: 'La identificación oficial adjunta está cortada por el reverso').")
    add_bullet(doc, "3. En la sección de documentos observados, haz clic en 'Reemplazar Archivo'.")
    add_bullet(doc, "4. Sube la fotografía o PDF corregido.")
    add_bullet(doc, "5. Presiona 'Reenviar Documentación'. Tu expediente regresará automáticamente a revisión sin costo adicional.")

    add_image_placeholder(doc, "Atención a Prevenciones (/portal/solicitud/ver/[id])",
                          "Pantalla donde se muestra el comentario del funcionario y el botón para subir el documento corregido.",
                          "Recuadro de alerta naranja con el mensaje de prevención, botón 'Subir Corrección' y botón 'Reenviar'.")

    # CAPÍTULO 10
    add_h1(doc, "10. Métodos de Pago: En Línea, Ventanilla y Sucursales BanBajío")
    add_paragraph(doc, "Cuando tu trámite esté en estado 'Pago Pendiente', podrás liquidar los derechos mediante dos opciones cómodas:")

    add_h2(doc, "Opción 1: Pago en Línea (Con Tarjeta Bancaria)")
    add_bullet(doc, "1. En la ficha de tu trámite, presiona el botón verde 'Pagar en Línea'.")
    add_bullet(doc, "2. Ingresa los datos de tu tarjeta de crédito o débito.")
    add_bullet(doc, "3. El sistema procesará el pago de forma segura y acreditará tu expediente de inmediato, cambiando a 'Pagado'.")

    add_h2(doc, "Opción 2: Pago en Ventanilla Municipal o Banco BanBajío")
    add_bullet(doc, "1. Presiona el botón 'Descargar Hoja de Pago Oficial' (COB-05).")
    add_bullet(doc, "2. Se descargará un archivo PDF con folio oficial COB-AAAA-NNNNNN, desglose del monto y código de referencia único.")
    add_bullet(doc, "3. Imprime la hoja de pago y acude a las ventanillas de la Dirección de Movilidad o a cualquier sucursal de BanBajío.")
    add_bullet(doc, "4. Tras pagar, el sistema registrará tu depósito y se emitirá tu Recibo Oficial con folio REC-AAAA-NNNNNN.")

    add_image_placeholder(doc, "Opciones de Pago y Hoja de Pago Imprimible (COB-05)",
                          "Pantalla para elegir pago con tarjeta o descargar la orden de pago para ventanilla/banco.",
                          "Botón 'Pagar con Tarjeta', botón 'Imprimir Hoja de Pago BanBajío' y desglose de tarifas.")

    # CAPÍTULO 11
    add_h1(doc, "11. Descarga e Impresión de Documentos Oficiales Emitidos")
    add_paragraph(doc, "Una vez que tu expediente alcance el estado 'Concluido / Emitido':")
    add_bullet(doc, "1. Ingresa al detalle de tu trámite en 'Mis Solicitudes'.")
    add_bullet(doc, "2. Encontrarás el botón destacado 'Descargar Documento Oficial'.")
    add_bullet(doc, "3. Podrás descargar en PDF tu Constancia de Despintado, Permiso Eventual, Orden de Plaqueo o Título de Concesión.")
    add_bullet(doc, "4. Este documento cuenta con firma electrónica, folio institucional y plena validez legal ante cualquier autoridad vial.")

    add_image_placeholder(doc, "Descarga de Documento Oficial Emitido",
                          "Vista final del expediente con el enlace de descarga del permiso o constancia autorizada.",
                          "Tarjeta de felicitación con botón 'Descargar Permiso Oficial en PDF' y resumen de vigencia.")

    # CAPÍTULO 12
    add_h1(doc, "12. Canales de Ayuda, Ubicación y Preguntas Frecuentes")
    add_paragraph(doc, "¿Tienes dudas o requieres asistencia técnica?")
    add_bullet(doc, "Dirección de Movilidad y Transporte — Presidencia Municipal de Uriangato, Gto.", "Oficinas de Atención: ")
    add_bullet(doc, "Lunes a Viernes de 8:30 a 15:30 horas.", "Horario de Atención en Ventanilla: ")
    add_bullet(doc, "soporte.movilidad@uriangato.gob.mx", "Correo de Soporte Técnico: ")

    add_h2(doc, "Preguntas Frecuentes del Ciudadano:")
    add_bullet(doc, "La plataforma está disponible las 24 horas del día. Si realizas un trámite en la noche o fin de semana, será atendido a primera hora del siguiente día hábil.", "¿Puedo ingresar un trámite en fin de semana? ")
    add_bullet(doc, "Sí. Al momento de crear tu cuenta, puedes registrar la razón social y subir el acta constitutiva y poder notarial en los trámites que lo requieran.", "¿Puedo hacer trámites como empresa o persona moral? ")
    add_bullet(doc, "No. El trámite UR-TT-T-03 de Orden de Plaqueo para cambio de unidad de transporte público es 100% gratuito conforme a la Ley de Ingresos.", "¿Tiene costo la Orden de Plaqueo? ")

    # Guardar
    doc.save(output_path)
    print(f"Guía del Ciudadano generada en: {output_path}")

if __name__ == "__main__":
    out = os.path.join(os.path.dirname(__file__), "..", "docs", "guias_usuario", "Guia_Usuario_Ciudadano.docx")
    generar_guia_ciudadano(out)

