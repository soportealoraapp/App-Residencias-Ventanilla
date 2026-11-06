# Guías de Usuario Oficiales en Formato Word (.docx)
## Sistema Integral de Ventanilla Única de Movilidad y Transporte — Uriangato, Gto.

En este directorio se encuentran los cinco (5) manuales y guías de operación en formato **Microsoft Word (`.docx`)**, elaborados con base en el código fuente, la normativa municipal y los flujos reales de la plataforma.

---

### Inventario de Documentos Generados

| Rol del Usuario | Archivo Word (.docx) | Superficie / Acceso | Recuadros para Capturas |
|---|---|---|:---:|
| **Juez Calificador** | [`Guia_Usuario_Juez_Calificador.docx`](./Guia_Usuario_Juez_Calificador.docx) | Panel Judicial (`/juez`) | 9 recuadros |
| **Agentes de Tránsito y Jefes de Turno** | [`Guia_Usuario_Transito_Agente.docx`](./Guia_Usuario_Transito_Agente.docx) | App Móvil Expo/React Native y `/agentes` | 7 recuadros |
| **Administrador del Sistema** | [`Guia_Usuario_Administrador.docx`](./Guia_Usuario_Administrador.docx) | Panel Administrativo General (`/admin`) | 10 recuadros |
| **Operador de Ventanilla** | [`Guia_Usuario_Operador_Ventanilla.docx`](./Guia_Usuario_Operador_Ventanilla.docx) | Panel de Mostrador con Permisos (`/admin`) | Completa: 19 figuras con capturas reales (`capturas_operador/`) |
| **Ciudadano / Solicitante** | [`Guia_Usuario_Ciudadano.docx`](./Guia_Usuario_Ciudadano.docx) | Portal Web y Móvil (`/portal` y `/`) | Completa: 19 figuras con capturas reales (`capturas_ciudadano/`) |

---

### Instrucciones para Insertar Capturas de Pantalla en Word

Cada documento cuenta con **recuadros preformateados** con bordes punteados, fondo gris institucional e indicaciones precisas:

1. **Abra el archivo `.docx`** en Microsoft Word, LibreOffice Writer o Google Docs.
2. **Localice el recuadro** que contiene el encabezado `📷 [ESPACIO RESERVADO PARA CAPTURA DE PANTALLA DE REFERENCIA]`.
3. **Tome la captura de pantalla** en el sistema real o ambiente de pruebas (usando la herramienta Recortes de Windows `Win + Shift + S` o captura en el teléfono).
4. **Pegue la imagen**:
   - Haga clic dentro del recuadro y presione `Ctrl + V`.
   - Ajuste el tamaño de la imagen si lo desea (el ancho sugerido es de 15 a 16 cm).
5. **Guarde el documento**.

---

### Regeneración de los Documentos

Si en el futuro se modifican los textos o se desean regenerar los manuales desde cero, ejecute el siguiente comando desde la raíz del proyecto:

```powershell
& "C:\Users\Alejandro\AppData\Local\Programs\Python\Python312\python.exe" scripts/generar_todas_las_guias.py
```

