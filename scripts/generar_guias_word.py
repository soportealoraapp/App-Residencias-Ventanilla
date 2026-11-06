# scripts/generar_guias_word.py
# -*- coding: utf-8 -*-
"""
Generador Automatizado de Guías de Usuario en Formato Microsoft Word (.docx)
para el Sistema Integral de Ventanilla Única de Movilidad y Transporte de Uriangato, Gto.

Genera 5 manuales completos por rol:
1. Guía del Juez Calificador (Panel Judicial /juez)
2. Guía de Agentes de Tránsito y Jefes de Turno (App Móvil Expo/React Native y /agentes)
3. Guía del Administrador del Sistema (Panel Administrativo /admin)
4. Guía del Operador de Ventanilla (Panel Administrativo con Permisos)
5. Guía del Ciudadano (Portal Ciudadano /portal y Portada Pública /)
"""

import os
import sys
from datetime import datetime
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import parse_xml, OxmlElement
from docx.oxml.ns import nsdecls, qn

# Colores Institucionales
COLOR_PRIMARY_HEX = "0D47A1"      # Azul institucional fuerte
COLOR_PRIMARY_RGB = RGBColor(13, 71, 161)

COLOR_SECONDARY_HEX = "1E293B"    # Azul pizarra / gris oscuro encabezados
COLOR_SECONDARY_RGB = RGBColor(30, 41, 59)

COLOR_TEXT_HEX = "334155"         # Texto estándar
COLOR_TEXT_RGB = RGBColor(51, 65, 85)

COLOR_LIGHT_BG_HEX = "F8FAFC"     # Fondo sutil tablas/contenedores
COLOR_BORDER_HEX = "CBD5E1"       # Borde sutil

COLOR_CALLOUT_INFO_BG = "E0F2FE"
COLOR_CALLOUT_INFO_BORDER = "0284C7"

COLOR_CALLOUT_WARN_BG = "FEF3C7"
COLOR_CALLOUT_WARN_BORDER = "D97706"

COLOR_CALLOUT_SUCCESS_BG = "DEF7EC"
COLOR_CALLOUT_SUCCESS_BORDER = "0E9F6E"

COLOR_CALLOUT_DANGER_BG = "FEE2E2"
COLOR_CALLOUT_DANGER_BORDER = "DC2626"

def set_cell_background(cell, hex_color):
    """Aplica color de fondo a una celda de tabla."""
    tcPr = cell._tc.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{hex_color}"/>')
    tcPr.append(shd)

def set_cell_margins(cell, top=140, bottom=140, left=200, right=200):
    """Establece márgenes internos (padding) para una celda (en dxa, 20 dxa = 1 pt)."""
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = parse_xml(
        f'<w:tcMar {nsdecls("w")}>'
        f'  <w:top w:w="{top}" w:type="dxa"/>'
        f'  <w:bottom w:w="{bottom}" w:type="dxa"/>'
        f'  <w:left w:w="{left}" w:type="dxa"/>'
        f'  <w:right w:w="{right}" w:type="dxa"/>'
        f'</w:tcMar>'
    )
    tcPr.append(tcMar)

def set_cell_borders(cell, top=None, bottom=None, left=None, right=None):
    """Configura bordes específicos de celda."""
    tcPr = cell._tc.get_or_add_tcPr()
    borders_xml = f'<w:tcBorders {nsdecls("w")}>'
    borders = {'top': top, 'bottom': bottom, 'left': left, 'right': right}
    for side, border in borders.items():
        if border:
            val = border.get('val', 'single')
            sz = border.get('sz', '4')
            color = border.get('color', 'CBD5E1')
            borders_xml += f'<w:{side} w:val="{val}" w:sz="{sz}" w:space="0" w:color="{color}"/>'
        else:
            borders_xml += f'<w:{side} w:val="none"/>'
    borders_xml += '</w:tcBorders>'
    tcPr.append(parse_xml(borders_xml))

