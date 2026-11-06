# scripts/crear_guia_ciudadano.py
# -*- coding: utf-8 -*-
import os
import docx
from generar_guias_word import (
    apply_document_styles, add_cover_page, add_h1, add_h2,
    add_paragraph, add_bullet, add_callout, add_screenshots, add_styled_table
)

CAPTURAS = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "docs", "guias_usuario", "capturas_ciudadano")

def generar_guia_ciudadano(output_path):
    doc = docx.Document()
    apply_document_styles(doc)

    numero_figura = [0]
    def figura(nombres, caption, **kwargs):
        numero_figura[0] += 1
        rutas = [os.path.join(CAPTURAS, f"{n}.jpg") for n in nombres]
        add_screenshots(doc, rutas, f"Figura {numero_figura[0]}. {caption}", **kwargs)

    # PORTADA
    add_cover_page(
        doc,
        role_title="Ciudadano y Solicitante",
        subtitle="Guía Paso a Paso para la Realización de Trámites de Movilidad en Línea, Pagos y Obtención de Documentos",
        target_audience="Ciudadanos, Concesionarios, Choferes y Transportistas de Uriangato",
        version="1.1"
    )

    # ÍNDICE GENERAL
    add_h1(doc, "ÍNDICE GENERAL DE LA GUÍA")
    add_bullet(doc, "1. Bienvenido a la Ventanilla Digital de Movilidad de Uriangato")
    add_bullet(doc, "2. Preparación de Documentos Digitales (Formatos y Consejos)")
    add_bullet(doc, "3. Creación de Cuenta, Inicio de Sesión y Recuperación de Contraseña")
    add_bullet(doc, "4. Si Iniciaste tu Trámite en Ventanilla: Cómo Vincular tu Cuenta")
    add_bullet(doc, "5. Conociendo el Portal Ciudadano (Computadora y Teléfono Celular)")
    add_bullet(doc, "6. Catálogo de los Siete Trámites Municipales Oficiales")
    add_bullet(doc, "7. Procedimiento Paso a Paso: Cómo Iniciar una Solicitud en Línea")
    add_bullet(doc, "8. Seguimiento de Solicitudes y Significado de los Estatus")
    add_bullet(doc, "9. Cómo Atender una Prevención u Observación")
    add_bullet(doc, "10. Cómo Pagar: En Línea, Tesorería, Jueces Calificadores y BanBajío")
    add_bullet(doc, "11. Cómo Obtener tu Permiso o Documento Oficial")
    add_bullet(doc, "12. Canales de Ayuda, Ubicación y Preguntas Frecuentes")

    # CAPÍTULO 1
    add_h1(doc, "1. Bienvenido a la Ventanilla Digital de Movilidad de Uriangato")
    add_paragraph(doc, "El Gobierno Municipal de Uriangato pone a tu disposición la Ventanilla Digital de Movilidad y Transporte. Esta plataforma te permite iniciar tus trámites de movilidad desde tu casa, negocio o teléfono celular, y consultar su avance sin acudir repetidamente a las oficinas.")

    add_paragraph(doc, "Ventajas principales para el ciudadano:")
    add_bullet(doc, "Inicia solicitudes y adjunta documentos a cualquier hora, todos los días.", "Acceso Continuo: ")
    add_bullet(doc, "Consulta en qué etapa va tu expediente con su folio único de seguimiento.", "Transparencia Total: ")
    add_bullet(doc, "Imprime tu hoja de pago con el importe y las opciones para pagar.", "Facilidad de Pago: ")
    add_bullet(doc, "Si fuiste primero a las oficinas, puedes ver ese mismo trámite en línea.", "Todo en un Solo Lugar: ")

    add_paragraph(doc, "Para empezar, entra a la portada de la Ventanilla Digital. Ahí encontrarás los botones 'Entrar al portal', si ya tienes cuenta, y 'Crear mi cuenta', si es tu primera vez.")
    figura(["portada"], "Portada de la Ventanilla Digital.", max_width=4.8)

    # CAPÍTULO 2
    add_h1(doc, "2. Preparación de Documentos Digitales (Formatos y Consejos)")
    add_paragraph(doc, "Antes de iniciar tu trámite en línea, ten a la mano los documentos requeridos en formato digital. Los formularios aceptan lo siguiente:")
    add_bullet(doc, "Para actas, pólizas, facturas, comprobantes y contratos.", "Archivos PDF (.pdf): ")
    add_bullet(doc, "Para fotografías claras de credencial de elector (INE), tarjeta de circulación o placas.", "Imágenes JPG o PNG (.jpg, .jpeg, .png): ")
    add_bullet(doc, "Cada archivo de un trámite puede pesar hasta 10 MB. La fotografía de tu INE en 'Mi Perfil' puede pesar hasta 5 MB por lado y debe ser JPG o PNG.", "Tamaño Máximo: ")

    add_callout(doc, "tip", "CONSEJOS PARA TOMAR BUENAS FOTOS A TUS DOCUMENTOS CON EL CELULAR",
                "• Coloca el documento sobre una mesa plana con buena iluminación natural.\n• Evita el reflejo del flash sobre credenciales plastificadas.\n• Asegúrate de que las cuatro esquinas del documento sean visibles y los textos sean completamente legibles.\n• Un documento borroso o incompleto puede provocar una prevención y demorar tu trámite.")

    # CAPÍTULO 3
    add_h1(doc, "3. Creación de Cuenta, Inicio de Sesión y Recuperación de Contraseña")
    add_paragraph(doc, "Para realizar trámites por primera vez, debes registrarte en el portal.")

    add_h2(doc, "Paso a Paso para Crear una Cuenta:")
    add_bullet(doc, "1. En la portada presiona 'Crear mi cuenta'.")
    add_bullet(doc, "2. Escribe tu CURP. Es opcional, pero si ya hiciste un trámite en ventanilla debes capturarla (ver capítulo 4).")
    add_bullet(doc, "3. Captura tu nombre, apellidos, teléfono, estado, ciudad, dirección y correo electrónico.")
    add_bullet(doc, "4. Elige un nombre de usuario. El RFC es opcional.")
    add_bullet(doc, "5. Crea una contraseña de mínimo 8 caracteres, con al menos una letra y un número, y confírmala.")
    add_bullet(doc, "6. Si quieres, adjunta tu INE por ambos lados. También puedes hacerlo después en 'Mi Perfil'.")
    add_bullet(doc, "7. Marca la casilla para aceptar los Términos y el Aviso de Privacidad.")
    add_bullet(doc, "8. Presiona el botón verde 'Registrarme'.")

    figura(["registro"], "Formulario para crear tu cuenta ciudadana.", max_height=5.9)

    add_h2(doc, "Iniciar Sesión")
    add_paragraph(doc, "En 'Entrar al portal', escribe tu usuario o correo y tu contraseña, y presiona 'Entrar al portal'.")
    figura(["login"], "Pantalla de inicio de sesión.", max_width=3.4)

    add_h2(doc, "¿Olvidaste tu contraseña?")
    add_paragraph(doc, "En la pantalla de inicio de sesión haz clic en '¿La olvidaste?', escribe tu correo registrado y presiona 'Enviar enlace'. Recibirás un correo con un enlace para crear una contraseña nueva. El enlace funciona durante 24 horas.")
    figura(["recuperar"], "Recuperación de contraseña.", max_width=3.4)

    # CAPÍTULO 4
    add_h1(doc, "4. Si Iniciaste tu Trámite en Ventanilla: Cómo Vincular tu Cuenta")
    add_callout(doc, "info", "CASO ESPECIAL: SI ACUDISTE PRIMERO A LAS OFICINAS MUNICIPALES",
                "Si fuiste a la Dirección de Movilidad y el personal registró tu trámite en mostrador, el sistema ya tiene un expediente a tu nombre, pero sin contraseña. No se crea una cuenta duplicada: al registrarte con tu CURP, el portal la reconoce y te entrega esos trámites.")

    add_paragraph(doc, "Cómo vincular tu cuenta:")
    add_bullet(doc, "1. Presiona 'Crear mi cuenta', igual que en el capítulo 3.")
    add_bullet(doc, "2. Captura en el campo CURP la misma CURP que diste en ventanilla.")
    figura(["campo_curp"], "El campo CURP liga los trámites que registraste en ventanilla.", max_width=4.2)
    add_bullet(doc, "3. Llena el resto del formulario, crea tu contraseña y presiona 'Registrarme'.")
    add_bullet(doc, "4. Verás el mensaje 'Vinculamos los trámites que ya tenías registrados en ventanilla. Ya puedes iniciar sesión.'")
    add_bullet(doc, "5. Inicia sesión y encontrarás tu trámite en 'Mis solicitudes', con todo su historial.")
    figura(["cuenta_vinculada"], "Mensaje de confirmación al vincular tu cuenta.", max_width=3.4)

    add_callout(doc, "warning", "SI EL PORTAL NO TE DEJA REGISTRARTE",
                "• 'Ya existe una cuenta con esta CURP': ya tienes acceso. Inicia sesión o recupera tu contraseña.\n• 'La cuenta asociada a esta CURP está desactivada': acude a la ventanilla de la Dirección de Movilidad.")

    # CAPÍTULO 5
    add_h1(doc, "5. Conociendo el Portal Ciudadano (Computadora y Teléfono Celular)")
    add_paragraph(doc, "Al ingresar verás tu panel de 'Inicio':")
    add_bullet(doc, "Muestra el total de tus solicitudes, cuántas están en trámite, concluidas y con pago pendiente, además de los trámites disponibles y tus últimas solicitudes.", "Pantalla de Inicio: ")
    add_bullet(doc, "En la computadora el menú está arriba: Inicio, Trámites, Mis solicitudes y tu nombre. En el celular hay una barra inferior con Inicio, Trámites, Solicitudes y Perfil.", "Diseño Adaptado a Celulares: ")
    add_bullet(doc, "Te permite actualizar tus datos, tu ubicación y tu contraseña, y subir tu INE. El nombre de usuario no se puede editar.", "Mi Perfil: ")

    figura(["inicio_pc"], "Panel de inicio en computadora.")
    figura(["inicio_celular", "mis_solicitudes_celular"], "El portal en el celular, con la barra de navegación inferior.", max_width=4.4, max_height=4.8)
    figura(["perfil"], "Pantalla 'Mi Perfil'.", max_height=7.0)

    # CAPÍTULO 6
    add_h1(doc, "6. Catálogo de los Siete Trámites Municipales Oficiales")
    add_paragraph(doc, "En la sección 'Trámites' encontrarás los siete procedimientos que puedes gestionar. Los costos y documentos de esta tabla corresponden a las fichas oficiales del municipio y a los formularios del portal:")

    add_styled_table(doc,
                     ["Clave", "Trámite de Movilidad", "Costo Oficial Vigente", "Documentos que se Adjuntan"],
                     [
                         ["UR-TT-T-01", "Concesión de transporte público de ruta fija (solo con convocatoria abierta)", "$9,055.20", "Acta de nacimiento o constitutiva, Constancia de Situación Fiscal, capacidad financiera, constancia de residencia, antecedentes no penales, proyecto técnico de rutas y horarios, fichas técnicas y facturas de vehículos."],
                         ["UR-TT-T-02", "Constancia de despintado de unidad", "$64.90", "Identificación oficial y factura del vehículo."],
                         ["UR-TT-T-03", "Orden de plaqueo para cambio de unidad", "Sin costo", "Solicitud firmada, título de concesión, identificación oficial, factura del vehículo y revista físico-mecánica. Persona moral: acta constitutiva y poder notarial."],
                         ["UR-TT-T-04", "Permiso eventual de transporte", "$156.94", "Solicitud por escrito, proyecto de cantidad de vehículos, frecuencia de servicios, acta y fondo de garantía o póliza de seguro."],
                         ["UR-TT-T-05", "Permiso de cierre parcial o total de calle", "$287.00", "Identificación oficial y solicitud escrita o croquis del tramo."],
                         ["UR-TT-T-06", "Cesión de concesión de transporte", "$9,055.20", "Título de concesión e identificaciones y actas de las personas involucradas. El formulario pide lo que corresponde a cada causa de la cesión."],
                         ["UR-TT-T-07", "Permiso de carga y descarga de mercancías", "Ver tabla siguiente", "Identificación oficial, tarjeta de circulación y factura, remisión u orden de compra."]
                     ],
                     [0.95, 1.6, 0.8, 3.15])

    add_paragraph(doc, "Costos del Permiso de Carga y Descarga (UR-TT-T-07):")
    add_styled_table(doc,
                     ["Periodo", "Particular (por camión)", "Empresa establecida en el municipio (hasta 15 camiones)"],
                     [
                         ["Día", "$191.00", "$573.00"],
                         ["Mes", "$1,151.00", "$5,732.00"],
                         ["Semestre", "$5,752.00", "$28,662.00"],
                         ["Año", "$11,503.00", "$57,325.00"]
                     ],
                     [1.5, 2.2, 2.8])

    figura(["catalogo"], "Catálogo Oficial de Trámites, con buscador y filtros.", max_height=7.4)

    # CAPÍTULO 7
    add_h1(doc, "7. Procedimiento Paso a Paso: Cómo Iniciar una Solicitud en Línea")
    add_paragraph(doc, "Todos los trámites siguen los mismos pasos. Como ejemplo se muestra el Permiso para Cierre de Calle:")
    add_bullet(doc, "1. En 'Trámites', localiza el que necesitas y presiona 'Iniciar trámite'. Para la Concesión de Transporte, el botón es 'Ver convocatoria / Postular'.")
    add_bullet(doc, "2. Si quieres revisar los requisitos, presiona 'Descargar formato' en el panel derecho.")
    add_bullet(doc, "3. Llena los campos. Los marcados con asterisco (*) son obligatorios.")
    add_bullet(doc, "4. En la sección de documentos, presiona 'Seleccionar archivo' en cada requisito y elige tu PDF o foto.")
    add_bullet(doc, "5. Presiona 'Enviar Solicitud'.")

    figura(["formulario"], "Formulario de solicitud con la carga de documentos.", max_height=7.0)

    add_paragraph(doc, "Al terminar verás el 'Resumen de solicitud' con tu Folio, la fecha, el monto y los datos capturados. El folio es una clave de 20 letras y números (ejemplo: 6B072D9B222C4928A99F). Guárdalo: con él se identifica tu trámite en cualquier consulta.")
    figura(["solicitud_enviada"], "Resumen de la solicitud enviada, con su folio.")

    add_h2(doc, "Qué Sigue Después de Enviar")
    add_styled_table(doc,
                     ["Trámite", "Siguiente paso"],
                     [
                         ["UR-TT-T-01", "Tu postulación se evalúa junto con las demás de la convocatoria."],
                         ["UR-TT-T-02", "El portal te lleva a agendar tu cita de inspección. La unidad debe presentarse totalmente despintada. El pago se autoriza después de la verificación."],
                         ["UR-TT-T-03", "La Dirección revisa tu expediente y, si está correcto, lo aprueba. No hay pago."],
                         ["UR-TT-T-04", "La Dirección revisa tu solicitud y después autoriza el pago."],
                         ["UR-TT-T-05", "La Dirección valida tu solicitud y después autoriza el pago."],
                         ["UR-TT-T-06", "La Dirección revisa los documentos de la cesión."],
                         ["UR-TT-T-07", "La solicitud pasa directamente a 'Pago pendiente'."]
                     ],
                     [1.3, 5.2])

    # CAPÍTULO 8
    add_h1(doc, "8. Seguimiento de Solicitudes y Significado de los Estatus")
    add_paragraph(doc, "En cualquier momento puedes consultar tus solicitudes en 'Mis solicitudes'. Puedes buscar por folio o por nombre del trámite, y filtrar por Pago pendiente, En revisión, Aprobados / Concluidos u Observación / Rechazo. Cada solicitud tiene una etiqueta de color que indica su situación:")

    add_styled_table(doc,
                     ["Color de la Etiqueta", "Estatus", "Qué Significa para Ti"],
                     [
                         ["Azul claro", "Recibido, Cita agendada, Autorizado para pago", "Tu solicitud ingresó al sistema o avanzó a su siguiente etapa."],
                         ["Azul", "En revisión, En validación, En revisión documental", "El personal de la Dirección está revisando tu expediente."],
                         ["Amarillo", "Prevención", "ATENCIÓN REQUERIDA: hay una observación que debes atender (capítulo 9)."],
                         ["Amarillo", "Pago pendiente", "Trámite autorizado para pago (capítulo 10)."],
                         ["Verde", "Verificado, Aprobado, Pagado", "Tu trámite superó esa etapa."],
                         ["Verde", "Permiso emitido, Constancia emitida, Título emitido, Vigente", "Tu documento fue emitido (capítulo 11)."],
                         ["Rojo", "Rechazado, Vencido", "La solicitud no procedió o el permiso venció. El motivo aparece en el Historial de estatus."]
                     ],
                     [1.2, 2.4, 2.9])

    figura(["mis_solicitudes"], "Bandeja 'Mis solicitudes', con folio, estatus, fecha, monto y acciones.")

    add_paragraph(doc, "Presiona 'Detalle' para abrir la solicitud. Arriba verás la 'Evolución del trámite' por etapas y, al lado, el 'Historial de estatus' con la fecha y el comentario de cada cambio.")

    # CAPÍTULO 9
    add_h1(doc, "9. Cómo Atender una Prevención u Observación")
    add_paragraph(doc, "Si tu solicitud cambia a 'Prevención', significa que un funcionario revisó tu expediente y necesita que aclares o completes algo. No es un rechazo.")

    add_paragraph(doc, "Pasos para atenderla:")
    add_bullet(doc, "1. Entra a 'Mis solicitudes' y presiona 'Detalle'.")
    add_bullet(doc, "2. Verás el aviso rojo 'Prevención / Aclaración Requerida'.")
    add_bullet(doc, "3. Lee en el 'Historial de estatus' el comentario de la Dirección, que indica qué falta.")
    add_bullet(doc, "4. Acude a la Dirección de Movilidad con el documento o escríbele a sus datos de contacto (capítulo 12), mencionando tu folio.")
    add_bullet(doc, "5. Cuando la Dirección reciba lo que falta, tu expediente regresará a revisión.")

    add_callout(doc, "info", "LA CORRECCIÓN NO SE SUBE POR EL PORTAL",
                "Por ahora el portal te muestra la observación, pero no tiene un botón para reemplazar archivos. La documentación complementaria se entrega directamente a la Dirección.")

    figura(["prevencion"], "Solicitud en Prevención, con el aviso rojo y la evolución del trámite.")
    figura(["prevencion_historial"], "El comentario de la Dirección aparece en el Historial de estatus.", max_width=2.8)

    # CAPÍTULO 10
    add_h1(doc, "10. Cómo Pagar: En Línea, Tesorería, Jueces Calificadores y BanBajío")
    add_paragraph(doc, "Cuando tu trámite esté en 'Pago pendiente', verás un aviso amarillo con el monto a cubrir y podrás pagar de las siguientes maneras.")

    figura(["pago_detalle"], "Solicitud en Pago pendiente, con los botones 'Hoja de pago' y 'Pagar en línea'.")

    add_h2(doc, "Opción 1: Con la Hoja de Pago (Todos los Trámites con Costo)")
    add_bullet(doc, "1. En el detalle de tu solicitud presiona el botón verde 'Hoja de pago'.")
    add_bullet(doc, "2. Se abrirá tu hoja con el folio del trámite, el folio de cobro (COB-AAAA-NNNNNN), el total y la referencia bancaria. Presiona 'Imprimir hoja'.")
    add_bullet(doc, "3. Paga en cualquiera de los lugares que indica la hoja: el Área de Jueces Calificadores de la Dirección, la Tesorería Municipal o BanBajío, con la referencia.")
    add_bullet(doc, "4. Al registrarse tu pago, el trámite cambia a 'Pagado' y el cobro queda con un folio de recibo (REC-AAAA-NNNNNN).")

    figura(["hoja_pago"], "Hoja de ayuda para el pago, con los lugares donde puedes pagar.", max_height=6.4)

    add_callout(doc, "warning", "LA HOJA DE PAGO NO ES UN RECIBO",
                "La hoja es informativa y no sustituye el recibo oficial de pago. Consérvala junto con tu folio para cualquier aclaración. El monto puede cambiar si el trámite se rechaza o si cambia la tarifa antes del pago.")

    add_h2(doc, "Opción 2: Pago en Línea (Trámites UR-TT-T-04, UR-TT-T-05 y UR-TT-T-07)")
    add_bullet(doc, "1. Presiona 'Pagar en línea' en el aviso amarillo, o 'Pagar' en 'Mis solicitudes'.")
    add_bullet(doc, "2. Revisa el resumen y confirma el pago.")
    add_bullet(doc, "3. Al confirmarse, tu permiso se emite de inmediato.")

    add_callout(doc, "warning", "PAGO EN LÍNEA EN MODO DE DEMOSTRACIÓN",
                "El pago en línea funciona en modo de demostración hasta que el municipio active el servicio con BanBajío: no hace cargos reales ni pide datos de tarjeta. Mientras tanto, para pagar usa la Hoja de Pago.")

    # CAPÍTULO 11
    add_h1(doc, "11. Cómo Obtener tu Permiso o Documento Oficial")
    add_paragraph(doc, "La forma de obtener tu documento depende del trámite:")
    add_styled_table(doc,
                     ["Trámite", "Cómo se obtiene"],
                     [
                         ["UR-TT-T-05 Cierre de Calle", "Desde el resumen de tu solicitud: presiona 'Ver permiso' y después 'Imprimir permiso'."],
                         ["UR-TT-T-01, UR-TT-T-02 y UR-TT-T-03", "El título, la constancia o la orden se entregan en la Dirección de Movilidad cuando el trámite concluye."],
                         ["UR-TT-T-04, UR-TT-T-06 y UR-TT-T-07", "Consulta el estatus y las vigencias en el detalle de tu solicitud. Si hay archivos en 'Documentos adjuntos', se bajan con el botón 'Descargar'; si necesitas el documento impreso, acude a la Dirección."]
                     ],
                     [2.2, 4.3])

    figura(["permiso_resumen"], "Resumen de un permiso emitido, con el botón 'Ver permiso'.")
    figura(["permiso"], "Permiso para Cierre de Calle, listo para imprimir. Los datos son de ejemplo.", max_height=6.6)

    add_callout(doc, "info", "VIGENCIA DEL PERMISO DE CIERRE DE CALLE",
                "El permiso es válido únicamente durante la fecha y el horario autorizados.")

    # CAPÍTULO 12
    add_h1(doc, "12. Canales de Ayuda, Ubicación y Preguntas Frecuentes")
    add_paragraph(doc, "¿Tienes dudas o requieres asistencia?")
    add_bullet(doc, "Dirección de Movilidad y Transporte Municipal, Blvd. Juan Pablo II núm. 40, col. Lázaro Cárdenas, Uriangato, Gto.", "Oficinas de Atención: ")
    add_bullet(doc, "Lunes a viernes de 8:00 a 15:00 y de 16:00 a 19:00; sábado de 8:00 a 15:00.", "Horario del Área de Jueces Calificadores: ")
    add_bullet(doc, "445 457 8000", "Teléfono: ")
    add_bullet(doc, "movilidaduriangato@gmail.com", "Correo: ")

    add_h2(doc, "Preguntas Frecuentes del Ciudadano:")
    add_bullet(doc, "Sí. El portal está disponible a cualquier hora. Tu solicitud será revisada en los horarios de la Dirección.", "¿Puedo ingresar un trámite en fin de semana? ")
    add_bullet(doc, "Sí. Tu cuenta es personal, pero en los trámites que lo permiten el formulario te deja indicar si eres persona física o moral y pide el acta constitutiva y el poder notarial cuando corresponde.", "¿Puedo hacer trámites como empresa o persona moral? ")
    add_bullet(doc, "No. La Orden de Plaqueo (UR-TT-T-03) no tiene costo, según su ficha oficial.", "¿Tiene costo la Orden de Plaqueo? ")
    add_bullet(doc, "Entra a 'Mis solicitudes': ahí está el folio de cada trámite, con un botón para copiarlo.", "¿Dónde veo mi folio? ")
    add_bullet(doc, "Abre el 'Detalle' de la solicitud. El motivo aparece en el 'Historial de estatus'.", "¿Por qué rechazaron mi solicitud? ")

    # Guardar
    doc.save(output_path)
    print(f"Guía del Ciudadano generada en: {output_path}")

if __name__ == "__main__":
    out = os.path.join(os.path.dirname(__file__), "..", "docs", "guias_usuario", "Guia_Usuario_Ciudadano.docx")
    generar_guia_ciudadano(out)
