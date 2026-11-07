# scripts/partes_manual/parte3_operador.py
# -*- coding: utf-8 -*-
import docx
from generar_guias_word import (
    add_h1, add_h2, add_h3, add_paragraph, add_bullet, add_callout, add_styled_table
)

def agregar_parte3(doc, add_part_header, add_figure_image):
    add_part_header(
        doc,
        part_num="III",
        part_title="Guía de Operación del Operador de Ventanilla (Panel Mostrador)",
        part_description="Manual exhaustivo para la atención presencial al público, radicación asistida de trámites, control de homonimia por CURP, revisión documental, cobro en mostrador, agenda de inspecciones y corte de caja."
    )

    # CAPÍTULO 16
    add_h1(doc, "16. Misión, Responsabilidades y Menú Dinámico por Permisos")
    add_paragraph(doc, "El Operador de Ventanilla es el funcionario responsable de brindar servicio directo a la ciudadanía en las instalaciones de la Dirección de Movilidad y Transporte. Su labor permite que las personas que no cuentan con medios digitales puedan acceder a los mismos trámites y derechos bajo los mismos principios de certeza jurídica.")

    add_paragraph(doc, "A diferencia del Administrador General, la barra lateral del operador es dinámica y muestra exclusivamente las secciones que le han sido concedidas a través de la matriz de permisos configurables.")

    add_figure_image(doc, "figura_01.jpg", "operador",
                     "Figura 1. Panel de inicio del operador de ventanilla con menú lateral según permisos.", width_inches=6.2)

    # CAPÍTULO 17
    add_h1(doc, "17. Protocolo de Atención Ciudadana y Búsqueda por CURP")
    add_paragraph(doc, "Todo trámite presencial en ventanilla (/admin/solicitudes-presenciales) inicia obligatoriamente con la identificación del ciudadano mediante su Clave Única de Registro de Población (CURP).")

    add_figure_image(doc, "figura_02.jpg", "operador",
                     "Figura 2. Paso 1 del registro presencial: búsqueda y validación de la CURP.", width_inches=6.0)

    # CAPÍTULO 18
    add_h1(doc, "18. Radicación Presencial Asistida y Control de Homonimias")
    add_paragraph(doc, "Si la CURP no existe en la base de datos, el sistema despliega el módulo de 'Alta Rápida de Ciudadano' para capturar su nombre, apellidos, teléfono y correo electrónico:")

    add_figure_image(doc, "figura_03.jpg", "operador",
                     "Figura 3. Alta rápida de solicitante cuando la CURP no existe en el sistema.", width_inches=6.0)

    add_figure_image(doc, "figura_04.jpg", "operador",
                     "Figura 4. Cuenta creada: el ciudadano queda identificado y los trámites se habilitan.", width_inches=6.0)

    add_paragraph(doc, "Alerta de Homonimia:")
    add_paragraph(doc, "Si el nombre del ciudadano coincide parcialmente con otra persona ya registrada, el sistema emite una advertencia preventiva de homonimia para evitar duplicar registros en el padrón municipal:")

    add_figure_image(doc, "figura_05.jpg", "operador",
                     "Figura 5. Aviso de posible duplicado por nombre (control de homonimia).", width_inches=6.0)

    add_paragraph(doc, "Captura del Formulario y Generación de Folio:")
    add_paragraph(doc, "Una vez validada la identidad, el operador selecciona el trámite a gestionar, captura los datos vehiculares o del evento, coteja físicamente los documentos originales y registra la solicitud:")

    add_figure_image(doc, "figura_06.jpg", "operador",
                     "Figura 6. Formulario del trámite presencial con casillas de cotejo documental.", width_inches=6.2)

    add_figure_image(doc, "figura_07.jpg", "operador",
                     "Figura 7. Solicitud registrada con éxito, generando su Folio Oficial.", width_inches=6.0)

    # CAPÍTULO 19
    add_h1(doc, "19. Gestión de Usuarios y Padrón Ciudadano en Mostrador")
    add_paragraph(doc, "En la sección 'Usuarios y Ciudadanos' (/admin/usuarios), el operador puede consultar cuentas ciudadanas para verificar expedientes históricos o actualizar teléfonos de contacto solicitados por el contribuyente:")

    add_figure_image(doc, "figura_08.jpg", "operador",
                     "Figura 8. Sección 'Usuarios y ciudadanos' con buscador y filtros.", width_inches=6.2)

    # CAPÍTULO 20
    add_h1(doc, "20. Revisión Documental y Emisión de Prevenciones")
    add_paragraph(doc, "En la bandeja de 'Solicitudes' (/admin/solicitudes), el operador examina los expedientes entrantes tanto de la web como de ventanilla física, utilizando los filtros por estatus, trámite y rango de fechas:")

    add_figure_image(doc, "figura_09.jpg", "operador",
                     "Figura 9. Bandeja de solicitudes con sus filtros de búsqueda avanzada.", width_inches=6.2)

    add_paragraph(doc, "Al abrir un expediente, el operador revisa los datos generales, la huella criptográfica de los archivos adjuntos y el historial de cambios de estado:")

    add_figure_image(doc, "figura_10.jpg", "operador",
                     "Figura 10. Detalle del expediente con datos generales y acciones de seguimiento.", width_inches=6.2)

    add_paragraph(doc, "Emisión de Notificación de Prevención:")
    add_paragraph(doc, "Si un requisito documental carece de nitidez, vigencia o autenticidad, el operador selecciona la acción 'Prevención' en el panel de seguimiento e introduce observaciones claras y fundadas:")

    add_figure_image(doc, "figura_11.jpg", "operador",
                     "Figura 11. Acciones de seguimiento de un expediente en revisión documental.", width_inches=6.0)

    # CAPÍTULO 21
    add_h1(doc, "21. Módulo de Cobros en Mostrador y Expedición de Recibos")
    add_paragraph(doc, "En la sección 'Cobros' (/admin/cobros), el operador de ventanilla localiza la orden de cobro mediante el Folio de Solicitud o Folio de Cobro COB-AAAA-NNNNNN:")

    add_figure_image(doc, "figura_12.jpg", "operador",
                     "Figura 12. Listado de cobros con botones de acción para ventanilla.", width_inches=6.2)

    add_paragraph(doc, "Registro del Pago en Ventanilla:")
    add_paragraph(doc, "Al presionar 'Registrar Cobro', se abre la ventana emergente para seleccionar el medio de pago recibido (Efectivo o Tarjeta Bancaria) y confirmar la transacción:")

    add_figure_image(doc, "figura_13.jpg", "operador",
                     "Figura 13. Ventana para registrar el cobro en mostrador.", width_inches=5.8)

    add_paragraph(doc, "El sistema genera de forma automática el Recibo Oficial con folio REC-AAAA-NNNNNN, el cual se imprime para firma y entrega al ciudadano:")

    add_figure_image(doc, "figura_14.jpg", "operador",
                     "Figura 14. Cobro registrado con éxito con su folio de recibo REC emitido.", width_inches=5.8)

    # CAPÍTULO 22
    add_h1(doc, "22. Conciliación de Pagos Bancarios en BanBajío (COB-04)")
    add_paragraph(doc, "Cuando un solicitante acude con su comprobante de pago realizado en sucursal BanBajío, el operador ingresa al módulo de banco (/admin/cobros/banco) para buscar la referencia bancaria y conciliar el expediente:")

    add_figure_image(doc, "figura_15.jpg", "operador",
                     "Figura 15. Búsqueda y validación de cobro por su referencia bancaria de BanBajío.", width_inches=6.0)

    # CAPÍTULO 23
    add_h1(doc, "23. Agenda y Registro de Verificaciones Físicas (UR-02 y UR-04)")
    add_paragraph(doc, "En 'Verificaciones Físicas' (/admin/verificaciones), el operador supervisa las citas agendadas para inspección mecánico-visual de vehículos:")

    add_figure_image(doc, "figura_16.jpg", "operador",
                     "Figura 16. Agenda de citas programadas para verificaciones físicas vehiculares.", width_inches=6.2)

    add_paragraph(doc, "Al concluir el peritaje vehicular, el operador captura el dictamen técnico en el expediente correspondiente:")

    add_figure_image(doc, "figura_17.jpg", "operador",
                     "Figura 17. Captura del dictamen de inspección física en el expediente.", width_inches=6.0)

    # CAPÍTULO 24
    add_h1(doc, "24. Almacén de Formatos Oficiales Descargables")
    add_paragraph(doc, "En 'Formatos de Trámite' (/admin/formatos), el operador dispone de las plantillas oficiales en formato PDF para imprimirlas y proporcionarlas a ciudadanos que requieran solicitudes impresas en blanco:")

    add_figure_image(doc, "figura_18.jpg", "operador",
                     "Figura 18. Almacén de formatos de trámite clasificados por clave oficial.", width_inches=6.2)

    # CAPÍTULO 25
    add_h1(doc, "25. Arqueo Parcial de Caja y Cierre de Turno en Ventanilla")
    add_paragraph(doc, "Al concluir el horario de atención al público, el operador genera el reporte de corte de caja (/admin/cobros/corte), concilia el dinero en efectivo y vouchers de terminal contra el monto reflejado por el sistema y entrega los valores a la Tesorería Municipal:")

    add_figure_image(doc, "figura_19.jpg", "operador",
                     "Figura 19. Reporte de corte de caja en ventanilla con totales y desglose de recibos.", width_inches=6.2)