def apply_document_styles(doc):
    """Configura márgenes y estilos base del documento."""
    for section in doc.sections:
        section.top_margin = Inches(1.0)
        section.bottom_margin = Inches(1.0)
        section.left_margin = Inches(1.0)
        section.right_margin = Inches(1.0)
        section.page_width = Inches(8.5)
        section.page_height = Inches(11.0)

    # Estilo Normal
    style_normal = doc.styles['Normal']
    font = style_normal.font
    font.name = 'Calibri'
    font.size = Pt(11)
    font.color.rgb = COLOR_TEXT_RGB
    style_normal.paragraph_format.line_spacing = 1.15
    style_normal.paragraph_format.space_after = Pt(6)

def add_cover_page(doc, role_title, subtitle, target_audience, version="1.0"):
    """Genera una portada institucional completa."""
    # Encabezado institucional superior
    p_inst = doc.add_paragraph()
    p_inst.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_inst.paragraph_format.space_after = Pt(4)
    r_inst1 = p_inst.add_run("GOBIERNO MUNICIPAL DE URIANGATO, GUANAJUATO\n")
    r_inst1.font.name = 'Calibri'
    r_inst1.font.size = Pt(13)
    r_inst1.font.bold = True
    r_inst1.font.color.rgb = COLOR_PRIMARY_RGB

    r_inst2 = p_inst.add_run("DIRECCIÓN DE MOVILIDAD Y TRANSPORTE")
    r_inst2.font.name = 'Calibri'
    r_inst2.font.size = Pt(11)
    r_inst2.font.bold = True
    r_inst2.font.color.rgb = COLOR_SECONDARY_RGB

    # Línea decorativa
    p_line = doc.add_paragraph()
    p_line.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_line.paragraph_format.space_after = Pt(40)
    r_line = p_line.add_run("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━")
    r_line.font.color.rgb = COLOR_PRIMARY_RGB
    r_line.font.size = Pt(10)

    # Título del Sistema
    p_sys = doc.add_paragraph()
    p_sys.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_sys.paragraph_format.space_after = Pt(12)
    r_sys = p_sys.add_run("SISTEMA INTEGRAL DE VENTANILLA ÚNICA\nDE MOVILIDAD Y TRANSPORTE")
    r_sys.font.name = 'Calibri'
    r_sys.font.size = Pt(18)
    r_sys.font.bold = True
    r_sys.font.color.rgb = COLOR_SECONDARY_RGB

    # Nombre del documento
    p_doc = doc.add_paragraph()
    p_doc.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_doc.paragraph_format.space_after = Pt(28)
    r_doc = p_doc.add_run("MANUAL Y GUÍA DE OPERACIÓN PARA EL USUARIO")
    r_doc.font.name = 'Calibri'
    r_doc.font.size = Pt(14)
    r_doc.font.color.rgb = RGBColor(100, 116, 139)

    # Tarjeta con el Rol Destacado
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = table.cell(0, 0)
    cell.width = Inches(6.5)
    set_cell_background(cell, "EEF2FF")
    set_cell_margins(cell, top=260, bottom=260, left=300, right=300)
    set_cell_borders(cell,
                     top={'val': 'single', 'sz': '8', 'color': '818CF8'},
                     bottom={'val': 'single', 'sz': '8', 'color': '818CF8'},
                     left={'val': 'single', 'sz': '24', 'color': COLOR_PRIMARY_HEX},
                     right={'val': 'single', 'sz': '8', 'color': '818CF8'})
    
    p_role = cell.paragraphs[0]
    p_role.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_role.paragraph_format.space_after = Pt(4)
    r_rt = p_role.add_run(f"ROL: {role_title.upper()}\n")
    r_rt.font.name = 'Calibri'
    r_rt.font.size = Pt(18)
    r_rt.font.bold = True
    r_rt.font.color.rgb = COLOR_PRIMARY_RGB

    r_sub = p_role.add_run(subtitle)
    r_sub.font.name = 'Calibri'
    r_sub.font.size = Pt(11)
    r_sub.font.color.rgb = COLOR_TEXT_RGB

    # Metadatos al pie
    p_meta = doc.add_paragraph()
    p_meta.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_meta.paragraph_format.space_before = Pt(80)
    p_meta.paragraph_format.space_after = Pt(4)

    r_aud = p_meta.add_run(f"Público objetivo: {target_audience}\n")
    r_aud.font.name = 'Calibri'
    r_aud.font.size = Pt(10)
    r_aud.font.bold = True
    r_aud.font.color.rgb = COLOR_SECONDARY_RGB

    r_ver = p_meta.add_run(f"Versión de Software: 6.0 | Documento Versión: {version}\n")
    r_ver.font.name = 'Calibri'
    r_ver.font.size = Pt(9.5)
    r_ver.font.color.rgb = RGBColor(100, 116, 139)

    r_fec = p_meta.add_run(f"Fecha de Actualización: Noviembre 2026 | Estado: Operativo Oficial")
    r_fec.font.name = 'Calibri'
    r_fec.font.size = Pt(9.5)
    r_fec.font.color.rgb = RGBColor(100, 116, 139)

    # Salto de página
    doc.add_page_break()

