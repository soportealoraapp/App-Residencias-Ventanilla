# scripts/partes_manual/parte5_juez.py
# -*- coding: utf-8 -*-
import docx
from generar_guias_word import (
    add_h1, add_h2, add_h3, add_paragraph, add_bullet, add_callout, add_styled_table
)

def agregar_parte5(doc, add_part_header, add_figure_image):
    add_part_header(
        doc,
        part_num="V",
        part_title="Guía de Operación del Juez Calificador (Panel Judicial)",
        part_description="Manual jurisdiccional para calificación de boletas de tránsito recibidas de campo, valoración de pruebas periciales y GPS, tasación en UMA, descuentos por pronto pago, recargos por mora, cobro en barandilla, condonaciones y corte de caja judicial."
    )

    # CAPÍTULO 35
    add_h1(doc, "35. Marco Competencial y Principio 'Cobrar no es Administrar'")
    add_paragraph(doc, "El Panel del Juez Calificador (/juez) es una superficie judicial especializada diseñada bajo el principio institucional de separación de funciones. El Juez Calificador ejerce facultades de valoración jurídica y fiscalización sobre las infracciones de tránsito cometidas en el Municipio de Uriangato, pero no administra usuarios, tarifas de trámites ni concesiones de transporte.")

    add_paragraph(doc, "Acceso al Entorno Judicial:")
    add_paragraph(doc, "El juez calificador ingresa con su cuenta institucional asignada a través del portal de autenticación central:")

    add_figure_image(doc, "figura_01.png", "juez",
                     "Figura 1. Inicio de sesión en la Ventanilla Digital para el Juez Calificador.", width_inches=6.0)

    # CAPÍTULO 36
    add_h1(doc, "36. Tablero de Control Judicial y Resumen del Turno")
    add_paragraph(doc, "Al ingresar a /juez/dashboard, el juez visualiza el centro de mando judicial en tiempo real con las boletas pendientes de calificación, los cobros efectuados en el turno y el importe recaudado acumulado:")

    add_figure_image(doc, "figura_02.png", "juez",
                     "Figura 2. Inicio del juez calificador con indicadores de boletas e ingresos del turno.", width_inches=6.2)

    # CAPÍTULO 37
    add_h1(doc, "37. Bandeja de Boletas de Infracción Recibidas de Campo")
    add_paragraph(doc, "En 'Boletas' (/juez/boletas), el juez accede al listado de infracciones sincronizadas por los agentes de tránsito desde la calle, con herramientas de filtrado por estatus, folio, placa del vehículo y rango de fechas:")

    add_figure_image(doc, "figura_03.png", "juez",
                     "Figura 3. Bandeja de boletas de infracción recibidas por calificar.", width_inches=6.2)

    # CAPÍTULO 38
    add_h1(doc, "38. Procedimiento de Calificación Judicial de la Boleta")
    add_paragraph(doc, "Al presionar 'Ver y Calificar' sobre una boleta, el juez examina el expediente probatorio completo:")
    add_bullet(doc, "Placas, estado emisor, marca, submarca, tipo de servicio y color del vehículo.", "Ficha Vehicular: ")
    add_bullet(doc, "Nombre, domicilio y licencia de conducir del infractor.", "Datos del Conductor: ")
    add_bullet(doc, "Mapa interactivo con las coordenadas geográficas satelitales del hecho.", "Geolocalización GPS: ")
    add_bullet(doc, "Visualizador de las tres fotografías tomadas por el oficial (placa, contexto y documento/falta).", "Evidencias Fotográficas: ")
    add_bullet(doc, "Garantía retenida por el agente (placa, tarjeta, licencia o vehículo en corralón).", "Garantías Retenidas: ")

    add_figure_image(doc, "figura_04.png", "juez",
                     "Figura 4. Datos generales de la boleta, evidencias fotográficas y garantías retenidas.", width_inches=6.2)

    add_paragraph(doc, "Asignación de UMA por Falta Cometida:")
    add_paragraph(doc, "En el módulo de tasación, el juez revisa las casillas marcadas por el oficial y asigna el valor en UMA dentro del rango normativo mínimo y máximo establecido por el reglamento:")

    add_figure_image(doc, "figura_05.png", "juez",
                     "Figura 5. Asignación de UMA por falta dentro de los rangos legales.", width_inches=6.2)

    add_paragraph(doc, "Vista Previa de la Calificación y Emisión de Cobro:")
    add_paragraph(doc, "El sistema genera una vista previa del cálculo financiero, aplicando las conversiones a pesos según la UMA legal vigente, y emite la orden de cobro judicial COB-AAAA-NNNNNN:")

    add_figure_image(doc, "figura_06.png", "juez",
                     "Figura 6. Vista previa de la calificación judicial y generación de la orden de cobro.", width_inches=6.2)

    # CAPÍTULO 39
    add_h1(doc, "39. Módulo de Cobros en Juzgado Calificador y Devolución de Garantías")
    add_paragraph(doc, "Cuando el infractor comparece en las oficinas del Juzgado Calificador a cubrir su sanción económica, el juez ingresa al módulo de cobros (/juez/cobros) para localizar el expediente:")

    add_figure_image(doc, "figura_07.png", "juez",
                     "Figura 7. Listado de cobros judiciales de infracciones de tránsito.", width_inches=6.2)

    add_paragraph(doc, "Al abrir el detalle del cobro, el juez visualiza el monto exigible, la aplicación automática del 40% de descuento por pronto pago (si está dentro de los 10 días hábiles) o el recargo por mora mensual (5%):")

    add_figure_image(doc, "figura_08.png", "juez",
                     "Figura 8. Detalle del cobro de una multa con desglose financiero de pronto pago y recargos.", width_inches=6.2)

    add_paragraph(doc, "El juez registra el pago en efectivo o terminal, expide el Recibo Oficial REC-AAAA-NNNNNN y efectúa la entrega formal de la garantía retenida (placa o licencia) asentando la entrega en el sistema.")

    # CAPÍTULO 40
    add_h1(doc, "40. Procedimiento de Condonación Motivada de Recargos")
    add_paragraph(doc, "En situaciones justificadas por causas de vulnerabilidad o fuerza mayor, el Juez Calificador cuenta con la atribución legal de condonar total o parcialmente los recargos acumulados. Todas las condonaciones exigen justificación obligatoria y quedan registradas de manera inmutable en la bitácora judicial:")

    add_figure_image(doc, "figura_09.png", "juez",
                     "Figura 9. Consulta y registro de condonaciones de recargos por mora.", width_inches=6.2)

    # CAPÍTULO 41
    add_h1(doc, "41. Corte de Caja Judicial Diario y Arqueo de Barandilla")
    add_paragraph(doc, "Al terminar la jornada o turno judicial, el juez calificador realiza el cierre y corte de caja (/juez/corte). El sistema genera la relación de todos los recibos oficiales REC emitidos, desglosados por efectivo y tarjeta bancaria, permitiendo exportar el reporte en CSV e imprimirlo para entrega formal a Tesorería:")

    add_figure_image(doc, "figura_10.png", "juez",
                     "Figura 10. Reporte de corte de caja del juez calificador con totales recaudados.", width_inches=6.2)

