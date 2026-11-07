# Guías de Usuario y Manual Maestro de Movilidad y Transporte
## Sistema Integral de Ventanilla Única de Movilidad y Transporte — Uriangato, Guanajuato

En este repositorio se encuentran los manuales de operación y guías de uso oficiales en formato **Microsoft Word (`.docx`)**, elaborados con base en el código fuente, la normativa municipal y enriquecidos con **todas las capturas de pantalla reales** de la plataforma.

---

### 📘 Documento Maestro Unificado (Recomendado)

| Documento Maestro | Archivo Word (.docx) | Alcance / Cobertura | Capturas Reales | Tamaño |
|---|---|---|:---:|:---:|
| **Manual de Operación y Guía de Uso General (Multi-Rol)** | [`Manual_de_Uso_General_Sistema_Movilidad.docx`](./Manual_de_Uso_General_Sistema_Movilidad.docx) | **Todos los roles unificados** en 7 partes temáticas y 57 capítulos: Ciudadano, Operador de Ventanilla, Agente de Tránsito / Jefe de Turno, Juez Calificador y Administrador General. | **90 figuras** | **9.5 MB** |

---

### 📑 Guías Individuales por Rol

Si se requiere distribuir el manual exclusivamente a un área o actor específico, se dispone de las guías individuales por rol:

| Rol del Usuario | Archivo Word (.docx) | Superficie / Acceso | Capturas Reales |
|---|---|---|:---:|
| **Ciudadano / Solicitante** | [`Guia_Usuario_Ciudadano.docx`](./Guia_Usuario_Ciudadano.docx) | Portal Web y Móvil (`/portal` y `/`) | 19 figuras |
| **Operador de Ventanilla** | [`Guia_Usuario_Operador_Ventanilla.docx`](./Guia_Usuario_Operador_Ventanilla.docx) | Panel de Mostrador con Permisos (`/admin`) | 19 figuras |
| **Agentes de Tránsito y Jefes de Turno** | [`Guia_Usuario_Transito_Agente.docx`](./Guia_Usuario_Transito_Agente.docx) | App Móvil Expo/React Native y `/agentes` | 15 figuras |
| **Juez Calificador** | [`Guia_Usuario_Juez_Calificador.docx`](./Guia_Usuario_Juez_Calificador.docx) | Panel Judicial (`/juez`) | 10 figuras |
| **Administrador del Sistema** | [`Guia_Usuario_Administrador.docx`](./Guia_Usuario_Administrador.docx) | Panel Administrativo General (`/admin`) | 27 figuras |

---

### Estructura del Manual Maestro Unificado

El manual maestro [`Manual_de_Uso_General_Sistema_Movilidad.docx`](./Manual_de_Uso_General_Sistema_Movilidad.docx) está organizado en **7 partes fundamentales**:

1. **PARTE I: Visión General, Arquitectura y Reglas del Sistema**:
   - Propósito y problemática de movilidad en Uriangato.
   - Arquitectura unificada de las 3 ramas y base de datos relacional única.
   - Matriz de roles y superficies de acceso.
   - Catálogo oficial de los siete trámites digitalizados (`UR-TT-T-01` a `UR-TT-T-07`) y tarifas.
   - Ciclo de vida del expediente y máquina de estados transversal.
   - Reglas fiscales: valor de UMA, pronto pago del 40%, recargo por mora del 5%, principio «Cobrar no es administrar» y trazabilidad inmutable.

2. **PARTE II: Guía de Operación del Ciudadano (Portal Web y Celular)**:
   - Formatos digitales admitidos (PDF, JPG, PNG, 10 MB, hash SHA-256).
   - Creación de cuenta, login y procedimiento de reclamo de cuenta presencial (COB-08).
   - Navegación en PC y en celular con barra táctil inferior fija.
   - Radicación paso a paso de trámites y obtención del Folio Oficial.
   - Seguimiento en «Mis Solicitudes» con semáforo de colores explicativo.
   - Atención a prevenciones documentales sin costo adicional.
   - Métodos de pago (en línea o con Hoja de Pago COB-05 para ventanilla / BanBajío).
   - Descarga e impresión de documentos resolutivos emitidos con validez jurídica.
   - *(19 Figuras reales incrustadas)*.

