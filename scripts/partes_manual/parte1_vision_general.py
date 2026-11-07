# scripts/partes_manual/parte1_vision_general.py
# -*- coding: utf-8 -*-
import docx
from generar_guias_word import (
    add_h1, add_h2, add_h3, add_paragraph, add_bullet, add_callout, add_styled_table
)

def agregar_parte1(doc, add_part_header):
    add_part_header(
        doc,
        part_num="I",
        part_title="Visión General, Arquitectura y Reglas del Sistema",
        part_description="Fundamento institucional, arquitectura unificada de tres superficies, catálogo oficial de trámites, ciclo de vida del expediente y marco fiscal municipal."
    )

    # CAPÍTULO 1
    add_h1(doc, "1. Propósito, Problemática Municipal y Alcance Institucional")
    add_paragraph(doc, "El Sistema Integral de Ventanilla Única de Movilidad y Transporte del Municipio de Uriangato, Guanajuato, surge como una respuesta tecnológica e institucional para modernizar, transparentar y agilizar la gestión de los servicios públicos de transporte, la emisión de permisos de vialidad y la fiscalización del tránsito municipal.")
    
    add_paragraph(doc, "Históricamente, los trámites de movilidad en Uriangato obligaban a la ciudadanía a acudir en reiteradas ocasiones a las oficinas de la Dirección de Movilidad y Transporte, presentar legajos de documentos en papel, esperar revisiones manuales prolongadas y desplazarse a cajas de cobro foráneas. Los expedientes se administraban en carpetas físicas vulnerables al extravío o deterioro, mientras que las boletas de infracción de tránsito se levantaban en talonarios manuales, generando demoras en la calificación y falta de certeza para el ciudadano infractor.")

    add_paragraph(doc, "La plataforma digital resuelve esta problemática mediante tres pilares operativos unificados:")
    add_bullet(doc, "Inicia solicitudes en línea las 24 horas del día, sube documentos probatorios digitales, realiza pagos electrónicos o bancarios y consulta en todo momento el avance de su expediente mediante un Folio Único correlativo.", "1. Atención Ciudadana 100% Digital e Inclusiva: ")
    add_bullet(doc, "Recepción en mostrador para personas sin acceso a internet, revisión técnica documental, dictamen fundado, prevención de inconsistencias, cobranza unificada, corte de caja automatizado y auditoría inmutable de todas las acciones.", "2. Gestión Administrativa Centralizada: ")
    add_bullet(doc, "Levantamiento de boletas digitales en calle mediante aplicación móvil con geolocalización GPS, captura obligatoria de hasta tres fotografías periciales y funcionamiento garantizado 'offline-first' aun en ausencia de datos móviles.", "3. Fiscalización y Calificación de Tránsito en Campo: ")

    # CAPÍTULO 2
    add_h1(doc, "2. Arquitectura Integral de las Tres Ramas y Base de Datos Única")
    add_paragraph(doc, "El sistema no está compuesto por aplicaciones aisladas, sino por un ecosistema tecnológico cohesionado que comparte una sola base de datos relacional PostgreSQL con Row Level Security (RLS), garantizando la integridad referencial y financiera en todo momento.")

    add_styled_table(doc,
                     ["Superficie / Rama", "Tecnología Principal", "URL / Acceso", "Población Usuaria"],
                     [
                         ["Portal Ciudadano", "CodeIgniter 4.7.4 + Bootstrap 5", "/portal y /", "Ciudadanos, choferes, concesionarios y personas morales."],
                         ["Panel Administrativo", "CodeIgniter 4.7.4 + Filtros RBAC", "/admin", "Administrador General y Operadores de Ventanilla."],
                         ["Panel Judicial", "CodeIgniter 4.7.4 (Aislamiento Judicial)", "/juez", "Juez Calificador y personal de barandilla."],
                         ["App Móvil de Tránsito", "React Native 0.86 + Expo SDK 57", "App Android / /agentes", "Agentes de tránsito operativos y Jefes de turno."],
                         ["API REST Central", "PHP 8.2 + Autenticación Bearer Token", "/api", "Servicios de sincronización móvil de boletas y catálogo."]
                     ],
                     [1.6, 2.0, 1.4, 1.5])

    add_callout(doc, "info", "SEGURIDAD DE SESIONES Y ALMACENAMIENTO DUAL",
                "El sistema opera bajo un esquema de almacenamiento documental dual: en producción utiliza buckets seguros en la nube (Supabase Storage) con URLs firmadas temporalmente con vigencia de 1 hora; en desarrollo o contingencia, conmuta automáticamente a disco local seguro mediante tokens criptográficos HMAC-SHA256. Cada documento subido almacena la huella digital SHA-256 de su contenido para detectar cualquier alteración posterior.")

    # CAPÍTULO 3
    add_h1(doc, "3. Actores del Sistema, Matriz de Roles y Superficies de Acceso")
    add_paragraph(doc, "El control de acceso al sistema se rige por una matriz de roles estricta, implementada mediante filtros a nivel de ruta y verificaciones de base de datos:")

    add_styled_table(doc,
                     ["Actor / Rol", "Identificador Técnico", "Superficie de Entrada", "Alcance y Facultades Principales"],
                     [
                         ["Ciudadano", "ciudadano", "/portal y portada /", "Registrarse, radicar los 7 trámites, subir requisitos, pagar en línea o banco, consultar semáforo de estatus y descargar documentos resolutivos emitidos."],
                         ["Operador de Ventanilla", "operador_ventanilla", "/admin (Restringido)", "Atención al público en mostrador, radicación presencial, revisión documental, observaciones de prevención, cobro en caja y agenda de verificación física."],
                         ["Juez Calificador", "juez_calificador", "/juez (Exclusivo)", "Revisar boletas de campo, inspeccionar fotos periciales y mapa GPS, calificar en UMA, cobrar multas, autorizar condonaciones y cerrar caja judicial."],
                         ["Agente de Tránsito", "agente (Tabla agentes_transito)", "App Móvil / /agentes", "Iniciar turno con placa y PIN, capturar boletas sin internet, tomar 3 evidencias fotográficas, registrar garantías retenidas y sincronizar."],
                         ["Jefe de Turno", "jefe_turno (Tabla agentes_transito)", "App Móvil / /agentes", "Mismas facultades operativas del agente en calle, con visibilidad de su sector operativo."],
                         ["Administrador General", "administrador", "/admin (Total)", "Control absoluto: todas las funciones del operador más bajas definitivas, matriz de 14 permisos, catálogo de tarifas, concesiones, convocatorias, alta de agentes y bitácora de auditoría."]
                     ],
                     [1.4, 1.4, 1.4, 2.3])

    add_callout(doc, "legal", "PRINCIPIO CONSTITUCIONAL: «COBRAR NO ES ADMINISTRAR»",
                "Por diseño de seguridad institucional, el panel judicial (/juez) es inaccesible para el Administrador General, y el Juez Calificador no tiene acceso a configurar tarifas, usuarios ni concesiones. La calificación de faltas y el cobro judicial gozan de plena autonomía jurisdiccional.")

    # CAPÍTULO 4
    add_h1(doc, "4. Catálogo Maestro de los Siete Trámites Digitalizados")
    add_paragraph(doc, "El sistema gestiona la totalidad de los trámites regulados por la Dirección de Movilidad y Transporte de Uriangato, identificados mediante claves oficiales que vinculan la Unidad Responsable (UR) con el tipo de procedimiento:")

    add_styled_table(doc,
                     ["Clave Oficial", "Nombre Oficial del Trámite", "UR", "Costo Oficial", "Criterio de Tarifa", "Requisitos Documentales Obligatorios"],
                     [
                         ["UR-TT-T-01", "Concesión de transporte público de ruta fija", "UR-01", "$9,055.20", "Base (por convocatoria)", "Acta constitutiva/nacimiento, Constancia Fiscal, poder notarial, constancia de no antecedentes y acreditación de residencia."],
                         ["UR-TT-T-02", "Constancia de despintado de unidad", "UR-02", "$64.90", "Base", "Identificación oficial (INE), factura o documento de propiedad del vehículo y acreditación de revista física."],
                         ["UR-TT-T-03", "Orden de plaqueo para cambio de unidad", "UR-03", "$0.00 (Gratuito)", "Sin costo", "Identificación oficial, factura/carta factura del nuevo vehículo y comprobante de revista físico-mecánica aprobada."],
                         ["UR-TT-T-04", "Permiso eventual de transporte público", "UR-04", "$156.94", "Base", "Póliza de seguro vigente o fondo de garantía, tarjeta de circulación, identificación del solicitante y revista mecánica."],
                         ["UR-TT-T-05", "Permiso de cierre parcial o total de calle", "UR-05", "$287.00", "Base", "Identificación oficial, croquis de afectación vial, programa del evento u obra y anuencia vecinal si aplica."],
                         ["UR-TT-T-06", "Cesión de derechos de concesión", "—", "$9,055.20", "Cesión base", "Identificación de cedente y cesionario, título original de concesión, actas de nacimiento y resolución en caso de sucesión."],
                         ["UR-TT-T-07", "Permiso de carga y descarga en vía pública", "—", "Desde $191.00 hasta $57,325.00", "Tipo solicitante x Periodo", "Tarjeta de circulación, identificación oficial, comprobante de domicilio y especificación de tonelaje y horarios."]
                     ],
                     [1.0, 1.8, 0.6, 1.0, 1.0, 1.6])

    add_paragraph(doc, "Tarifas del Trámite UR-TT-T-07 (Carga y Descarga):")
    add_bullet(doc, "Día: $191.00 | Mes: $1,151.00 | Semestre: $5,752.00 | Año: $11,503.00 (por camión declarado).", "Solicitante Particular: ")
    add_bullet(doc, "Día: $573.00 | Mes: $5,732.00 | Semestre: $28,662.00 | Año: $57,325.00 (cubre flotilla de hasta 15 camiones).", "Empresa Establecida: ")

    # CAPÍTULO 5
    add_h1(doc, "5. Ciclo de Vida General del Expediente y Máquina de Estados")
    add_paragraph(doc, "Todo expediente en el sistema se gobierna por una máquina de estados finita estricta. Una transición de estatus no declarada en el código fuente es rechazada automáticamente por el motor de validación:")
    
    add_paragraph(doc, "Flujo cronológico estándar:")
    add_bullet(doc, "1. Recepción: El trámite ingresa por el portal ciudadano o por radicación presencial en mostrador. Estado inicial: 'Recibido'.")
    add_bullet(doc, "2. Revisión Documental / Estudio Técnico: El personal revisa autenticidad, vigencia y legibilidad de los requisitos.")
    add_bullet(doc, "3. Prevención (Opcional): Si un documento es ilegible o falta información, el expediente se pone en 'Prevención'. El ciudadano puede subsanar y reenviar sin generar otro folio.")
    add_bullet(doc, "4. Autorización para Pago: El expediente se dictamina favorablemente y el sistema genera automáticamente la orden de cobro en estado 'Pago Pendiente'.")
    add_bullet(doc, "5. Liquidación Financiera: El ciudadano paga en línea, en ventanilla o en BanBajío. El expediente transita a 'Pagado' y se emite el recibo REC.")
    add_bullet(doc, "6. Emisión Resolutiva: La autoridad emite el documento oficial (Constancia, Título, Permiso u Orden de Plaqueo), quedando en estado 'Concluido / Emitido'.")
    add_bullet(doc, "7. Rechazo Definitivo: Si la solicitud no es procedente conforme a la ley, se marca 'Rechazado'. Exige de forma obligatoria fundamentar el motivo y cancela cualquier orden de cobro pendiente.")

    # CAPÍTULO 6
    add_h1(doc, "6. Reglas de Negocio Transversales y Marco Fiscal Municipal")
    add_paragraph(doc, "El módulo financiero unificado de Uriangato opera bajo las siguientes directrices impositivas y de control:")
    add_bullet(doc, "Unidad de Medida y Actualización que sirve de base para la tasación de multas de tránsito. El Administrador actualiza su valor legal anualmente.", "Unidad UMA: ")
    add_bullet(doc, "Descuento del 40% aplicado automáticamente por el sistema si la infracción de tránsito es pagada dentro de los primeros 10 días hábiles posteriores al hecho y antes de transcurrido un mes.", "Beneficio de Pronto Pago: ")
    add_bullet(doc, "Recargo moratorio del 5% mensual acumulado exigible cuando la multa se liquida después de un mes de su emisión.", "Recargo por Mora: ")
    add_bullet(doc, "Facultad exclusiva de la autoridad facultada para condonar total o parcialmente recargos por mora bajo causas justificadas registradas en bitácora.", "Condonaciones Auditadas: ")
    add_bullet(doc, "Todo pago exitoso genera un folio correlativo único REC-AAAA-NNNNNN que sella de forma inmutable el expediente contable.", "Folio de Recibo Oficial: ")

