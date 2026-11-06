# scripts/crear_guia_operador.py
# -*- coding: utf-8 -*-
import os
import docx
from generar_guias_word import (
    apply_document_styles, add_cover_page, add_h1, add_h2,
    add_paragraph, add_bullet, add_callout, add_screenshots, add_styled_table
)

CAPTURAS = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "docs", "guias_usuario", "capturas_operador")

def generar_guia_operador(output_path):
    doc = docx.Document()
    apply_document_styles(doc)

    numero_figura = [0]
    def figura(nombre, caption, **kwargs):
        numero_figura[0] += 1
        add_screenshots(doc, [os.path.join(CAPTURAS, f"{nombre}.jpg")], f"Figura {numero_figura[0]}. {caption}", **kwargs)

    # PORTADA
    add_cover_page(
        doc,
        role_title="Operador de Ventanilla",
        subtitle="Manual de Operación en Mostrador: Atención Ciudadana, Registro Presencial y Cobros",
        target_audience="Personal Operativo de Atención al Público en Ventanilla de Movilidad",
        version="1.1"
    )

    # ÍNDICE GENERAL
    add_h1(doc, "ÍNDICE GENERAL DEL MANUAL")
    add_bullet(doc, "1. Propósito y Alcance del Operador de Ventanilla")
    add_bullet(doc, "2. Acceso al Panel y Menú por Permisos")
    add_bullet(doc, "3. Protocolo de Atención Ciudadana en Mostrador")
    add_bullet(doc, "4. Procedimiento Paso a Paso: Registro Presencial de Trámites")
    add_bullet(doc, "5. Cuentas de Ciudadano: Con CURP, Sin CURP y Acceso al Portal")
    add_bullet(doc, "6. Seguimiento de Expedientes y Prevenciones")
    add_bullet(doc, "7. Módulo de Cobros y Folios de Recibo")
    add_bullet(doc, "8. Consulta de Citas de Verificación Física")
    add_bullet(doc, "9. Formatos de Trámite")
    add_bullet(doc, "10. Corte de Caja y Cierre de Turno")
    add_bullet(doc, "11. Solución de Problemas Frecuentes en la Atención al Público")

    # CAPÍTULO 1
    add_h1(doc, "1. Propósito y Alcance del Operador de Ventanilla")
    add_paragraph(doc, "El Operador de Ventanilla representa la cara visible de la Dirección de Movilidad y Transporte ante la ciudadanía de Uriangato. Su misión principal es brindar una atención cálida, ágil y certera a quienes acuden personalmente a realizar trámites de transporte o a pagar derechos y multas.")

    add_paragraph(doc, "El sistema le da herramientas para:")
    add_bullet(doc, "Registrar solicitudes de personas que no cuentan con computadora, internet o teléfono inteligente.", "Atención Inclusiva: ")
    add_bullet(doc, "Revisar los documentos y avanzar cada expediente por sus etapas.", "Seguimiento de Expedientes: ")
    add_bullet(doc, "Registrar pagos de trámites y multas, que generan un folio de recibo.", "Cobros: ")
    add_bullet(doc, "Auxiliar al ciudadano a entender el estatus de su trámite y qué le falta.", "Orientación Clara: ")

    # CAPÍTULO 2
    add_h1(doc, "2. Acceso al Panel y Menú por Permisos")
    add_paragraph(doc, "Entre a la pantalla de inicio de sesión de la Ventanilla Digital con su usuario o correo y su contraseña de operador. Al entrar verá el panel de 'Inicio', con las cifras del día y el botón 'Registrar Solicitud Presencial'.")

    figura("inicio", "Panel de inicio del operador, con el menú lateral.")

    add_paragraph(doc, "El menú lateral se organiza en estos grupos:")
    add_bullet(doc, "Inicio, Todas las solicitudes, Nueva solicitud presencial y un acceso por trámite (T-01 a T-07).", "Operaciones: ")
    add_bullet(doc, "Cobros, Pago en BanBajío y Corte de caja.", "Cobros: ")
    add_bullet(doc, "Usuarios y ciudadanos, Tarifario, Concesiones, Formatos de trámite, Auditoría, Documentos recibidos, Verificaciones físicas y Convocatorias UR-01.", "Ventanilla y Catálogos: ")
    add_bullet(doc, "Accesos a la aplicación de campo, sus boletas y su catálogo de faltas.", "App Tránsito: ")

    add_callout(doc, "info", "MENÚ POR PERMISOS",
                "El Administrador decide qué secciones puede usar la ventanilla. Si necesita una sección y no aparece en su menú (por ejemplo, 'Cobros' o 'Verificaciones físicas'), pídale al Administrador que se la habilite.")

    # CAPÍTULO 3
    add_h1(doc, "3. Protocolo de Atención Ciudadana en Mostrador")
    add_paragraph(doc, "Para asegurar un servicio de calidad y evitar contratiempos administrativos, siga este protocolo de 4 pasos al recibir a un ciudadano:")
    add_bullet(doc, "Salude cordialmente y pregunte qué trámite o pago desea realizar.", "1. Saludo y Diagnóstico: ")
    add_bullet(doc, "Solicite una identificación oficial (INE o pasaporte) y, si la tiene, la CURP del solicitante.", "2. Identificación Oficial: ")
    add_bullet(doc, "Revise que los documentos físicos correspondan al trámite solicitado y se encuentren completos y vigentes.", "3. Revisión Preliminar: ")
    add_bullet(doc, "Proceda a la captura en el sistema siguiendo el capítulo 4.", "4. Captura en Sistema: ")

    # CAPÍTULO 4
    add_h1(doc, "4. Procedimiento Paso a Paso: Registro Presencial de Trámites")
    add_paragraph(doc, "En el menú, presione 'Nueva solicitud presencial'. La pantalla tiene dos pasos: identificar al ciudadano y seleccionar el trámite.")

    add_h2(doc, "Paso 1: Identificar al Ciudadano")
    add_paragraph(doc, "Capture los 18 caracteres de la CURP y presione 'Buscar por CURP'. Si la persona no trae su CURP, abra '¿No trae su CURP? Buscar por nombre, correo o RFC' y busque con al menos 2 caracteres.")
    figura("nueva_solicitud", "Paso 1 del registro presencial: búsqueda por CURP.")

    add_bullet(doc, "Caso A, el ciudadano ya existe: aparece en pantalla con sus datos. Use 'Editar datos' si hay que corregirlos, o 'Cambiar ciudadano' si no es la persona.")
    add_bullet(doc, "Caso B, no existe: aparece el aviso 'No hay ninguna cuenta con la CURP'. Solo nombre y apellidos son obligatorios. Presione 'Crear cuenta y continuar'.")
    add_bullet(doc, "Caso C, la cuenta está desactivada: el aviso le lleva a 'Usuarios y ciudadanos' para activarla.")

    figura("alta_ciudadano", "Alta rápida cuando la CURP no existe en el sistema.")

    add_paragraph(doc, "Al crear la cuenta, el aviso verde muestra el usuario y la contraseña temporal.")
    figura("cuenta_creada", "Cuenta creada: el ciudadano queda identificado y los trámites se habilitan.")

    add_callout(doc, "warning", "ENTREGUE LA CONTRASEÑA TEMPORAL AL CIUDADANO",
                "La contraseña temporal se muestra una sola vez. Anótela y entréguela en ese momento; al salir de la pantalla ya no se puede volver a ver.")

    add_h2(doc, "Posible Duplicado u Homónimo")
    add_paragraph(doc, "Si el nombre coincide con el de otra persona registrada, el sistema muestra un aviso ámbar antes de crear la cuenta:")
    add_bullet(doc, "Revise las coincidencias que aparecen debajo del aviso y compárelas con la identificación del ciudadano.")
    add_bullet(doc, "Si es la misma persona, presione 'Seleccionar' para conservar su historial y sus trámites.")
    add_bullet(doc, "Si es otra persona con nombre similar, presione 'Registrar de todos modos'.")
    figura("homonimo", "Aviso de posible duplicado por nombre.")

    add_h2(doc, "Paso 2: Seleccionar el Trámite")
    add_paragraph(doc, "Con el ciudadano identificado, presione 'Continuar' en el trámite que corresponda:")
    add_bullet(doc, "UR-TT-T-01: Concesión de Transporte. Necesita una convocatoria vigente.")
    add_bullet(doc, "UR-TT-T-02: Constancia de Despintado. Requiere cita de inspección.")
    add_bullet(doc, "UR-TT-T-03: Orden de Plaqueo. No tiene costo.")
    add_bullet(doc, "UR-TT-T-04: Permiso Eventual de Transporte.")
    add_bullet(doc, "UR-TT-T-05: Permiso para Cierre de Calle.")
    add_bullet(doc, "UR-TT-T-06: Cesión de Concesión.")
    add_bullet(doc, "UR-TT-T-07: Permiso de Carga y Descarga.")

    add_h2(doc, "Paso 3: Capturar el Trámite y Registrar")
    add_paragraph(doc, "Los datos del ciudadano se llenan automáticamente; revíselos. Complete los campos marcados con asterisco (*). En 'Documentos (opcionales)' adjunte los archivos si el ciudadano los entrega en ese momento; también se pueden recibir después. El monto según el tarifario vigente aparece arriba. Presione 'Registrar Solicitud'.")
    figura("formulario", "Formulario del trámite presencial.", max_height=6.6)

    add_paragraph(doc, "El sistema abre el expediente con un aviso verde. Entregue al ciudadano su folio de 20 letras y números: con él se identifica su trámite. Desde ahí también puede imprimir la 'Hoja de pago' cuando corresponda.")
    figura("solicitud_registrada", "Solicitud registrada, con su folio.")

    # CAPÍTULO 5
    add_h1(doc, "5. Cuentas de Ciudadano: Con CURP, Sin CURP y Acceso al Portal")
    add_paragraph(doc, "La CURP identifica al ciudadano y no puede repetirse entre cuentas. Lo que ocurre con el acceso al portal depende de si se captura:")
    add_styled_table(doc,
                     ["Situación", "Qué crea el sistema", "Qué decirle al ciudadano"],
                     [
                         ["Con CURP", "Una cuenta completa. El usuario es la CURP y se genera una contraseña temporal.", "Entréguele su usuario y contraseña temporal. Ya puede entrar al portal."],
                         ["Sin CURP", "Un registro sin acceso al portal. El trámite queda a su nombre.", "Para ver su trámite en línea debe tener su CURP capturada. Anótela en 'Usuarios y ciudadanos' cuando la presente."],
                         ["Ya registrado sin acceso", "Conserva su registro y sus trámites.", "Debe entrar a 'Crear mi cuenta' en el portal y capturar la misma CURP: el sistema vincula sus trámites."]
                     ],
                     [1.4, 2.4, 2.7])

    add_paragraph(doc, "En 'Usuarios y ciudadanos' puede buscar cuentas, crear una con 'Nueva cuenta', editarlas y activarlas o desactivarlas. Las cuentas de personal y las bajas definitivas son exclusivas del Administrador.")
    figura("usuarios", "Sección 'Usuarios y ciudadanos'.")

    # CAPÍTULO 6
    add_h1(doc, "6. Seguimiento de Expedientes y Prevenciones")
    add_paragraph(doc, "En 'Todas las solicitudes' puede filtrar por estatus, trámite o canal y buscar por folio o nombre. Presione 'Ver' para abrir el expediente.")
    figura("solicitudes", "Bandeja de solicitudes con sus filtros.")

    add_paragraph(doc, "En el expediente encontrará los datos generales, los datos capturados, los documentos adjuntos y el historial. Cada documento tiene botones para descargarlo y validarlo. El recuadro 'Acciones de seguimiento' muestra únicamente los botones permitidos para el estatus actual.")
    figura("expediente", "Expediente con sus datos generales y las acciones de seguimiento.")

    add_h2(doc, "Cómo Pedir Documentos (Prevención)")
    add_paragraph(doc, "Esta acción existe en los trámites UR-TT-T-04 y UR-TT-T-06, durante la revisión documental. Si un documento es borroso, no coincide con el solicitante o está vencido:")
    add_bullet(doc, "1. En 'Acciones de seguimiento', escriba en 'Motivo de la acción' qué documento presenta deficiencias y cómo debe corregirse (ejemplo: 'Falta la póliza de seguro vigente de la unidad').")
    add_bullet(doc, "2. Presione 'Pedir documentos'. El motivo es obligatorio.")
    add_bullet(doc, "3. El expediente queda en 'Prevención' y el ciudadano ve su comentario en el portal.")
    add_bullet(doc, "4. Cuando el ciudadano entregue lo que falta, regrese el expediente a revisión documental con la acción disponible.")
    figura("acciones", "Acciones de seguimiento de un expediente en revisión documental.", max_width=3.6)

    add_callout(doc, "warning", "EL CIUDADANO ENTREGA LA CORRECCIÓN EN LA DIRECCIÓN",
                "El portal le muestra la observación al ciudadano, pero no tiene un botón para reemplazar archivos. Recibirá la documentación directamente en la Dirección.")

    add_h2(doc, "Etapas de Cada Trámite")
    add_styled_table(doc,
                     ["Trámite", "Etapas"],
                     [
                         ["UR-TT-T-01", "Se resuelve en la evaluación comparativa de 'Convocatorias UR-01'. El seleccionado pasa a pago y al final se usa 'Entregar título-concesión'."],
                         ["UR-TT-T-02", "Cita agendada, dictamen de inspección, 'Autorizar pago', cobro y 'Entregar constancia de despintado'."],
                         ["UR-TT-T-03", "'Iniciar revisión' y 'Aprobar solicitud'. No tiene pago."],
                         ["UR-TT-T-04", "Revisión documental, documentos completos, estudio técnico, dictamen favorable, inspección, revista mecánica, validación del seguro, autorización de pago, cobro y 'Emitir permiso'."],
                         ["UR-TT-T-05", "'Iniciar validación', revisión de los criterios de seguridad vial, 'Autorizar pago', cobro y 'Emitir permiso'."],
                         ["UR-TT-T-06", "Revisión documental, 'Aprobar dictamen', 'Autorizar pago' y cobro, que concluye el trámite."],
                         ["UR-TT-T-07", "'Autorizar pago', cobro, 'Emitir permiso' y 'Activar vigencia'."]
                     ],
                     [1.2, 5.3])

    add_callout(doc, "info", "EL PAGO SIEMPRE SE REGISTRA EN COBROS",
                "Cuando un expediente está en 'Pago pendiente', el botón 'Registrar pago' lo lleva a su cobro. El estatus 'Pagado' solo se obtiene desde ahí, para que quede recibo, movimiento y auditoría.")

    # CAPÍTULO 7
    add_h1(doc, "7. Módulo de Cobros y Folios de Recibo")
    add_paragraph(doc, "Cuando un ciudadano acude a pagar los derechos de un trámite autorizado o una multa de tránsito:")
    add_bullet(doc, "1. Entre a 'Cobros'. Arriba verá lo pendiente, lo que está por completar y lo cobrado hoy.")
    add_bullet(doc, "2. Busque por nombre, concepto, folio o referencia.")
    add_bullet(doc, "3. Verifique en pantalla el concepto, el nombre y el total.")
    figura("cobros", "Listado de cobros, con los botones de cada uno.")
    add_bullet(doc, "4. Presione 'Cobrar'.")
    add_bullet(doc, "5. En '¿Dónde se pagó?' elija el lugar. Viene seleccionado 'Ventanilla'.")
    add_bullet(doc, "6. Si hay comprobante, escriba su número en 'Referencia del pago'. Es opcional.")
    add_bullet(doc, "7. Presione 'Registrar cobro'.")
    figura("registrar_cobro", "Ventana para registrar el cobro.", max_width=3.4)

    add_paragraph(doc, "El sistema confirma con el folio de recibo (REC-AAAA-NNNNNN), y el trámite pasa solo a 'Pagado'. En las multas, el descuento por pronto pago o el recargo se recalculan con la fecha de hoy, por lo que el total puede cambiar.")
    figura("cobro_registrado", "Cobro registrado, con su folio de recibo.")

    add_callout(doc, "warning", "EL RECIBO IMPRESO AÚN NO SE GENERA DESDE EL SISTEMA",
                "El sistema asigna el folio de recibo, pero todavía no imprime el recibo oficial. Anote ese folio en el comprobante que entregue al ciudadano. La Hoja de pago no sustituye al recibo.")

    add_h2(doc, "Mandar a Completar y Cancelar")
    add_bullet(doc, "Úselo cuando falte un documento para poder cobrar. Pide un motivo y el cobro queda 'Por completar'.", "Mandar a completar: ")
    add_bullet(doc, "Úselo cuando el cobro no proceda. Pide un motivo obligatorio y queda registrado en el historial.", "Cancelar: ")

    add_callout(doc, "warning", "REVISE ANTES DE CONFIRMAR",
                "Un cobro aprobado no se puede revertir desde esta pantalla. Si registró un cobro por error, avise al Administrador. Condonar recargos corresponde al juez calificador o al Administrador.")

    add_h2(doc, "Pago en BanBajío")
    add_paragraph(doc, "Cuando el ciudadano pagó en el banco y presenta su comprobante:")
    add_bullet(doc, "1. Entre a 'Pago en BanBajío' y escriba la referencia de 12 dígitos de su hoja de pago.")
    add_bullet(doc, "2. Presione 'Buscar' y confirme el nombre, el concepto y el total.")
    add_bullet(doc, "3. Presione 'Registrar comprobante de BanBajío'.")
    add_bullet(doc, "4. Copie la referencia, la fecha del pago y el importe del comprobante. Si el importe no coincide con el total del día del pago, el cobro no se registra.")
    figura("banco", "Búsqueda de un cobro por su referencia bancaria.")

    # CAPÍTULO 8
    add_h1(doc, "8. Consulta de Citas de Verificación Física")
    add_paragraph(doc, "En 'Verificaciones físicas' puede consultar la agenda de inspecciones de la Constancia de Despintado (UR-TT-T-02): citas próximas, vencidas sin dictamen y con dictamen capturado. Esta pantalla es de consulta; el resultado se captura en el expediente.")
    figura("verificaciones", "Agenda de verificaciones físicas.")

    add_paragraph(doc, "Para registrar el resultado de la inspección:")
    add_bullet(doc, "1. Presione el folio para abrir el expediente, que debe estar en 'Cita agendada'.")
    add_bullet(doc, "2. En 'Registrar dictamen', elija 'Aprobar (Despintado)' o 'Rechazar (Incumple)'.")
    add_bullet(doc, "3. Describa las condiciones de la unidad en 'Observaciones del dictamen'. Es obligatorio.")
    add_bullet(doc, "4. Presione 'Guardar dictamen de verificación'.")
    figura("dictamen_ur02", "Captura del dictamen de inspección en el expediente.")

    add_callout(doc, "warning", "LA CITA SE AGENDA DESDE EL PORTAL",
                "El panel no tiene una pantalla para agendar la cita: el ciudadano la agenda en el portal al terminar su solicitud. Si la persona registró su trámite en ventanilla y no tiene acceso al portal, consulte al Administrador.")

    # CAPÍTULO 9
    add_h1(doc, "9. Formatos de Trámite")
    add_paragraph(doc, "En 'Formatos de trámite' se administran los formatos oficiales que los ciudadanos descargan antes de iniciar un trámite:")
    add_bullet(doc, "Presione 'Subir' en el trámite y elija el archivo. Si ya tiene formato, puede reemplazarlo o quitarlo.")
    add_bullet(doc, "El formato cargado aparece en el portal con el botón 'Descargar formato'.")
    add_bullet(doc, "Si un ciudadano no tiene medios digitales, ábralo desde aquí e imprímalo para entregárselo.")
    figura("formatos", "Formatos de trámite por clave.")

    # CAPÍTULO 10
    add_h1(doc, "10. Corte de Caja y Cierre de Turno")
    add_paragraph(doc, "'Corte de caja' resume lo cobrado por periodo. Elija Día, Semana, Mes o Rango de fechas, el día de referencia y, en 'Cobró', su nombre; después presione 'Ver corte'.")
    figura("corte", "Corte de caja, con el total, los recibos y el desglose.")

    add_paragraph(doc, "Al finalizar el horario de atención al público:")
    add_bullet(doc, "1. Cuente el dinero recibido y reúna los comprobantes.")
    add_bullet(doc, "2. Compare esos montos contra el 'Total cobrado' de su corte del día.")
    add_bullet(doc, "3. Si coinciden, presione 'Imprimir corte'. También puede usar 'Descargar CSV' para abrirlo en Excel.")
    add_bullet(doc, "4. Entregue los valores conforme al procedimiento de la Dirección.")
    add_bullet(doc, "5. CIERRE SU SESIÓN en el equipo con 'Cerrar sesión'.")

    # CAPÍTULO 11
    add_h1(doc, "11. Solución de Problemas Frecuentes en la Atención al Público")
    add_h2(doc, "El portal dice que la CURP ya está registrada, pero el ciudadano no recuerda su cuenta")
    add_paragraph(doc, "Busque a la persona en 'Usuarios y ciudadanos'. Su usuario puede ser la CURP. Si tiene correo, puede recuperar su contraseña con '¿La olvidaste?'. Si no tiene acceso a ese correo, abra su cuenta con editar, actualice el correo o asigne una contraseña nueva.")

    add_h2(doc, "El botón del trámite está deshabilitado")
    add_paragraph(doc, "Primero identifique al ciudadano en el Paso 1. Si ya lo hizo y el formulario avisa que no hay tarifa vigente, revise el 'Tarifario' o consulte al Administrador. En la Concesión de Transporte, además, debe haber una convocatoria vigente.")

    add_h2(doc, "El expediente no muestra el botón que necesito")
    add_paragraph(doc, "Solo aparecen las acciones permitidas para el estatus actual. Revise las etapas del capítulo 6. Los pagos se registran en 'Cobros' y la Concesión de Transporte se resuelve en 'Convocatorias UR-01'.")

    add_h2(doc, "El pago se realizó por transferencia o depósito en BanBajío")
    add_paragraph(doc, "El ciudadano debe presentar su comprobante con la referencia visible. Búsquela en 'Pago en BanBajío' y registre el comprobante (capítulo 7).")

    add_h2(doc, "No encuentro el cobro de un trámite")
    add_paragraph(doc, "El cobro se crea cuando se autoriza el pago. Revise que el expediente esté en 'Pago pendiente'. Si lo está y el aviso dice que no se encontró un cobro vigente, consulte al Administrador.")

    # Guardar
    doc.save(output_path)
    print(f"Guía del Operador de Ventanilla generada en: {output_path}")

if __name__ == "__main__":
    out = os.path.join(os.path.dirname(__file__), "..", "docs", "guias_usuario", "Guia_Usuario_Operador_Ventanilla.docx")
    generar_guia_operador(out)