3. **PARTE III: Guía de Operación del Operador de Ventanilla (Mostrador)**:
   - Misión y barra lateral dinámica por permisos (1 a 14 módulos).
   - Protocolo de atención ciudadana y búsqueda obligatoria por CURP.
   - Radicación presencial asistida, alta rápida y prevención preventiva de homonimias.
   - Emisión de la Hoja de Radicación con folio oficial.
   - Revisión documental en mostrador y notificaciones de prevención fundadas.
   - Cobro en ventanilla (efectivo/terminal) y expedición de recibos oficiales `REC-AAAA-NNNNNN`.
   - Conciliación de pagos BanBajío por número de referencia.
   - Agenda y registro de verificaciones físicas e inspecciones vehiculares (UR-02 y UR-04).
   - Almacén de formatos descargables para impresión y entrega física.
   - Arqueo parcial de caja y cierre de turno.
   - *(19 Figuras reales incrustadas)*.

4. **PARTE IV: Guía de Agentes de Tránsito y Mandos de Turno (App Móvil)**:
   - Filosofía de campo «Offline-First» y ergonomía para uso con una sola mano.
   - Preparación de dispositivos Android, sensor GPS y cámara pericial.
   - Inicio de turno con número de placa oficial y PIN confidencial.
   - Dashboard móvil con UMA del día y semáforo de conectividad.
   - Captura paso a paso de boleta digital (vehículo, conductor, 15 categorías / 78 faltas, garantías retenidas y coordenadas).
   - Protocolo pericial de las tres fotografías obligatorias (placa, contexto y falta/documento).
   - Guardado local cifrado y asignación de folio correlativo único.
   - Sincronización automática y manual con el servidor municipal.
   - Historial del turno, perfil del oficial y entrega segura de equipo.
   - *(15 Figuras reales incrustadas en formato individual y parejas lado a lado)*.

5. **PARTE V: Guía de Operación del Juez Calificador (Panel Judicial)**:
   - Marco competencial jurisdiccional y principio «Cobrar no es administrar».
   - Tablero de control judicial y resumen financiero en tiempo real.
   - Bandeja de boletas de campo recibidas y filtros por placa, fecha y agente.
   - Calificación judicial: revisión pericial de fotos, mapa GPS, garantías y asignación de UMA dentro de mínimos y máximos normativos.
   - Reglas fiscales automáticas: pronto pago (40%), cobro ordinario y recargo por mora mensual (5%).
   - Módulo de cobros judiciales en barandilla, emisión del recibo `REC` y devolución de garantías con sello de pagado.
   - Procedimiento de condonación motivada de recargos.
   - Corte de caja judicial diario, arqueo y exportación a formato CSV.
   - *(10 Figuras reales incrustadas)*.

6. **PARTE VI: Guía del Administrador General (Panel Completo)**:
   - Gobernanza institucional y máxima responsabilidad técnica de la plataforma.
   - Dashboard central y Desglose Global del Sistema (análisis exhaustivo de 11 subpaneles).
   - Gestión integral de expedientes de los 7 trámites y panel de dictámenes resolutivos.
   - Radicación presencial avanzada y vinculación de identidades ciudadanas.
   - Módulo maestro de cobros y conciliación bancaria BanBajío (COB-04).
   - Configuración granular de la Matriz de los 14 Permisos para operadores de ventanilla.
   - Padrón general de usuarios, asignación de roles y seguridad de contraseñas.
   - Administración de catálogos normativos (Tarifas, Concesiones, Convocatorias y Formatos).
   - Gobernanza de tránsito: alta de agentes, catálogo de faltas, valor de UMA, versión mínima de la app y monitoreo de sesiones.
   - Supervisión de verificaciones físicas e inspecciones mecánicas.
   - Bitácora inmutable de auditoría y trazabilidad histórica.
   - *(27 Figuras reales incrustadas)*.

7. **PARTE VII: Anexos Técnicos, Matrices y Glosario Canónico**:
   - Anexo A: Glosario canónico de términos (Folios, UMA, Expediente, Boleta, Condonación, Concesión, etc.).
   - Anexo B: Matriz de trazabilidad y capacidades del módulo de cobros (COB-01 a COB-12).
   - Anexo C: Matriz de seguridad y acceso: Ruta × Rol × Filtro.
   - Anexo D: Directorio institucional y solución de contingencias operativas.

---

### Regeneración del Manual Maestro

Para regenerar el documento maestro unificado a partir del código fuente y las capturas actualizadas, ejecute:

```powershell
& "C:\Users\Alejandro\AppData\Local\Programs\Python\Python312\python.exe" scripts/generar_manual_general_unificado.py
```
