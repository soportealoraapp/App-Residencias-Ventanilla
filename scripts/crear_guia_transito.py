# scripts/crear_guia_transito.py
# -*- coding: utf-8 -*-
import os
import docx
from generar_guias_word import (
    apply_document_styles, add_cover_page, add_h1, add_h2, add_h3,
    add_paragraph, add_bullet, add_callout, add_image_placeholder, add_styled_table
)

def generar_guia_transito(output_path):
    doc = docx.Document()
    apply_document_styles(doc)

    # PORTADA
    add_cover_page(
        doc,
        role_title="Agentes de Tránsito y Jefes de Turno",
        subtitle="Manual de Operación de Campo: App Móvil para Captura Digital de Infracciones y Sincronización Offline",
        target_audience="Oficiales de Tránsito Operativos y Mandos de Turno de Uriangato",
        version="1.0"
    )

    # ÍNDICE GENERAL
    add_h1(doc, "ÍNDICE GENERAL DEL MANUAL")
    add_bullet(doc, "1. Introducción y Arquitectura de Campo 'Offline-First'")
    add_bullet(doc, "2. Preparación del Dispositivo Móvil y Permisos Requeridos")
    add_bullet(doc, "3. Inicio de Turno y Autenticación con Placa Oficial")
    add_bullet(doc, "4. Centro de Mando (Dashboard del Oficial de Tránsito)")
    add_bullet(doc, "5. Procedimiento Paso a Paso: Levantamiento de Boleta Digital")
    add_bullet(doc, "6. Protocolo de Captura de Evidencias Fotográficas Obligatorias")
    add_bullet(doc, "7. Registro de Garantías Retenidas e Interés Fiscal")
    add_bullet(doc, "8. Operación Sin Cobertura de Red y Almacenamiento Seguro")
    add_bullet(doc, "9. Sincronización con el Servidor Municipal y Gestión de la Cola")
    add_bullet(doc, "10. Consulta de Historial, Perfil y Cierre de Turno")
    add_bullet(doc, "11. Solución de Problemas Frecuentes en Vía Pública")

    # CAPÍTULO 1
    add_h1(doc, "1. Introducción y Arquitectura de Campo 'Offline-First'")
    add_paragraph(doc, "La Aplicación Móvil para Agentes de Tránsito es la herramienta oficial de trabajo diseñada para sustituir las antiguas boletas de papel por un sistema de infracción 100% digital en la vía pública del Municipio de Uriangato, Guanajuato.")
    
    add_paragraph(doc, "A diferencia de un sistema de oficina tradicional, esta aplicación fue concebida bajo tres exigencias operativas reales de la calle:")
    add_bullet(doc, "La aplicación guarda todas las boletas y fotografías localmente en el teléfono. El agente puede levantar infracciones en zonas sin señal telefónica o con datos intermitentes sin perder ningún dato.", "1. Funciona Sin Conexión (Offline-First): ")
    add_bullet(doc, "Botones táctiles grandes (altura mínima de 54 px), textos de alta legibilidad y navegación simplificada para permitir que el oficial capture datos rápidamente aun estando de pie en el tráfico vehicular.", "2. Ergonomía para Uso con una Sola Mano: ")
    add_bullet(doc, "Cada boleta incluye sello de tiempo inviolable, coordenadas satelitales GPS, tres fotografías de evidencia obligatorias y desglose normativo de faltas.", "3. Validez Probatoria Plena: ")

    add_callout(doc, "legal", "ALCANCE OPERATIVO: EMISIÓN VS CALIFICACIÓN",
                "El oficial de tránsito registra los hechos, identifica al infractor y retiene las garantías correspondientes. El monto en UMA que el oficial ve en la app es ÚNICAMENTE REFERENCIAL. El importe exigible y definitivo es fijado por el Juez Calificador en apego al reglamento.")

    # CAPÍTULO 2
    add_h1(doc, "2. Preparación del Dispositivo Móvil y Permisos Requeridos")
    add_paragraph(doc, "Antes de salir a patrullaje o servicio de crucero, el oficial debe cerciorarse de las condiciones de su equipo:")
    add_bullet(doc, "Dispositivo con sistema operativo Android 10 o superior (o acceso al enlace web /agentes en navegador institucional).", "Sistema Operativo: ")
    add_bullet(doc, "La app solicita permiso de 'Ubicación precisa' al iniciar. Es obligatorio para registrar las coordenadas del hecho.", "Sensor GPS Activo: ")
    add_bullet(doc, "Permiso obligatorio para la toma de las tres fotografías de evidencia pericial.", "Cámara Fotográfica: ")
    add_bullet(doc, "Verifique contar con al menos 60% de carga al inicio de su jornada.", "Batería: ")

    # CAPÍTULO 3
    add_h1(doc, "3. Inicio de Turno y Autenticación con Placa Oficial")
    add_paragraph(doc, "El acceso a la app móvil no requiere correos electrónicos complejos; se realiza directamente con la identidad del oficial en activo.")
    
    add_paragraph(doc, "Pasos para iniciar turno:")
    add_bullet(doc, "1. Abra la aplicación 'Tránsito Uriangato' en el dispositivo móvil.")
    add_bullet(doc, "2. En el primer campo, digite su Número de Placa Oficial asignado (ejemplo: AGT-042 o número asignado).")
    add_bullet(doc, "3. En el segundo campo, capture su Contraseña o PIN confidencial de turno.")
    add_bullet(doc, "4. Presione el botón azul 'Iniciar Turno'.")
    add_bullet(doc, "5. Si cuenta con señal de red, la app descargará automáticamente en segundo plano el catálogo de 78 faltas actualizado y el valor vigente de la UMA. Si no hay señal, la app utilizará el catálogo guardado en la memoria segura del equipo.")

    add_image_placeholder(doc, "Inicio de Sesión / Login de Tránsito (app/login.tsx)",
                          "Formulario de acceso para oficiales mediante placa y contraseña/PIN.",
                          "Campo de Placa Oficial, campo de Contraseña/PIN, botón de ingreso 'Iniciar Turno' e indicador de versión.")

    add_callout(doc, "warning", "CONFIDENCIALIDAD DE LA PLACA Y EL PIN",
                "Todas las boletas levantadas en el turno quedarán asociadas irrevocablemente a su número de placa oficial. Nunca preste su sesión a otro oficial. Si comparte el teléfono, cada elemento debe cerrar su turno e iniciar con sus propias credenciales.")

    # CAPÍTULO 4
    add_h1(doc, "4. Centro de Mando (Dashboard del Oficial de Tránsito)")
    add_paragraph(doc, "Una vez autenticado, el oficial visualiza la pantalla principal o Centro de Mando (/app/dashboard.tsx).")

    add_styled_table(doc,
                     ["Elemento en Pantalla", "Descripción", "Utilidad Práctica"],
                     [
                         ["Indicador de Conectividad", "Ícono verde (Conectado) o gris (Modo Offline).", "Indica si las boletas se enviarán de inmediato o esperarán en cola."],
                         ["Boletas del Turno", "Contador de infracciones elaboradas en la jornada.", "Permite llevar el control estadístico personal."],
                         ["Boletas Pendientes", "Contador de boletas guardadas en el teléfono sin subir.", "Alerta al oficial de sincronizar antes de entregar turno."],
                         ["Botón 'Nueva Infracción'", "Botón principal destacado en color institucional.", "Acceso con un solo toque para iniciar una boleta de campo."],
                         ["Barra Inferior", "Navegación entre: Inicio, Mis Boletas y Mi Perfil.", "Cambio rápido de vista."]
                     ],
                     [1.8, 2.5, 2.2])

    add_image_placeholder(doc, "Dashboard de Tránsito (app/dashboard.tsx)",
                          "Centro de mando del oficial con contadores de actividad y estado de sincronización.",
                          "Semáforo de conectividad, contador de infracciones del turno, boletas en cola y botón destacado 'Nueva Infracción'.")

    # CAPÍTULO 5
    add_h1(doc, "5. Procedimiento Paso a Paso: Levantamiento de Boleta Digital")
    add_paragraph(doc, "Al presionar el botón 'Nueva Infracción', se abre el formulario de captura (/app/nueva-infraccion.tsx), estructurado en 6 secciones consecutivas.")

    add_h2(doc, "Sección A: Datos del Vehículo Infractor")
    add_bullet(doc, "Número de placas y entidad federativa emisora (Guanajuato, Michoacán, Querétaro, etc.).", "Placa y Estado: ")
    add_bullet(doc, "Nissan, Chevrolet, Volkswagen, Italika, Freightliner, etc.", "Marca y Submarca/Línea: ")
    add_bullet(doc, "Color predominante y año aproximado o modelo.", "Color y Modelo: ")
    add_bullet(doc, "Automóvil particular, Motocicleta, Camioneta, Autobús urbano, Taxi o Transporte de carga.", "Tipo y Servicio: ")

    add_h2(doc, "Sección B: Datos del Conductor")
    add_bullet(doc, "Nombre completo y domicilio proporcionado.", "Identidad: ")
    add_bullet(doc, "Número de licencia y tipo (Automovilista, Chofer, Motociclista).", "Licencia de Conducir: ")
    add_bullet(doc, "Si el conductor no está presente (vehículo mal estacionado) o se rehúsa a identificarse, marque la casilla 'Conductor Ausente / Se negó a proporcionar datos'.", "Casilla Especial: ")

    add_image_placeholder(doc, "Captura de Vehículo y Conductor (app/nueva-infraccion.tsx - Parte 1)",
                          "Formulario con los campos de placa, marca, modelo y datos del conductor infractor.",
                          "Campos de texto optimizados para teclado numérico y selector de tipo de vehículo.")

    add_h2(doc, "Sección C: Selección de Infracciones del Catálogo Normativo")
    add_paragraph(doc, "El catálogo municipal cuenta con 78 casillas normativas agrupadas en 15 categorías reglamentarias:")
    add_bullet(doc, "Estacionamiento prohibido, doble fila, rampas de personas con discapacidad, banquetas.", "Estacionamiento y Vía Pública: ")
    add_bullet(doc, "Exceso de velocidad, pasarse alto de semáforo, vuelta prohibida, sentido contrario.", "Circulación y Maniobras: ")
    add_bullet(doc, "Falta de casco protector en motocicletas, falta de cinturón, celular al conducir.", "Seguridad Vial: ")
    add_bullet(doc, "Falta de placas, placas sobrepuestas, tarjeta de circulación vencida.", "Documentación del Vehículo: ")
    add_bullet(doc, "Conducir bajo el influjo de alcohol o sustancias tóxicas.", "Estado de Ebriedad: ")

    add_paragraph(doc, "El oficial puede buscar la falta escribiendo palabras clave en el buscador o desplegar la categoría correspondiente. Al marcar una casilla, la app muestra de manera automática el rango referencial en UMA y su aproximación en pesos.")

    add_image_placeholder(doc, "Selector de Infracciones y Cálculo en UMA (app/nueva-infraccion.tsx - Parte 2)",
                          "Buscador dinámico de infracciones con casillas de verificación y cálculo referencial.",
                          "Buscador por palabra clave, categorías normativas colapsables y tarjeta de estimación en UMA/pesos.")

    # CAPÍTULO 6
    add_h1(doc, "6. Protocolo de Captura de Evidencias Fotográficas Obligatorias")
    add_paragraph(doc, "Para blindar jurídicamente la boleta contra impugnaciones, la aplicación exige la toma de hasta tres evidencias fotográficas mediante la cámara integrada:")

    add_styled_table(doc,
                     ["Evidencia", "Objetivo Pericial", "Recomendación de Captura"],
                     [
                         ["Foto 1: Placa / Vehículo", "Acreditar plenamente la unidad infractora.", "Enfoque claro de la lámina de circulación y parte trasera o delantera."],
                         ["Foto 2: Contexto Panorámico", "Demostrar el lugar de los hechos y la falta cometida.", "Toma amplia que muestre el disco de no estacionarse, la banqueta o el crucero."],
                         ["Foto 3: Documento / Detalle", "Respaldar retenciones o agravantes especiales.", "Foto de la licencia retenida, alcoholímetro o daño material."]
                     ],
                     [1.8, 2.5, 2.2])

    add_image_placeholder(doc, "Módulo de Cámara y Evidencias Fotográficas (app/nueva-infraccion.tsx)",
                          "Pantalla con tres recuadros para capturar y previsualizar las fotografías de campo.",
                          "Botones 'Tomar Foto Placa', 'Tomar Foto Contexto', 'Tomar Foto Detalle', con miniaturas capturadas.")

    # CAPÍTULO 7
    add_h1(doc, "7. Registro de Garantías Retenidas e Interés Fiscal")
    add_paragraph(doc, "Para garantizar el cumplimiento de la sanción administrativa, el oficial debe marcar qué documento u objeto retiene en garantía:")
    add_bullet(doc, "Placa de circulación (delantera o trasera).")
    add_bullet(doc, "Licencia de conducir.")
    add_bullet(doc, "Tarjeta de circulación del vehículo.")
    add_bullet(doc, "Vehículo remitido a depósito oficial / corralón mediante servicio de grúa.")

    add_callout(doc, "legal", "CADENA DE CUSTODIA DE GARANTÍAS",
                "El objeto retenido asentado en la boleta digital es el único que el Juez Calificador podrá devolver al ciudadano tras el pago. Verifique con precisión el número de serie o folio de la garantía retenida.")

    # CAPÍTULO 8
    add_h1(doc, "8. Operación Sin Cobertura de Red y Almacenamiento Seguro")
    add_paragraph(doc, "Al pulsar 'Guardar Boleta', la aplicación realiza las siguientes acciones:")
    add_bullet(doc, "1. Asigna automáticamente un Folio Correlativo Único estructurado con la placa del oficial (ejemplo: AGT42-2026-000125).")
    add_bullet(doc, "2. Obtiene las coordenadas GPS del dispositivo y sella la fecha y hora del hecho.")
    add_bullet(doc, "3. Guarda los datos y comprime las fotografías en la memoria cifrada del teléfono.")
    add_bullet(doc, "4. Emite un aviso de confirmación en pantalla.")
    add_bullet(doc, "5. Si hay internet, intenta el envío inmediato. Si no hay conexión, coloca la boleta en la Cola de Sincronización Local sin generar errores ni retrasar al oficial.")

    # CAPÍTULO 9
    add_h1(doc, "9. Sincronización con el Servidor Municipal y Gestión de la Cola")
    add_paragraph(doc, "En la sección 'Mis Boletas' (/app/boletas.tsx), el oficial puede consultar el estado de cada boleta elaborada:")

    add_bullet(doc, "🟡 Pendiente de Sincronizar: La boleta está a salvo en el teléfono esperando señal de red.", "Estados de Sincronización: ")
    add_bullet(doc, "🔵 Sincronizando: La app está transmitiendo los datos y subiendo las fotografías al servidor.", "")
    add_bullet(doc, "🟢 Sincronizada con Éxito: El servidor municipal recibió la boleta y ya está disponible en el panel del Juez Calificador.", "")
    add_bullet(doc, "🔴 Error en Sincronización: Falló la subida por corte abrupto de red. Requiere pulsar 'Reintentar'.", "")

    add_paragraph(doc, "Para forzar la sincronización manual cuando el oficial llega a una zona con buena cobertura Wi-Fi o red 4G/5G, basta con presionar el botón azul 'Sincronizar Todo Ahora' en la parte superior de la lista.")

    add_image_placeholder(doc, "Bandeja de Boletas Locales y Cola de Envío (app/boletas.tsx)",
                          "Listado de boletas guardadas en el teléfono con sus estados de sincronización visuales.",
                          "Tarjetas de boletas con folio, fecha, placas, etiquetas de color de estado y botón 'Sincronizar Todo Ahora'.")

    # CAPÍTULO 10
    add_h1(doc, "10. Consulta de Historial, Perfil y Cierre de Turno")
    add_paragraph(doc, "Antes de hacer entrega del dispositivo móvil al final del turno en comandancia, el oficial debe:")
    add_bullet(doc, "1. Ingresar a 'Mis Boletas' y verificar que el contador de 'Pendientes de Sincronizar' esté en CERO.")
    add_bullet(doc, "2. Si quedan pendientes, presionar 'Sincronizar Todo' y esperar la confirmación en verde de todas las boletas.")
    add_bullet(doc, "3. Ir a la pestaña 'Mi Perfil' (/app/perfil.tsx).")
    add_bullet(doc, "4. Presionar 'Cerrar Turno / Salir'. La sesión se cerrará de forma segura y el equipo quedará listo para el siguiente turno.")

    add_image_placeholder(doc, "Perfil del Oficial y Cierre de Turno (app/perfil.tsx)",
                          "Pantalla con la identificación del oficial en servicio y botón de entrega de turno.",
                          "Nombre del oficial, placa activa, sector asignado, total de infracciones del turno y botón 'Cerrar Turno'.")

    # CAPÍTULO 11
    add_h1(doc, "11. Solución de Problemas Frecuentes en Vía Pública")
    
    add_h2(doc, "¿Qué hacer si el GPS no fija coordenadas?")
    add_paragraph(doc, "Asegúrese de estar a cielo abierto y no dentro de un estacionamiento subterráneo. Si el sensor tarda más de 10 segundos, la app le permitirá guardar anotando la calle y esquina manualmente en el campo 'Observaciones'.")

    add_h2(doc, "¿Qué hacer si la cámara no abre?")
    add_paragraph(doc, "Revise que ninguna otra aplicación (como WhatsApp o la cámara nativa) esté abierta en segundo plano usando la cámara. Cierre las apps abiertas y vuelva a intentar.")

    add_h2(doc, "¿Qué ocurre si se apaga el teléfono mientras capturaba una boleta?")
    add_paragraph(doc, "Las boletas que ya fueron guardadas no se pierden gracias al almacenamiento seguro. Si estaba en medio de una captura no guardada, deberá reingresar los datos al encender el teléfono.")

    # Guardar
    doc.save(output_path)
    print(f"Guía de Tránsitos / Agentes generada en: {output_path}")

if __name__ == "__main__":
    out = os.path.join(os.path.dirname(__file__), "..", "docs", "guias_usuario", "Guia_Usuario_Transito_Agente.docx")
    generar_guia_transito(out)

