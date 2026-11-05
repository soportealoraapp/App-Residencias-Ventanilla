# scripts/crear_guia_administrador.py
# -*- coding: utf-8 -*-
import os
import docx
from generar_guias_word import (
    apply_document_styles, add_cover_page, add_h1, add_h2, add_h3,
    add_paragraph, add_bullet, add_callout, add_image_placeholder, add_styled_table
)

def generar_guia_administrador(output_path):
    doc = docx.Document()
    apply_document_styles(doc)

    # PORTADA
    add_cover_page(
        doc,
        role_title="Administrador del Sistema",
        subtitle="Manual Integral de Supervisión, Parametrización Normativa, Cobros y Gestión Global",
        target_audience="Titular de la Dirección de Movilidad, Administradores Generales y Personal de Control",
        version="1.0"
    )

    # ÍNDICE GENERAL
    add_h1(doc, "ÍNDICE GENERAL DEL MANUAL")
    add_bullet(doc, "1. Perfil, Responsabilidades y Alcance del Administrador")
    add_bullet(doc, "2. Acceso al Panel Administrativo y Entorno de Trabajo (/admin)")
    add_bullet(doc, "3. Tablero de Control y Desglose Global del Sistema (11 Subpaneles)")
    add_bullet(doc, "4. Gestión Integral de Expedientes y Solicitudes de Trámite")
    add_bullet(doc, "5. Radicación Presencial en Ventanilla y Prevención de Homonimias")
    add_bullet(doc, "6. Módulo Central de Cobros, Pagos BanBajío y Finanzas")
    add_bullet(doc, "7. Matriz de Permisos Granulares para el Operador de Ventanilla")
    add_bullet(doc, "8. Padrón y Administración de Usuarios y Seguridad")
    add_bullet(doc, "9. Gestión de Catálogos Normativos (Tarifas, Concesiones, Convocatorias y Formatos)")
    add_bullet(doc, "10. Configuración del Módulo de Tránsito (Agentes, Faltas y Parámetros UMA)")
    add_bullet(doc, "11. Agenda de Verificaciones Físicas e Inspecciones Mecánicas")
    add_bullet(doc, "12. Auditoría, Trazabilidad Inmutable y Respaldo")
    add_bullet(doc, "13. Solución de Contingencias Operativas y Soporte")

    # CAPÍTULO 1
    add_h1(doc, "1. Perfil, Responsabilidades y Alcance del Administrador")
    add_paragraph(doc, "El Administrador del Sistema cuenta con las facultades más amplias dentro de la plataforma digital municipal. Es el responsable de la gobernanza técnica y operativa del Sistema Integral de Ventanilla Única de Movilidad y Transporte de Uriangato, Gto.")
    
    add_paragraph(doc, "Sus atribuciones clave comprenden:")
    add_bullet(doc, "Supervisar el flujo completo de los siete trámites oficiales de movilidad (UR-TT-T-01 al T-07).", "Supervisión de Trámites: ")
    add_bullet(doc, "Garantizar la conciliación de pagos en ventanilla, transferencias y pagos bancarios en BanBajío.", "Fiscalización de Ingresos: ")
    add_bullet(doc, "Configurar las 14 secciones del panel que puede o no operar el personal de mostrador.", "Control de Permisos de Operadores: ")
    add_bullet(doc, "Dar de alta a los agentes de tránsito en calle, actualizar el catálogo normativo de faltas y el valor legal de la UMA.", "Gobernanza de Tránsito: ")
    add_bullet(doc, "Monitorear la bitácora inmutable de eventos sensibles para auditoría interna y contraloría.", "Trazabilidad y Auditoría: ")

    # CAPÍTULO 2
    add_h1(doc, "2. Acceso al Panel Administrativo y Entorno de Trabajo (/admin)")
    add_paragraph(doc, "El acceso se realiza mediante navegador web institucional:")
    add_bullet(doc, "http://[servidor-municipal]/admin", "URL Oficial: ")
    add_bullet(doc, "Cuenta con rol privilegiado 'administrador' asignado en la base de datos.", "Perfil Requerido: ")

    add_paragraph(doc, "El entorno de trabajo se divide en dos áreas principales:")
    add_bullet(doc, "Menú vertical ubicado a la izquierda con las 14 secciones del sistema.", "Barra Lateral de Navegación: ")
    add_bullet(doc, "Área de visualización con tarjetas de indicadores, tablas con paginación bootstrap y formularios asistidos.", "Lienzo de Contenido: ")

    add_image_placeholder(doc, "Acceso al Panel Administrativo (/auth/login y /admin)",
                          "Formulario de autenticación segura y redirección al panel administrativo.",
                          "Campos de credenciales, logotipo municipal y acceso al dashboard principal.")

    # CAPÍTULO 3
    add_h1(doc, "3. Tablero de Control y Desglose Global del Sistema (11 Subpaneles)")
    add_paragraph(doc, "El administrador cuenta con dos herramientas analíticas estratégicas:")
    
    add_h2(doc, "A. Dashboard Administrativo (/admin/dashboard)")
    add_paragraph(doc, "Ofrece una vista rápida de la operación del día: expedientes ingresados en las últimas 24 horas, recaudación diaria, boletas de campo recibidas y citas de revista vehicular pendientes.")

    add_h2(doc, "B. Módulo de Desglose Global (/admin/desglose)")
    add_paragraph(doc, "Es una sección EXCLUSIVA del Administrador que reúne 11 subpaneles analíticos profundos:")

    add_styled_table(doc,
                     ["#", "Subpanel Analítico", "Información Estratégica que Proporciona"],
                     [
                         ["1", "Solicitudes por Estatus", "Conteo exacto de expedientes en cada estación de la máquina de estados."],
                         ["2", "Solicitudes por Trámite", "Distribución de la demanda ciudadana entre los trámites T-01 a T-07."],
                         ["3", "Origen de Radicación", "Comparativa porcentual: Trámites en línea vs Trámites en Ventanilla Presencial."],
                         ["4", "Serie Cronológica", "Comportamiento mensual acumulado de expedientes e ingresos."],
                         ["5", "Tiempos de Atención", "Días promedio transcurridos desde la recepción hasta la emisión del documento."],
                         ["6", "Control de Vigencias", "Monitoreo de permisos y títulos vigentes, próximos a vencer o vencidos."],
                         ["7", "Almacén Documental", "Total de archivos en custodia digital y espacio en disco/nube utilizado."],
                         ["8", "Padrón de Cuentas", "Estadística de cuentas ciudadanas activas vs presenciales sin reclamar."],
                         ["9", "Operación de Campo", "Desempeño de la app de tránsito: boletas emitidas por sector y agente."],
                         ["10", "Verificaciones Físicas", "Porcentaje de vehículos aprobados vs rechazados en revista mecánica."],
                         ["11", "Alertas Operativas", "Semáforos rojos para expedientes con rezago o documentos observados."]
                     ],
                     [0.5, 2.3, 3.7])

    add_image_placeholder(doc, "Módulo de Desglose Global del Sistema (/admin/desglose)",
                          "Vista panorámica de métricas institucionales y gráficas de control.",
                          "Tarjetas de los 11 subpaneles con enlaces directos para navegar a las listas ya filtradas.")

    # CAPÍTULO 4
    add_h1(doc, "4. Gestión Integral de Expedientes y Solicitudes de Trámite")
    add_paragraph(doc, "En la sección 'Solicitudes' (/admin/solicitudes), el administrador supervisa los expedientes de los siete trámites digitalizados de la Dirección de Movilidad:")

    add_styled_table(doc,
                     ["Clave Oficial", "Nombre del Trámite Municipal", "Criterio de Tarifa", "Importe Vigente"],
                     [
                         ["UR-TT-T-01", "Concesión de transporte público de ruta fija", "Base", "$9,055.20"],
                         ["UR-TT-T-02", "Constancia de despintado de unidad", "Base", "$64.90"],
                         ["UR-TT-T-03", "Orden de plaqueo para cambio de unidad", "Sin costo", "$0.00 (Gratuito)"],
                         ["UR-TT-T-04", "Permiso eventual de transporte", "Base", "$156.94"],
                         ["UR-TT-T-05", "Permiso para cierre parcial o total de calle", "Base", "$287.00"],
                         ["UR-TT-T-06", "Cesión de concesión de transporte", "Base", "$9,055.20"],
                         ["UR-TT-T-07", "Permiso de carga y descarga en vía pública", "Tipo x Periodo", "Desde $191.00 hasta $57,325.00"]
                     ],
                     [1.2, 2.8, 1.2, 1.3])

    add_h2(doc, "Operaciones sobre el Expediente:")
    add_bullet(doc, "Revisión documental y verificación de la huella digital criptográfica SHA-256 de los archivos.", "Inspección Técnica: ")
    add_bullet(doc, "Si un documento es ilegible o incorrecto, se marca 'Prevención'. Esto regresa el expediente al ciudadano para subsanar sin perder su folio.", "Prevención Documental: ")
    add_bullet(doc, "Avanza el expediente y genera automáticamente la orden de cobro pendiente.", "Autorización de Pago: ")
    add_bullet(doc, "Termina el expediente de forma definitiva. Exige de manera OBLIGATORIA ingresar el motivo formal fundado y motivado.", "Rechazo: ")
    add_bullet(doc, "Tras acreditarse el pago, se autoriza la generación e impresión del permiso o título oficial.", "Emisión Resolutiva: ")

    add_image_placeholder(doc, "Detalle del Expediente y Panel de Dictamen (/admin/solicitudes/ver/[id])",
                          "Ficha del trámite con documentos probatorios, historial de estados y botones de acción resolutiva.",
                          "Visor de documentos adjuntos, historial cronológico de cambios de estado y formulario de dictaminación.")

    # CAPÍTULO 5
    add_h1(doc, "5. Radicación Presencial en Ventanilla y Prevención de Homonimias")
    add_paragraph(doc, "Cuando un ciudadano acude a las oficinas sin haber creado una cuenta en línea, el sistema permite radicar el trámite desde ventanilla (/admin/solicitudes-presenciales) bajo un protocolo seguro:")
    
    add_bullet(doc, "1. Búsqueda por CURP: El sistema verifica si el solicitante ya existe en el padrón.")
    add_bullet(doc, "2. Control de Homonimia: Si existe coincidencia parcial de nombres, el sistema muestra una advertencia para evitar duplicar ciudadanos.")
    add_bullet(doc, "3. Alta de Cuenta Presencial: Si el ciudadano es nuevo, se crea un registro de usuario 'sin acceso al portal'. El ciudadano podrá reclamar su cuenta posteriormente en línea si lo desea (COB-08).")
    add_bullet(doc, "4. Selección de Trámite y Captura Asistida: Se digitan los datos y se asocian los documentos físicos cotejados.")
    add_bullet(doc, "5. Asignación de Folio: El expediente entra a la misma máquina de estados y genera la misma orden de cobro que los trámites web.")

    add_image_placeholder(doc, "Radicación Presencial en Ventanilla (/admin/solicitudes-presenciales)",
                          "Formulario de captura para trámites iniciados en ventanilla física.",
                          "Validador de CURP, alerta de homonimia, selector de trámite y generación de folio oficial.")

    # CAPÍTULO 6
    add_h1(doc, "6. Módulo Central de Cobros, Pagos BanBajío y Finanzas")
    add_paragraph(doc, "El módulo de cobros (/admin/cobros) es el núcleo financiero que unifica los ingresos por derechos de trámites y por multas de tránsito. Cuenta con 12 capacidades certificadas (COB-01 a COB-12):")

    add_bullet(doc, "Cada cobro cuenta con folio único correlativo COB-AAAA-NNNNNN.", "1. Folios Oficiales: ")
    add_bullet(doc, "Efectivo, Terminal bancaria, Pago en línea en el portal y Depósito en sucursal BanBajío.", "2. Medios de Pago: ")
    add_bullet(doc, "El administrador puede conciliar y registrar pagos realizados en banco buscando por la referencia bancaria única.", "3. Validación BanBajío (COB-04): ")
    add_bullet(doc, "Genera un documento imprimible con código de barras y referencia para que el ciudadano pague en ventanilla o banco.", "4. Hoja de Pago Oficial (COB-05): ")
    add_bullet(doc, "Registro de condonaciones autorizadas sobre recargos por mora con justificación auditada.", "5. Condonaciones (COB-11): ")
    add_bullet(doc, "Agrupa los ingresos por fecha, concepto y cajero, permitiendo exportar la relación contable en CSV.", "6. Corte de Caja Consolidado (COB-03): ")

    add_image_placeholder(doc, "Listado Maestro de Cobros (/admin/cobros)",
                          "Tabla general de pagos de trámites y multas de tránsito.",
                          "Columnas: Folio Cobro, Trámite/Boleta, Solicitante, Monto, Estatus (Pendiente/Pagado) y botones de acción.")

    add_image_placeholder(doc, "Registro de Pago Bancario BanBajío (/admin/cobros/banco)",
                          "Módulo para validar depósitos bancarios mediante referencia de pago.",
                          "Buscador por número de referencia, verificación de cuenta BanBajío y botón 'Confirmar Pago Bancario'.")

    # CAPÍTULO 7
    add_h1(doc, "7. Matriz de Permisos Granulares para el Operador de Ventanilla")
    add_paragraph(doc, "El Administrador puede modelar con precisión quirúrgica el nivel de acceso del rol 'operador_ventanilla' desde /admin/permisos-operador.")
    
    add_paragraph(doc, "El sistema cuenta con 14 casillas de permisos activables individualmente:")
    add_styled_table(doc,
                     ["Sección / Permiso", "Función que Habilita al Operador en Ventanilla"],
                     [
                         ["solicitudes", "Consultar, revisar y dictaminar expedientes de trámites."],
                         ["cobros", "Cobrar trámites y multas en ventanilla y consultar historial."],
                         ["usuarios", "Consultar el padrón y dar de alta ciudadanos."],
                         ["tarifas", "Consultar el catálogo oficial de tarifas."],
                         ["concesiones", "Consultar el padrón de concesiones de transporte."],
                         ["convocatorias", "Consultar convocatorias públicas de concesión."],
                         ["formatos", "Descargar formatos de trámite para entrega física."],
                         ["documentos", "Consultar el repositorio documental general."],
                         ["verificaciones", "Consultar la agenda y registrar inspecciones vehiculares."],
                         ["app_transito_config", "Visualizar parámetros y UMA de la app móvil."],
                         ["app_transito_boletas", "Consultar boletas levantadas en campo."],
                         ["app_transito_catalogo", "Consultar el catálogo de faltas de tránsito."],
                         ["app_transito_sesiones", "Ver el estado de conexión de los agentes."],
                         ["auditoria", "Consultar la bitácora de eventos del sistema."]
                     ],
                     [2.3, 4.2])

    add_callout(doc, "info", "SEGURIDAD DE ROLES", 
                "El Administrador nunca sufre restricciones de permisos. El operador sólo verá en su menú lateral las secciones que tengan su casilla marcada.")

    add_image_placeholder(doc, "Matriz de Permisos del Operador (/admin/permisos-operador)",
                          "Pantalla con interruptores para habilitar o inhabilitar secciones a los operadores de ventanilla.",
                          "Listado de los 14 permisos con casillas de selección y botón 'Guardar Matriz de Permisos'.")

    # CAPÍTULO 8
    add_h1(doc, "8. Padrón y Administración de Usuarios y Seguridad")
    add_paragraph(doc, "Desde la sección 'Usuarios' (/admin/usuarios), el administrador gestiona las cuentas internas y ciudadanas:")
    add_bullet(doc, "Alta de nuevos operadores de ventanilla o funcionarios municipales.")
    add_bullet(doc, "Actualización de correos electrónicos y teléfonos de contacto.")
    add_bullet(doc, "Restablecimiento seguro de contraseñas olvidadas.")
    add_bullet(doc, "Activación manual de cuentas creadas previamente en ventanilla presencial.")

    # CAPÍTULO 9
    add_h1(doc, "9. Gestión de Catálogos Normativos")
    add_paragraph(doc, "Los catálogos garantizan que el sistema opere con base en disposiciones oficiales aprobadas por el H. Ayuntamiento:")
    add_bullet(doc, "Permite actualizar los costos de los trámites ante modificaciones a la Ley de Ingresos Municipal.", "Catálogo de Tarifas (/admin/tarifas): ")
    add_bullet(doc, "Registro oficial de títulos de concesión, concesionarios, rutas asignadas y unidades autorizadas.", "Padrón de Concesiones (/admin/concesiones): ")
    add_bullet(doc, "Publicación de convocatorias para otorgamiento de concesiones, evaluación técnica y emisión de fallos de ganadores.", "Convocatorias Públicas (/admin/convocatorias): ")
    add_bullet(doc, "Subida y reemplazo de plantillas oficiales en formato PDF para descarga ciudadana.", "Formatos de Trámite (/admin/formatos): ")

    add_image_placeholder(doc, "Administración de Catálogos de Tarifas y Concesiones (/admin/catalogos)",
                          "Interfaces para dar de alta y editar tarifas oficiales y títulos de concesión.",
                          "Formularios de tarifas con montos en pesos, vigencias y catálogo de rutas y concesionarios.")

    # CAPÍTULO 10
    add_h1(doc, "10. Configuración del Módulo de Tránsito")
    add_paragraph(doc, "La supervisión de la operación de campo se concentra en tres secciones:")
    add_bullet(doc, "Definición del valor diario de la UMA, versión mínima obligatoria de la app móvil y activación del 'Modo Estricto de Evidencias'.", "1. Configuración de Parámetros (/admin/app-transito): ")
    add_bullet(doc, "Alta exclusiva de oficiales en campo, asignación de número de placa oficial, contraseña y sector de patrullaje.", "2. Padrón de Agentes de Tránsito: ")
    add_bullet(doc, "Mantenimiento a las 78 casillas normativas, descripción de la falta y rangos legales de UMA.", "3. Catálogo de Faltas (/admin/app-transito/catalogo): ")
    add_bullet(doc, "Monitoreo en tiempo real de los tokens de acceso y dispositivos conectados desde calle.", "4. Sesiones Activas (/admin/sesiones-app): ")

    add_image_placeholder(doc, "Supervisión de Agentes y Parámetros de Tránsito (/admin/app-transito)",
                          "Panel de control para configurar la UMA, registrar agentes y supervisar boletas.",
                          "Formulario de actualización de UMA en pesos, listado de agentes con botón 'Nuevo Agente' y bitácora de boletas.")

    # CAPÍTULO 11
    add_h1(doc, "11. Agenda de Verificaciones Físicas e Inspecciones Mecánicas")
    add_paragraph(doc, "En /admin/verificaciones se administra el calendario de revistas físico-mecánicas de unidades vehiculares (indispensables para los trámites UR-TT-T-02 de despintado y UR-TT-T-04 de permisos eventuales). Permite dictaminar si la unidad cumple con las condiciones físico-mecánicas de seguridad.")

    # CAPÍTULO 12
    add_h1(doc, "12. Auditoría, Trazabilidad Inmutable y Respaldo")
    add_paragraph(doc, "La sección 'Auditoría' (/admin/auditoria) contiene el registro histórico inmutable de cada acción sensible realizada en el sistema. Nadie (ni siquiera el administrador) puede borrar registros de auditoría.")

    add_styled_table(doc,
                     ["Campo de Auditoría", "Información que Registra"],
                     [
                         ["Usuario", "Nombre y correo del funcionario o ciudadano que ejecutó la acción."],
                         ["Fecha y Hora", "Sello de tiempo exacto al segundo."],
                         ["Dirección IP", "Dirección IP y datos del dispositivo de origen."],
                         ["Módulo y Acción", "Sección impactada (Cobros, Solicitudes, Concesiones, etc.) y tipo de acción (Crear, Actualizar, Condonar, Dictaminar)."],
                         ["Detalle de Cambios", "Valores anteriores vs valores nuevos modificados."]
                     ],
                     [2.0, 4.5])

    add_image_placeholder(doc, "Módulo de Auditoría del Sistema (/admin/auditoria)",
                          "Bitácora inmutable de eventos con buscador por usuario, fecha y módulo.",
                          "Tabla con columnas: Fecha/Hora, Usuario, Módulo, Acción, IP y botón para inspeccionar detalles del cambio.")

    # CAPÍTULO 13
    add_h1(doc, "13. Solución de Contingencias Operativas y Soporte")
    add_paragraph(doc, "Guía rápida para la atención de incidentes frecuentes:")
    add_bullet(doc, "Verifique el valor en pesos en /admin/app-transito. La app móvil se actualizará automáticamente en su siguiente conexión.", "Cambio de valor de UMA a inicio de año: ")
    add_bullet(doc, "Si un oficial extravía su teléfono, ingrese de inmediato a /admin/sesiones-app y revoque su token de acceso.", "Extravío de dispositivo móvil de tránsito: ")
    add_bullet(doc, "Utilice la herramienta de reclamo de cuenta (COB-08) o restablezca la contraseña desde /admin/usuarios.", "Ciudadano que no recuerda sus credenciales: ")

    # Guardar
    doc.save(output_path)
    print(f"Guía del Administrador generada en: {output_path}")

if __name__ == "__main__":
    out = os.path.join(os.path.dirname(__file__), "..", "docs", "guias_usuario", "Guia_Usuario_Administrador.docx")
    generar_guia_administrador(out)

