# scripts/partes_manual/parte7_anexos.py
# -*- coding: utf-8 -*-
import docx
from generar_guias_word import (
    add_h1, add_h2, add_h3, add_paragraph, add_bullet, add_callout, add_styled_table
)

def agregar_parte7(doc, add_part_header):
    add_part_header(
        doc,
        part_num="VII",
        part_title="Anexos Técnicos, Matrices y Glosario Canónico",
        part_description="Glosario oficial de términos del sistema, matriz de capacidades financieras COB-01 a COB-12, matriz de seguridad por rol y directorio institucional de soporte."
    )

    # ANEXO A
    add_h1(doc, "Anexo A: Glosario Canónico de Términos del Sistema")
    add_paragraph(doc, "Definiciones normativas y técnicas unificadas utilizadas a lo largo de toda la plataforma municipal:")

    add_styled_table(doc,
                     ["Término Canónico", "Definición Precisa en el Sistema"],
                     [
                         ["Solicitud / Expediente", "Registro de un trámite iniciado por un ciudadano en línea o en mostrador. Cuenta con folio, trámite, estatus, montos y sellos de tiempo."],
                         ["Folio de Solicitud", "Identificador público del expediente con formato SOL-AAAA-NNNNNN. Es el número con el que el ciudadano consulta su estatus."],
                         ["Trámite", "Cada uno de los siete procedimientos oficiales de movilidad digitalizados (UR-TT-T-01 a UR-TT-T-07)."],
                         ["Boleta de Infracción", "Registro digital de la falta levantada por un agente de tránsito en campo con folio único, georreferenciación GPS, evidencias fotográficas y garantías retenidas."],
                         ["Calificación Judicial", "Acto jurisdiccional del Juez Calificador de determinar el monto exigible de una boleta asignando UMA dentro de los rangos legales."],
                         ["Orden de Cobro", "Documento fiscal originado por un trámite o por una boleta calificada con folio correlativo único COB-AAAA-NNNNNN."],
                         ["Folio de Recibo (REC)", "Comprobante oficial inmutable expedido al ciudadano tras liquidar un cobro con formato REC-AAAA-NNNNNN."],
                         ["UMA", "Unidad de Medida y Actualización que sirve de base legal para el cálculo y tasación de las multas de tránsito."],
                         ["Pronto Pago", "Descuento del 40% aplicado automáticamente cuando la multa se liquida dentro de los primeros 10 días hábiles posteriores al hecho y antes de transcurrido un mes."],
                         ["Recargo por Mora", "Cargo mensual acumulado del 5% aplicable cuando la multa se liquida después de un mes de su emisión."],
                         ["Condonación", "Renuncia justificada total o parcial al recargo moratorio autorizada por la autoridad competente y registrada en bitácora."],
                         ["Permiso de Operador", "Cada una de las 14 secciones del panel administrativo que el Administrador General puede habilitar o revocar al Operador de Ventanilla."],
                         ["Concesión", "Título oficial emitido por la Presidencia y Dirección de Movilidad que faculta a una persona física o moral para prestar el servicio público de transporte."],
                         ["Corte de Caja", "Resumen consolidado de cobros de un periodo o turno agrupados por lugar de pago, cajero y concepto con desglose de condonaciones."],
                         ["Origen Presencial", "Expediente radicado en mostrador para una persona que acudió físicamente. Comparte la misma máquina de estados y orden de cobro que el trámite web."]
                     ],
                     [2.0, 4.5])

    # ANEXO B
    add_h1(doc, "Anexo B: Matriz de Trazabilidad y Capacidades de Cobros (COB-01 a COB-12)")
    add_paragraph(doc, "El módulo de cobros de Uriangato cumple con las doce especificaciones de recaudación y auditoría certificadas:")

    add_styled_table(doc,
                     ["Clave", "Capacidad Certificada", "Descripción Operativa"],
                     [
                         ["COB-01", "Cobro Unificado", "Cobro de trámites y multas desde un solo listado maestro."],
                         ["COB-02", "Folios Correlativos", "Secuencias oficiales únicas COB-AAAA-NNNNNN y REC-AAAA-NNNNNN."],
                         ["COB-03", "Corte de Caja en CSV", "Generación de corte de caja consolidado con exportación a hoja de cálculo."],
                         ["COB-04", "Conciliación BanBajío", "Búsqueda y acreditación de pagos bancarios mediante referencia."],
                         ["COB-05", "Hoja de Pago Imprimible", "Formato descargable para ventanilla o banco con desglose oficial."],
                         ["COB-06", "Historial de Movimientos", "Traza cronológica de cada cambio de saldo o estado sobre el cobro."],
                         ["COB-07", "Cancelación Motivada", "Cancelación justificada de órdenes de cobro por rechazo de trámite."],
                         ["COB-08", "Reclamo de Cuenta", "Activación en línea de cuentas creadas previamente en ventanilla física."],
                         ["COB-09", "Caja Judicial", "Módulo de cobro de multas en barandilla con entrega de garantías."],
                         ["COB-10", "Pronto Pago Automático", "Aplicación programada del 40% de descuento en 10 días hábiles."],
                         ["COB-11", "Condonación Auditada", "Registro de condonaciones de recargos por mora con motivo fundado."],
                         ["COB-12", "Caja en Ventanilla", "Cobro directo en mostrador municipal con expedición de recibo REC."]
                     ],
                     [1.1, 2.0, 3.4])

    # ANEXO C
    add_h1(doc, "Anexo C: Matriz de Seguridad y Acceso (Ruta × Rol × Filtro)")
    add_styled_table(doc,
                     ["Prefijo de Ruta", "Superficie", "Filtro de Seguridad", "Roles Autorizados"],
                     [
                         ["/ y /auth", "Portada y Acceso", "Público (Sin sesión)", "Cualquier ciudadano o usuario."],
                         ["/portal", "Portal Ciudadano", "auth + AdminRedirectFilter", "Rol ciudadano exclusivamente."],
                         ["/admin", "Panel Administrativo", "auth + role + PermisoOperadorFilter", "Roles administrador y operador_ventanilla."],
                         ["/juez", "Panel Judicial", "auth + role:juez_calificador", "Rol juez_calificador exclusivamente."],
                         ["/api", "API REST Móvil", "agenteToken (Bearer Token)", "Agentes de tránsito y mandos de turno activos."]
                     ],
                     [1.2, 1.8, 2.0, 1.5])

    # ANEXO D
    add_h1(doc, "Anexo D: Directorio Institucional y Soporte Técnico")
    add_paragraph(doc, "Para asesoría ciudadana, dudas normativas o reporte de incidencias técnicas en la plataforma:")
    add_bullet(doc, "Dirección de Movilidad y Transporte — Presidencia Municipal de Uriangato, Gto.", "Oficinas Centrales: ")
    add_bullet(doc, "Lunes a Viernes de 8:30 a 15:30 horas.", "Horario de Atención al Público: ")
    add_bullet(doc, "soporte.movilidad@uriangato.gob.mx", "Correo de Soporte Técnico: ")
    add_bullet(doc, "445 457 5000 extensión Movilidad y Transporte.", "Teléfono de Contacto: ")