def add_h1(doc, text):
    """Agrega un encabezado de nivel 1 estilizado."""
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(18)
    p.paragraph_format.space_after = Pt(8)
    p.paragraph_format.keep_with_next = True
    r = p.add_run(text)
    r.font.name = 'Calibri'
    r.font.size = Pt(16)
    r.font.bold = True
    r.font.color.rgb = COLOR_PRIMARY_RGB
    return p

def add_h2(doc, text):
    """Agrega un encabezado de nivel 2 estilizado."""
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(14)
    p.paragraph_format.space_after = Pt(6)
    p.paragraph_format.keep_with_next = True
    r = p.add_run(text)
    r.font.name = 'Calibri'
    r.font.size = Pt(13)
    r.font.bold = True
    r.font.color.rgb = COLOR_SECONDARY_RGB
    return p

def add_h3(doc, text):
    """Agrega un encabezado de nivel 3 estilizado."""
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(10)
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.keep_with_next = True
    r = p.add_run(text)
    r.font.name = 'Calibri'
    r.font.size = Pt(11.5)
    r.font.bold = True
    r.font.color.rgb = COLOR_TEXT_RGB
    return p

def add_paragraph(doc, text):
    """Agrega un párrafo estándar."""
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(6)
    r = p.add_run(text)
    r.font.name = 'Calibri'
    r.font.size = Pt(11)
    r.font.color.rgb = COLOR_TEXT_RGB
    return p

def add_bullet(doc, text, bold_prefix=None):
    """Agrega un punto con viñeta."""
    p = doc.add_paragraph(style='List Bullet')
    p.paragraph_format.space_after = Pt(3)
    if bold_prefix:
        r_pre = p.add_run(bold_prefix)
        r_pre.font.name = 'Calibri'
        r_pre.font.size = Pt(11)
        r_pre.font.bold = True
        r_pre.font.color.rgb = COLOR_SECONDARY_RGB
    r = p.add_run(text)
    r.font.name = 'Calibri'
    r.font.size = Pt(11)
    r.font.color.rgb = COLOR_TEXT_RGB
    return p

