# scripts/crear_guia_operador.py
# -*- coding: utf-8 -*-
import os
import docx
from generar_guias_word import (
    apply_document_styles, add_cover_page, add_h1, add_h2, add_h3,
    add_paragraph, add_bullet, add_callout, add_image_placeholder, add_styled_table
)

def generar_guia_operador(output_path):
    doc = docx.Document()
    apply_document_styles(doc)

    # PORTADA
    add_cover_page(
        doc,
        role_title="Operador de Ventanilla",
        subtitle="Manual de Operación en Mostrador: Atención Ciudadana, Radicación Presencial y Cobros",
        target_audience="Personal Operativo de Atención al Público en Ventanilla de Movilidad",
        version="1.0"
    )

    # ÍNDICE GENERAL
    add_h1(doc, "ÍNDICE GENERAL DEL MANUAL")
    add_bullet(doc, "1. Propósito, Misión y Alcance del Operador de Ventanilla")
    add_bullet(doc, "2. Acceso al Panel y Comprensión del Menú Dinámico por Permisos")
    add_bullet(doc, "3. Protocolo de Atención Ciudadana en Mostrador")
    add_bullet(doc, "4. Procedimiento Paso a Paso: Radicación Presencial de Trámites")
    add_bullet(doc, "5. Búsqueda por CURP y Detección de Homonimias")
    add_bullet(doc, "6. Revisión Documental y Emisión de Notificaciones de Prevención")
    add_bullet(doc, "7. Módulo de Cobros en Ventanilla y Emisión de Recibos Oficiales")
    add_bullet(doc, "8. Consulta de Citas de Verificación Física Vehicular")
    add_bullet(doc, "9. Consulta y Entrega de Formatos Oficiales Impresos")
    add_bullet(doc, "10. Arqueo Parcial, Cierre de Turno y Seguridad en Mostrador")
    add_bullet(doc, "11. Solución de Problemas Frecuentes en la Atención al Público")

    # CAPÍTULO 1
    add_h1(doc, "1. Propósito, Misión y Alcance del Operador de Ventanilla")
    add_paragraph(doc, "El Operador de Ventanilla representa la cara visible de la Dirección de Movilidad y Transporte ante la ciudadanía de Uriangato. Su misión principal es brindar una atención cálida, ágil y jurídicamente certera a los ciudadanos que acuden personalmente a realizar trámites de transporte, liquidar derechos o pagar multas.")
    
    add_paragraph(doc, "El sistema municipal le otorga herramientas especializadas para:")
    add_bullet(doc, "Recibir y registrar solicitudes de personas que no cuentan con computadora, internet o teléfono inteligente.", "Atención Inclusiva: ")
    add_bullet(doc, "Cotejar que los requisitos físicos presentados cumplan con la normatividad.", "Validación Documental: ")
    add_bullet(doc, "Cobrar en caja de ventanilla y expedir al instante el recibo oficial con validez fiscal.", "Caja Rápida: ")
    add_bullet(doc, "Auxiliar al ciudadano a entender el estatus de su trámite y cómo subsanar observaciones.", "Orientación Clara: ")

    # CAPÍTULO 2
    add_h1(doc, "2. Acceso al Panel y Comprensión del Menú Dinámico por Permisos")
    add_paragraph(doc, "El operador ingresa a través del enlace institucional:")
    add_bullet(doc, "http://[servidor-municipal]/admin", "URL de Acceso: ")
    add_bullet(doc, "Correo personal asignado y contraseña de operador.", "Credenciales: ")

    add_callout(doc, "info", "BARRA LATERAL DINÁMICA POR PERMISOS",
                "A diferencia del Administrador, la barra lateral del Operador de Ventanilla muestra ÚNICAMENTE las secciones que le han sido habilitadas. Si requiere operar una sección y no aparece en su pantalla (por ejemplo, 'Cobros' o 'Verificaciones'), solicite al Administrador General que active la casilla correspondiente en la Matriz de Permisos.")

    add_image_placeholder(doc, "Panel del Operador de Ventanilla (/admin)",
                          "Vista de la interfaz administrativa con los módulos asignados al operador.",
                          "Menú lateral con secciones habilitadas (Solicitudes, Cobros, Verificaciones, etc.) y área de trabajo central.")

    # CAPÍTULO 3
    add_h1(doc, "3. Protocolo de Atención Ciudadana en Mostrador")
    add_paragraph(doc, "Para asegurar un servicio de calidad y evitar contratiempos administrativos, siga este protocolo de 4 pasos al recibir a un ciudadano:")
    add_bullet(doc, "Salude cordialmente y pregunte qué trámite o pago desea realizar.", "1. Saludo y Diagnóstico: ")
    add_bullet(doc, "Solicite una Identificación Oficial (INE o pasaporte) y la CURP del solicitante.", "2. Identificación Oficial: ")
    add_bullet(doc, "Revise que los documentos físicos correspondan al trámite solicitado y se encuentren completos y vigentes.", "3. Revisión Preliminar: ")
    add_bullet(doc, "Proceda a la captura en el sistema siguiendo el procedimiento de radicación presencial.", "4. Captura en Sistema: ")

    # CAPÍTULO 4
    add_h1(doc, "4. Procedimiento Paso a Paso: Radicación Presencial de Trámites")
    add_paragraph(doc, "La radicación presencial (/admin/solicitudes-presenciales) permite registrar trámites para solicitantes que acuden en persona.")

    add_h2(doc, "Paso 1: Búsqueda y Validación de la CURP")
    add_paragraph(doc, "Capture la CURP del ciudadano en el buscador y presione 'Buscar'.")
    add_bullet(doc, "Caso A: El ciudadano ya existe en el sistema. El sistema carga automáticamente sus datos personales (nombre, apellidos, teléfono, correo) ahorrando tiempo de captura.")
    add_bullet(doc, "Caso B: El ciudadano no existe. El sistema abre la ventana de 'Alta Rápida de Ciudadano' para capturar su nombre y teléfono.")

    add_image_placeholder(doc, "Búsqueda por CURP y Alta de Ciudadano (/admin/solicitudes-presenciales)",
                          "Módulo de validación de identidad para evitar duplicidad de registros.",
                          "Campo de captura de CURP de 18 caracteres, botón 'Validar' y ventana emergente de alta rápida.")

    add_h2(doc, "Paso 2: Alerta y Prevención de Homonimias")
    add_paragraph(doc, "Si el nombre del ciudadano coincide parcialmente con otra persona ya registrada, el sistema desplegará un recuadro ámbar de advertencia de homonimia:")
    add_bullet(doc, "Verifique la fecha de nacimiento y la entidad federativa en la INE para confirmar si es la misma persona.")
    add_bullet(doc, "Si es la misma persona, pulse 'Vincular a esta cuenta'.")
    add_bullet(doc, "Si se trata de un homónimo real (persona distinta con nombre similar), pulse 'Crear como nuevo ciudadano'.")

    add_h2(doc, "Paso 3: Selección del Trámite y Captura Asistida")
    add_paragraph(doc, "Seleccione en el menú desplegable el trámite correspondiente:")
    add_bullet(doc, "UR-TT-T-01: Concesión de ruta fija (durante convocatorias).")
    add_bullet(doc, "UR-TT-T-02: Constancia de despintado (genera cita de inspección).")
    add_bullet(doc, "UR-TT-T-03: Orden de plaqueo para cambio de unidad (trámite gratuito).")
    add_bullet(doc, "UR-TT-T-04: Permiso eventual de transporte.")
    add_bullet(doc, "UR-TT-T-05: Permiso para cierre parcial o total de calle.")
    add_bullet(doc, "UR-TT-T-06: Cesión de concesión.")
    add_bullet(doc, "UR-TT-T-07: Permiso de carga y descarga.")

    add_h2(doc, "Paso 4: Cotejo de Documentos y Confirmación")
    add_paragraph(doc, "Llene los campos específicos del formulario y marque la casilla de cotejo de los requisitos presentados en físico. Al presionar 'Registrar Solicitud Presencial':")
    add_bullet(doc, "El sistema genera de inmediato el Folio Oficial de Solicitud (ejemplo: SOL-2026-000450).")
    add_bullet(doc, "Se emite la Hoja de Radicación en Ventanilla imprimible.")
    add_bullet(doc, "Entregue la hoja con el folio al ciudadano e infórmele cómo consultar su avance.")

    add_image_placeholder(doc, "Formulario de Trámite Presencial y Emisión de Folio",
                          "Formulario de llenado con casillas de cotejo documental y generación de folio.",
                          "Campos del trámite seleccionado, botón 'Registrar Expediente' y comprobante con folio emitido.")

    # CAPÍTULO 5
    add_h1(doc, "5. Búsqueda por CURP y Detección de Homonimias")
    add_paragraph(doc, "La correcta captura de la CURP es crítica en la ventanilla única. Cuando se crea una cuenta presencial, el sistema genera un usuario con la bandera 'sin acceso al portal'.")
    add_paragraph(doc, "Explique al ciudadano que, si en el futuro desea consultar sus trámites desde su celular o computadora personal, podrá ingresar al portal (/portal) y pulsar la opción 'Reclamar mi cuenta previa', activándola fácilmente con su CURP y un correo electrónico.")

    # CAPÍTULO 6
    add_h1(doc, "6. Revisión Documental y Emisión de Notificaciones de Prevención")
    add_paragraph(doc, "En la bandeja de 'Solicitudes' (/admin/solicitudes), el operador revisa los expedientes asignados. Al abrir un expediente, puede examinar los archivos subidos por el ciudadano:")

    add_h2(doc, "¿Cómo emitir una Prevención?")
    add_paragraph(doc, "Si un documento es borroso, no coincide con el solicitante o está vencido:")
    add_bullet(doc, "1. En el panel de dictaminación, seleccione el estado 'Prevención'.")
    add_bullet(doc, "2. En el cuadro de comentarios, redacte con total claridad qué documento presenta deficiencias y cómo debe corregirse (ejemplo: 'El comprobante de domicilio presentado tiene más de 3 meses de antigüedad; favor de adjuntar un recibo reciente de CFE o agua').")
    add_bullet(doc, "3. Presione 'Enviar Notificación'. El expediente quedará marcado en estado de prevención y el ciudadano recibirá el aviso en su portal para reemplazar el archivo sin pagar nuevamente.")

    add_image_placeholder(doc, "Panel de Dictamen y Emisión de Prevenciones (/admin/solicitudes/ver/[id])",
                          "Vista de revisión de archivos probatorios y formulario para enviar observaciones.",
                          "Visor de documentos adjuntos, selector de estado 'Prevención' y campo de observaciones detalladas.")

    # CAPÍTULO 7
    add_h1(doc, "7. Módulo de Cobros en Ventanilla y Emisión de Recibos Oficiales")
    add_paragraph(doc, "Cuando un ciudadano acude a pagar los derechos de un trámite aprobado o una multa de tránsito:")
    add_bullet(doc, "1. Ingrese a la sección 'Cobros' (/admin/cobros).")
    add_bullet(doc, "2. Ingrese el Folio del Trámite o Folio de Cobro proporcionado por el ciudadano.")
    add_bullet(doc, "3. Verifique en pantalla el concepto, el nombre del solicitante y el monto exacto en pesos.")
    add_bullet(doc, "4. Presione el botón 'Cobrar en Ventanilla'.")
    add_bullet(doc, "5. Seleccione el medio de cobro: 'Efectivo' o 'Tarjeta de Crédito/Débito'.")
    add_bullet(doc, "6. Confirme la transacción. El sistema generará el Recibo Oficial con folio REC-AAAA-NNNNNN.")
    add_bullet(doc, "7. Imprima el recibo oficial, coloque el sello de pagado y entregue el original al contribuyente.")

    add_image_placeholder(doc, "Formulario de Cobro en Mostrador (/cobros/ver/[id])",
                          "Pantalla de registro de pago en ventanilla y selección de medio de pago.",
                          "Monto a pagar, selector de método de cobro (efectivo/terminal) y botón 'Generar Recibo Oficial'.")

    # CAPÍTULO 8
    add_h1(doc, "8. Consulta de Citas de Verificación Física Vehicular")
    add_paragraph(doc, "En la sección 'Verificaciones' (/admin/verificaciones), el operador puede consultar la agenda de citas programadas para inspección físico-mecánica de vehículos (trámites UR-TT-T-02 y UR-TT-T-04):")
    add_bullet(doc, "Identificar las unidades vehiculares que tienen cita agendada en la fecha actual.")
    add_bullet(doc, "Registrar la asistencia del concesionario o propietario en las instalaciones de la Dirección.")
    add_bullet(doc, "Capturar el resultado técnico emitido por el inspector mecánico (Aprobada / Rechazada con observaciones).")

    add_image_placeholder(doc, "Agenda de Verificaciones Físicas (/admin/verificaciones)",
                          "Calendario y listado de citas para inspección mecánica de unidades de transporte.",
                          "Tabla con fecha, hora, placa del vehículo, solicitante y estado de la inspección.")

    # CAPÍTULO 9
    add_h1(doc, "9. Consulta y Entrega de Formatos Oficiales Impresos")
    add_paragraph(doc, "En la sección 'Formatos' (/admin/formatos), el operador tiene acceso a todas las plantillas y formatos oficiales del municipio en formato PDF descargable:")
    add_bullet(doc, "Formatos de solicitud para personas físicas y personas morales.")
    add_bullet(doc, "Guías de requisitos documentales por trámite.")
    add_bullet(doc, "Si un ciudadano no tiene medios digitales, el operador puede descargar e imprimir el formato en blanco en su impresora para entrega inmediata.")

    # CAPÍTULO 10
    add_h1(doc, "10. Arqueo Parcial, Cierre de Turno y Seguridad en Mostrador")
    add_paragraph(doc, "Al finalizar el horario de atención al público, el operador de ventanilla debe:")
    add_bullet(doc, "1. Realizar el conteo físico del efectivo cobrado en la caja de ventanilla.")
    add_bullet(doc, "2. Sumar los comprobantes o vouchers de la terminal bancaria.")
    add_bullet(doc, "3. Contrastar ambos montos contra el reporte de corte de su usuario en /admin/cobros/corte.")
    add_bullet(doc, "4. Entregar los valores al área de Tesorería o al Administrador General debidamente firmados.")
    add_bullet(doc, "5. CERRAR SU SESIÓN en el equipo de cómputo para salvaguardar la seguridad del sistema.")

    # CAPÍTULO 11
    add_h1(doc, "11. Solución de Problemas Frecuentes en la Atención al Público")
    add_h2(doc, "¿Qué hacer si el sistema indica que la CURP ya está registrada pero el ciudadano no recuerda haber creado cuenta?")
    add_paragraph(doc, "Verifique el correo electrónico registrado con el ciudadano. Si el ciudadano no tiene acceso a ese correo, el Administrador General puede actualizar el correo desde /admin/usuarios.")

    add_h2(doc, "¿Qué hacer si el pago se realizó por transferencia o depósito en BanBajío?")
    add_paragraph(doc, "El ciudadano debe presentar su comprobante bancario con la referencia visible. Si cuenta con permiso de banco, busque la referencia en /admin/cobros/banco y concilie el pago; en caso contrario, turne el expediente al Administrador.")

    # Guardar
    doc.save(output_path)
    print(f"Guía del Operador de Ventanilla generada en: {output_path}")

if __name__ == "__main__":
    out = os.path.join(os.path.dirname(__file__), "..", "docs", "guias_usuario", "Guia_Usuario_Operador_Ventanilla.docx")
    generar_guia_operador(out)

