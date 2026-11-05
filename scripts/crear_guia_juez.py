# scripts/crear_guia_juez.py
# -*- coding: utf-8 -*-
import os
import docx
from generar_guias_word import (
    apply_document_styles, add_cover_page, add_h1, add_h2, add_h3,
    add_paragraph, add_bullet, add_callout, add_image_placeholder, add_styled_table
)

def generar_guia_juez(output_path):
    doc = docx.Document()
    apply_document_styles(doc)

    # PORTADA
    add_cover_page(
        doc,
        role_title="Juez Calificador",
        subtitle="Manual de Operación Judicial para Calificación de Boletas de Infracción y Cobros",
        target_audience="Jueces Calificadores y Personal Judicial de Barandilla",
        version="1.0"
    )

    # ÍNDICE GENERAL
    add_h1(doc, "ÍNDICE GENERAL DEL MANUAL")
    add_bullet(doc, "1. Propósito, Marco Normativo y Principios de Operación")
    add_bullet(doc, "2. Acceso al Panel Judicial y Seguridad de Sesión")
    add_bullet(doc, "3. Tablero de Control Judicial (Dashboard)")
    add_bullet(doc, "4. Bandeja de Boletas de Infracción Recibidas de Campo")
    add_bullet(doc, "5. Procedimiento Paso a Paso: Calificación Judicial de Boletas")
    add_bullet(doc, "6. Reglas de Cálculo: UMA, Pronto Pago del 40% y Recargos")
    add_bullet(doc, "7. Módulo de Cobros en Juzgado Calificador y Emisión de Recibos")
    add_bullet(doc, "8. Procedimiento de Condonación de Recargos")
    add_bullet(doc, "9. Corte de Caja Diario y Arqueo Judicial")
    add_bullet(doc, "10. Solución de Problemas y Preguntas Frecuentes")

    # CAPÍTULO 1
    add_h1(doc, "1. Propósito, Marco Normativo y Principios de Operación")
    add_paragraph(doc, "El presente manual tiene como objetivo guiar al Juez Calificador en el uso del Sistema Integral de Ventanilla Única de Movilidad y Transporte del Municipio de Uriangato, Guanajuato. Este módulo proporciona las herramientas tecnológicas necesarias para calificar de manera justa, transparente y ágil las boletas de infracción levantadas en la vía pública por los agentes de tránsito, así como gestionar la cobranza en barandilla y el cierre diario de caja.")
    
    add_callout(doc, "legal", "PRINCIPIO INSTITUCIONAL: «COBRAR NO ES ADMINISTRAR»", 
                "El panel judicial está diseñado bajo el principio estricto de separación de funciones. El Juez Calificador tiene atribuciones jurisdiccionales sobre las faltas y la cobranza de las multas de tránsito, pero no administra catálogos de tarifas de trámites, ni modifica concesiones ni interviene en la configuración del personal municipal. Esta frontera garantiza la imparcialidad del juzgado.")

    add_paragraph(doc, "El sustento normativo de las actuaciones del juez calificador emana del Reglamento de Movilidad y Transporte del Municipio de Uriangato y de la Ley de Movilidad del Estado de Guanajuato. Todas las sanciones económicas se calculan con base en la Unidad de Medida y Actualización (UMA) vigente.")

    # CAPÍTULO 2
    add_h1(doc, "2. Acceso al Panel Judicial y Seguridad de Sesión")
    add_paragraph(doc, "Para acceder a las funciones judiciales, el juez calificador debe ingresar a través del navegador web institucional utilizando su equipo de cómputo asignado.")
    add_bullet(doc, "Dirección URL del Panel Judicial: http://[servidor-municipal]/juez", "URL Oficial: ")
    add_bullet(doc, "Correo electrónico institucional y contraseña encriptada.", "Credenciales: ")
    add_bullet(doc, "Google Chrome, Mozilla Firefox o Microsoft Edge actualizados.", "Navegador Recomendado: ")

    add_paragraph(doc, "Procedimiento de inicio de sesión:")
    add_bullet(doc, "1. Abra el navegador web e ingrese la dirección del sistema.")
    add_bullet(doc, "2. En la pantalla de autenticación, capture su correo institucional y su contraseña.")
    add_bullet(doc, "3. Haga clic en el botón 'Iniciar Sesión'. El sistema verificará su rol y lo redirigirá automáticamente al panel judicial.")

    add_image_placeholder(doc, "Pantalla de Acceso / Login (/auth/login)",
                          "Formulario de autenticación donde el Juez ingresa sus credenciales.",
                          "Campos de Correo Institucional, Contraseña, botón Iniciar Sesión y logotipo institucional.")

    add_callout(doc, "warning", "CIERRE DE SESIÓN OBLIGATORIO", 
                "Por seguridad jurídica y confidencialidad de los expedientes, nunca deje su sesión abierta al retirarse del juzgado. Use siempre el botón 'Cerrar Sesión' ubicado en la esquina superior derecha.")

    # CAPÍTULO 3
    add_h1(doc, "3. Tablero de Control Judicial (Dashboard)")
    add_paragraph(doc, "Al ingresar al sistema, la primera pantalla visible es el Tablero de Control Judicial (/juez/dashboard). Este centro de mando resume en tiempo real la situación operativa del juzgado.")

    add_styled_table(doc, 
                     ["Indicador", "Significado Operativo", "Acción Recomendada"],
                     [
                         ["Boletas Pendientes", "Infracciones sincronizadas por agentes que esperan calificación.", "Revisar y calificar prioritariamente."],
                         ["Cobros Realizados Hoy", "Número de multas liquidadas en barandilla en el turno.", "Monitorear para corte de caja."],
                         ["Recaudación Acumulada", "Monto total en pesos cobrado durante la jornada actual.", "Cotejar contra el efectivo y vouchers en caja."],
                         ["Últimas Boletas", "Listado directo de las boletas más recientes recibidas.", "Acceso rápido a calificación."]
                     ],
                     [1.8, 2.7, 2.0])

    add_image_placeholder(doc, "Dashboard Judicial (/juez/dashboard)",
                          "Vista panorámica con tarjetas de métricas del día y accesos rápidos.",
                          "Tarjetas de conteo de boletas pendientes, total recaudado del día, accesos a 'Boletas' y 'Cobros'.")

    # CAPÍTULO 4
    add_h1(doc, "4. Bandeja de Boletas de Infracción Recibidas de Campo")
    add_paragraph(doc, "En la sección 'Boletas' (/juez/boletas), el juez tiene a su disposición todas las infracciones remitidas por los agentes de tránsito desde la aplicación móvil. El servidor municipal las registra de manera inmediata con estatus inicial 'Recibida'.")

    add_paragraph(doc, "Herramientas de filtrado y búsqueda rápida:")
    add_bullet(doc, "Permite consultar boletas de un turno o fecha determinada.", "Filtro por Rango de Fechas: ")
    add_bullet(doc, "Filtra entre boletas 'Recibida' (por calificar) y 'Calificada'.", "Filtro por Estatus: ")
    add_bullet(doc, "Búsqueda directa por el número de boleta impreso o digital.", "Búsqueda por Folio: ")
    add_bullet(doc, "Permite rastrear infracciones pendientes asociadas a un vehículo específico.", "Búsqueda por Placas: ")

    add_image_placeholder(doc, "Bandeja de Boletas (/juez/boletas)",
                          "Listado completo de boletas de infracción sincronizadas desde la vía pública.",
                          "Tabla con columnas: Folio, Agente, Fecha/Hora, Placa, Faltas Detectadas, Estatus y botón 'Calificar'.")

    # CAPÍTULO 5
    add_h1(doc, "5. Procedimiento Paso a Paso: Calificación Judicial de Boletas")
    add_paragraph(doc, "La calificación es el acto formal mediante el cual el Juez Calificador revisa la actuación del agente de campo, examina las pruebas fotográficas y determina la sanción en UMA aplicable al infractor.")

    add_h2(doc, "Paso 1: Apertura del Expediente de la Boleta")
    add_paragraph(doc, "Ubique la boleta en la bandeja y presione el botón azul 'Ver y Calificar'. Se desplegará la pantalla de detalle (/juez/boletas/detalle/[id]).")

    add_h2(doc, "Paso 2: Examen de Evidencias Probatorias y Datos de Campo")
    add_paragraph(doc, "Antes de fijar una sanción, el juez debe verificar minuciosamente la solidez del expediente:")
    add_bullet(doc, "Placa, entidad federativa, marca, modelo, submarca y color del vehículo.", "Datos del Vehículo: ")
    add_bullet(doc, "Nombre, domicilio y licencia de conducir (o nota de ausencia del conductor).", "Datos del Infractor: ")
    add_bullet(doc, "Mapa interactivo con las coordenadas geográficas exactas de la infracción.", "Ubicación GPS: ")
    add_bullet(doc, "El agente registra hasta 3 fotografías obligatorias (placa, contexto panorámico y documento/falta).", "Fotografías de Evidencia: ")
    add_bullet(doc, "Declaración de qué documento u objeto fue retenido en garantía (placa, licencia, tarjeta de circulación o vehículo en corralón).", "Garantías Retenidas: ")

    add_image_placeholder(doc, "Detalle de Boleta y Visor de Evidencias (/juez/boleta_detalle)",
                          "Pantalla con la ficha completa del vehículo, mapa de ubicación y carrusel de fotografías.",
                          "Fotografías de la infracción en alta resolución, coordenadas GPS, garantías retenidas y datos del infractor.")

    add_h2(doc, "Paso 3: Asignación de UMA por Cada Falta")
    add_paragraph(doc, "En el panel inferior de calificación, se muestran las casillas marcadas por el oficial de tránsito con base en el catálogo de 78 faltas:")
    add_bullet(doc, "Cada falta indica su rango legal normativo (ejemplo: Mínimo 5 UMA - Máximo 10 UMA).")
    add_bullet(doc, "El Juez Calificador ingresa el número de UMA correspondiente dentro de dicho rango, valorando atenuantes o agravantes (ejemplo: reincidencia, poner en peligro a transeúntes, estado de ebriedad).")
    add_bullet(doc, "El sistema calcula automáticamente el subtotal en pesos multiplicando las UMA asignadas por el valor diario oficial de la UMA.")

    add_image_placeholder(doc, "Formulario de Asignación de UMA (/juez/boleta_detalle - Panel Inferior)",
                          "Módulo interactivo donde el juez califica cada concepto de infracción.",
                          "Campos para digitar UMA, cálculo automático en pesos en tiempo real, desglose de faltas y botón 'Emitir Calificación'.")

    add_h2(doc, "Paso 4: Confirmación y Emisión de la Orden de Cobro")
    add_paragraph(doc, "Al presionar 'Guardar y Emitir Calificación', el sistema ejecuta tres acciones automáticas:")
    add_bullet(doc, "1. Cambia el estatus de la boleta de 'Recibida' a 'Calificada'.")
    add_bullet(doc, "2. Crea inmediatamente una orden de cobro oficial con folio correlativo único COB-AAAA-NNNNNN.")
    add_bullet(doc, "3. Habilita la liquidación de la multa tanto en el juzgado como en ventanilla o bancos.")

    # CAPÍTULO 6
    add_h1(doc, "6. Reglas de Cálculo: UMA, Pronto Pago del 40% y Recargos")
    add_paragraph(doc, "El sistema de cobros de Uriangato tiene integradas por código las siguientes reglas fiscales automáticas:")

    add_styled_table(doc,
                     ["Concepto Fiscal", "Criterio de Aplicación", "Impacto Económico"],
                     [
                         ["Pronto Pago (Descuento)", "Pago dentro de los primeros 10 días hábiles posteriores a la boleta y antes de un mes.", "Descuento automático del 40% sobre el monto calificado."],
                         ["Monto Ordinario", "Pago realizado entre el día 11 hábil y el día 30 natural.", "100% del monto en pesos calculado en UMA."],
                         ["Recargo por Mora", "Pago realizado transcurrido más de un mes desde la fecha de infracción.", "Recargo acumulado del 5% mensual sobre el importe base."],
                         ["Condonación", "Autorización expresa del juez para condonar total o parcialmente recargos.", "Reducción del recargo acumulado con registro en bitácora."]
                     ],
                     [1.8, 2.7, 2.0])

    add_callout(doc, "info", "VALOR DE LA UMA", 
                "El valor diario de la Unidad de Medida y Actualización (UMA) es configurado centralmente por el Administrador. El sistema toma siempre el valor legal vigente al momento de la calificación.")

    # CAPÍTULO 7
    add_h1(doc, "7. Módulo de Cobros en Juzgado Calificador y Emisión de Recibos")
    add_paragraph(doc, "Cuando el infractor acude a las oficinas del Juzgado Cívico / Calificador a liquidar su sanción y recuperar su garantía retenida, el juez realiza el cobro desde la sección 'Cobros' (/juez/cobros).")

    add_h2(doc, "Procedimiento de Cobro en Mostrador:")
    add_bullet(doc, "1. Ingrese a la sección 'Cobros'.")
    add_bullet(doc, "2. Localice el cobro buscando por Folio de Boleta, Folio de Cobro (COB-...) o Placas del vehículo.")
    add_bullet(doc, "3. Verifique el monto total a pagar. El sistema mostrará si aplica descuento de pronto pago o recargo.")
    add_bullet(doc, "4. Presione el botón 'Cobrar en Ventanilla'.")
    add_bullet(doc, "5. Seleccione el medio de pago recibido (Efectivo o Tarjeta Bancaria) y capture observaciones si aplica.")
    add_bullet(doc, "6. Confirme la transacción. El sistema generará el Recibo Oficial con folio REC-AAAA-NNNNNN.")
    add_bullet(doc, "7. Imprima el recibo oficial y entregue el original al ciudadano.")
    add_bullet(doc, "8. Proceda a devolver la garantía retenida (placa o documento), registrando la entrega.")

    add_image_placeholder(doc, "Pantalla de Cobro Judicial (/cobros/ver/[id])",
                          "Vista de desglose financiero, desglose de pronto pago y confirmación de recepción de fondos.",
                          "Monto a cobrar, desglose de recargos/descuentos, selector de medio de pago y botón 'Procesar Cobro'.")

    add_image_placeholder(doc, "Recibo Oficial de Pago Emitido (REC-AAAA-NNNNNN)",
                          "Formato oficial de comprobante de pago generado por el sistema.",
                          "Folio correlativo REC, sello digital de cobrado por el juez, desglose de faltas y garantías devueltas.")

    # CAPÍTULO 8
    add_h1(doc, "8. Procedimiento de Condonación de Recargos")
    add_paragraph(doc, "En situaciones justificadas (v. gr., personas en situación de vulnerabilidad acreditada, retrasos administrativos atribuibles a la autoridad o convenios ciudadanos), el Juez Calificador cuenta con la facultad de condonar recargos por mora.")
    
    add_bullet(doc, "1. En la pantalla del cobro correspondiente, presione el botón 'Condonar Recargos'.")
    add_bullet(doc, "2. El sistema abrirá el formulario /cobros/condonar/[id].")
    add_bullet(doc, "3. Ingrese el porcentaje o monto en pesos a condonar (solo aplica sobre el recargo, nunca sobre la multa base).")
    add_bullet(doc, "4. Capture de forma OBLIGATORIA la justificación o fundamento legal en el campo de motivos.")
    add_bullet(doc, "5. Guarde la condonación. El sistema actualizará el saldo y registrará la acción en la bitácora de auditoría.")

    add_image_placeholder(doc, "Formulario de Condonación (/cobros/condonar/[id])",
                          "Pantalla donde se especifica el monto a condonar y el motivo formal.",
                          "Campo de porcentaje o monto a condonar, cuadro de justificación obligatoria y botón 'Autorizar Condonación'.")

    # CAPÍTULO 9
    add_h1(doc, "9. Corte de Caja Diario y Arqueo Judicial")
    add_paragraph(doc, "Al finalizar el turno o jornada laboral, el Juez Calificador debe realizar el cierre y arqueo de caja desde la sección 'Corte de Caja' (/juez/corte).")
    
    add_paragraph(doc, "Pasos para realizar el corte de caja:")
    add_bullet(doc, "1. Seleccione la fecha del turno y presione 'Generar Corte'.")
    add_bullet(doc, "2. El sistema mostrará la relación detallada de todos los folios REC emitidos en el periodo, agrupados por medio de pago (Efectivo vs Terminal).")
    add_bullet(doc, "3. Realice el arqueo físico del dinero en efectivo y los comprobantes bancarios contra la cifra del sistema.")
    add_bullet(doc, "4. Haga clic en 'Exportar CSV' para descargar la relación contable oficial.")
    add_bullet(doc, "5. Imprima el reporte de corte para su firma y entrega al área de Tesorería Municipal.")

    add_image_placeholder(doc, "Corte de Caja Judicial (/cobros/corte)",
                          "Resumen financiero de la jornada con desglose por medio de pago y listado de folios.",
                          "Totales en pesos, listado correlativo de cobros, resumen de condonaciones y botón 'Exportar CSV'.")

    # CAPÍTULO 10
    add_h1(doc, "10. Solución de Problemas y Preguntas Frecuentes")
    
    add_h2(doc, "¿Qué hacer si una fotografía es ilegible o no corresponde?")
    add_paragraph(doc, "Si la evidencia aportada por el agente no permite comprobar la infracción o la placa no es legible, el Juez puede citar al agente de tránsito para aclaración o calificar en el mínimo legal si existen elementos testimoniales suficientes, asentando la observación en el expediente.")

    add_h2(doc, "¿Qué hacer si el ciudadano reclama el 40% de descuento pero el sistema no lo aplica?")
    add_paragraph(doc, "El sistema cuenta estrictamente los 10 días hábiles posteriores a la boleta. Si el plazo venció, el sistema retira el descuento automáticamente en apego a la ley. No es una falla del sistema, sino un límite legal programado.")

    add_h2(doc, "¿Se puede recalificar una boleta ya pagada?")
    add_paragraph(doc, "No. Una vez emitido el recibo oficial REC y registrado el pago, el expediente queda cerrado e inmutable para preservar la certeza jurídica y la integridad contable.")

    # Guardar
    doc.save(output_path)
    print(f"Guía del Juez Calificador generada en: {output_path}")

if __name__ == "__main__":
    out = os.path.join(os.path.dirname(__file__), "..", "docs", "guias_usuario", "Guia_Usuario_Juez_Calificador.docx")
    generar_guia_juez(out)