def add_callout(doc, callout_type, title, text):
    """Agrega una caja de aviso destacada (Info, Advertencia, Éxito, Peligro)."""
    configs = {
        'info': (COLOR_CALLOUT_INFO_BG, COLOR_CALLOUT_INFO_BORDER, 'ℹ️ NOTA INFORMATIVA'),
        'warning': (COLOR_CALLOUT_WARN_BG, COLOR_CALLOUT_WARN_BORDER, '⚠️ ADVERTENCIA OPERATIVA'),
        'success': (COLOR_CALLOUT_SUCCESS_BG, COLOR_CALLOUT_SUCCESS_BORDER, '✅ CONFIRMACIÓN / REQUISITO CUMPLIDO'),
        'legal': ("F1F5F9", COLOR_PRIMARY_HEX, '⚖️ MARCO NORMATIVO Y FUNDAMENTO LEGAL'),
        'tip': ("F0FDF4", "16A34A", '💡 CONSEJO PRÁCTICO')
    }
    bg_hex, border_hex, default_title = configs.get(callout_type, configs['info'])
    display_title = title if title else default_title

    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = table.cell(0, 0)
    cell.width = Inches(6.5)
    set_cell_background(cell, bg_hex)
    set_cell_margins(cell, top=140, bottom=140, left=200, right=200)
    set_cell_borders(cell,
                     top={'val': 'single', 'sz': '4', 'color': border_hex},
                     bottom={'val': 'single', 'sz': '4', 'color': border_hex},
                     left={'val': 'single', 'sz': '20', 'color': border_hex},
                     right={'val': 'single', 'sz': '4', 'color': border_hex})

    p = cell.paragraphs[0]
    p.paragraph_format.space_after = Pt(3)
    r_t = p.add_run(f"{display_title}\n")
    r_t.font.name = 'Calibri'
    r_t.font.size = Pt(10.5)
    r_t.font.bold = True
    r_t.font.color.rgb = COLOR_SECONDARY_RGB

    r_body = p.add_run(text)
    r_body.font.name = 'Calibri'
    r_body.font.size = Pt(10)
    r_body.font.color.rgb = COLOR_TEXT_RGB

    doc.add_paragraph().paragraph_format.space_after = Pt(2)

def add_image_placeholder(doc, screen_name, description, elements_to_show, dims="Ancho completo de página (aprox. 15 a 16 cm)"):
    """
    Inserta un contenedor preformateado y elegante para colocar una captura de pantalla de referencia.
    Incluye instrucciones de qué pantalla capturar y cómo pegar la imagen.
    """
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = table.cell(0, 0)
    cell.width = Inches(6.5)
    
    # Fondo gris claro profesional y borde punteado/sutil
    set_cell_background(cell, "F8FAFC")
    set_cell_margins(cell, top=180, bottom=180, left=220, right=220)
    set_cell_borders(cell,
                     top={'val': 'dashed', 'sz': '6', 'color': '94A3B8'},
                     bottom={'val': 'dashed', 'sz': '6', 'color': '94A3B8'},
                     left={'val': 'dashed', 'sz': '6', 'color': '94A3B8'},
                     right={'val': 'dashed', 'sz': '6', 'color': '94A3B8'})

    p = cell.paragraphs[0]
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_after = Pt(4)

    r_icon = p.add_run("📷 [ESPACIO RESERVADO PARA CAPTURA DE PANTALLA DE REFERENCIA]\n")
    r_icon.font.name = 'Calibri'
    r_icon.font.size = Pt(11)
    r_icon.font.bold = True
    r_icon.font.color.rgb = COLOR_PRIMARY_RGB

    r_screen = p.add_run(f"Pantalla o Módulo: {screen_name}\n")
    r_screen.font.name = 'Calibri'
    r_screen.font.size = Pt(10.5)
    r_screen.font.bold = True
    r_screen.font.color.rgb = COLOR_SECONDARY_RGB

    r_desc = p.add_run(f"Descripción: {description}\n")
    r_desc.font.name = 'Calibri'
    r_desc.font.size = Pt(9.5)
    r_desc.font.italic = True
    r_desc.font.color.rgb = COLOR_TEXT_RGB

    r_elem = p.add_run(f"Elementos clave a mostrar: {elements_to_show}\n")
    r_elem.font.name = 'Calibri'
    r_elem.font.size = Pt(9.5)
    r_elem.font.color.rgb = COLOR_TEXT_RGB

    r_hint = p.add_run(f"Dimensiones recomendadas: {dims}\n(Para insertar: clic derecho sobre este espacio o Ctrl+V para pegar la imagen)")
    r_hint.font.name = 'Calibri'
    r_hint.font.size = Pt(8.5)
    r_hint.font.color.rgb = RGBColor(148, 163, 184)

    doc.add_paragraph().paragraph_format.space_after = Pt(4)

def _tamano_captura(image_path, ancho_max, alto_max):
    """Calcula el ancho en pulgadas para que la imagen quepa en ancho_max x alto_max."""
    from PIL import Image
    with Image.open(image_path) as img:
        ancho_px, alto_px = img.size
    return min(ancho_max, alto_max * ancho_px / alto_px)

def _add_pie_figura(doc, caption):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(4)
    p.paragraph_format.space_after = Pt(10)
    r = p.add_run(caption)
    r.font.name = 'Calibri'
    r.font.size = Pt(9.5)
    r.font.italic = True
    r.font.color.rgb = COLOR_TEXT_RGB

def add_screenshots(doc, image_paths, caption, max_width=6.3, max_height=7.4):
    """Inserta una o varias capturas lado a lado, con marco sutil y pie de figura."""
    table = doc.add_table(rows=1, cols=len(image_paths))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    ancho_celda = max_width / len(image_paths)
    borde = {'val': 'single', 'sz': '4', 'color': COLOR_BORDER_HEX}
    for cell, image_path in zip(table.rows[0].cells, image_paths):
        cell.width = Inches(ancho_celda)
        set_cell_margins(cell, top=40, bottom=40, left=40, right=40)
        set_cell_borders(cell, top=borde, bottom=borde, left=borde, right=borde)
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.space_after = Pt(0)
        p.paragraph_format.keep_with_next = True
        ancho = _tamano_captura(image_path, ancho_celda - 0.08, max_height)
        p.add_run().add_picture(image_path, width=Inches(ancho))
    # La fila no se parte entre páginas: la imagen se queda junto a su pie.
    trPr = table.rows[0]._tr.get_or_add_trPr()
    trPr.append(OxmlElement('w:cantSplit'))
    _add_pie_figura(doc, caption)

def add_styled_table(doc, headers, data, col_widths=None):
    """Crea una tabla con cabecera en azul institucional y filas alternadas."""
    table = doc.add_table(rows=len(data) + 1, cols=len(headers))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False

    # Cabecera
    hdr_cells = table.rows[0].cells
    for i, title in enumerate(headers):
        hdr_cells[i].text = title
        set_cell_background(hdr_cells[i], COLOR_PRIMARY_HEX)
        set_cell_margins(hdr_cells[i], top=120, bottom=120, left=140, right=140)
        p = hdr_cells[i].paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.LEFT
        for run in p.runs:
            run.font.name = 'Calibri'
            run.font.size = Pt(10)
            run.font.bold = True
            run.font.color.rgb = RGBColor(255, 255, 255)

    # Filas de datos
    for r_idx, row_data in enumerate(data):
        row_cells = table.rows[r_idx + 1].cells
        bg_color = "F8FAFC" if r_idx % 2 == 1 else "FFFFFF"
        for c_idx, val in enumerate(row_data):
            row_cells[c_idx].text = str(val)
            set_cell_background(row_cells[c_idx], bg_color)
            set_cell_margins(row_cells[c_idx], top=90, bottom=90, left=120, right=120)
            set_cell_borders(row_cells[c_idx],
                             top={'val': 'single', 'sz': '2', 'color': 'E2E8F0'},
                             bottom={'val': 'single', 'sz': '2', 'color': 'E2E8F0'},
                             left={'val': 'single', 'sz': '2', 'color': 'E2E8F0'},
                             right={'val': 'single', 'sz': '2', 'color': 'E2E8F0'})
            p = row_cells[c_idx].paragraphs[0]
            p.alignment = WD_ALIGN_PARAGRAPH.LEFT
            for run in p.runs:
                run.font.name = 'Calibri'
                run.font.size = Pt(9.5)
                run.font.color.rgb = COLOR_TEXT_RGB

    # Las filas no se parten entre páginas y el encabezado se repite en cada una.
    for row in table.rows:
        row._tr.get_or_add_trPr().append(OxmlElement('w:cantSplit'))
    table.rows[0]._tr.get_or_add_trPr().append(OxmlElement('w:tblHeader'))
    for cell in hdr_cells:
        cell.paragraphs[0].paragraph_format.keep_with_next = True

    # Anchos de columna si se proporcionan
    if col_widths:
        for row in table.rows:
            for idx, width in enumerate(col_widths):
                row.cells[idx].width = Inches(width)

    doc.add_paragraph().paragraph_format.space_after = Pt(4)

print("Módulo base de generación de estilos inicializado con éxito.")

