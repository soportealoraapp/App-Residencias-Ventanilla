# scripts/generar_manual_general_unificado.py
# -*- coding: utf-8 -*-
"""
Generador Maestro del Manual de Operación y Guía de Uso General Unificado
Sistema Integral de Ventanilla Única de Movilidad y Transporte — Uriangato, Gto.

Integra en una sola obra oficial los 5 roles del sistema e incrusta las 90 capturas de pantalla reales.
"""

import os
import sys
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT

# Agregar scripts al path
sys.path.insert(0, os.path.dirname(__file__))

from generar_guias_word import (
    apply_document_styles, add_h1, add_h2, add_h3,
    add_paragraph, add_bullet, add_callout, add_styled_table,
    set_cell_background, set_cell_margins, set_cell_borders,
    COLOR_PRIMARY_HEX, COLOR_PRIMARY_RGB, COLOR_SECONDARY_HEX, COLOR_SECONDARY_RGB,
    COLOR_TEXT_HEX, COLOR_TEXT_RGB
)

from partes_manual.parte1_vision_general import agregar_parte1
from partes_manual.parte2_ciudadano import agregar_parte2
from partes_manual.parte3_operador import agregar_parte3
from partes_manual.parte4_transito import agregar_parte4
from partes_manual.parte5_juez import agregar_parte5
from partes_manual.parte6_administrador import agregar_parte6
from partes_manual.parte7_anexos import agregar_parte7

BASE_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "docs", "guias_usuario"))
CAPT_DIR = os.path.join(BASE_DIR, "capturas_extraidas")

def add_master_cover_page(doc):
    """Portada institucional formal para el manual maestro unificado."""
    p_inst = doc.add_paragraph()
    p_inst.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_inst.paragraph_format.space_after = Pt(4)
    r1 = p_inst.add_run("GOBIERNO MUNICIPAL DE URIANGATO, GUANAJUATO\n")
    r1.font.name = 'Calibri'
    r1.font.size = Pt(13)
    r1.font.bold = True
    r1.font.color.rgb = COLOR_PRIMARY_RGB

    r2 = p_inst.add_run("DIRECCIÓN DE MOVILIDAD Y TRANSPORTE")
    r2.font.name = 'Calibri'
    r2.font.size = Pt(11)
    r2.font.bold = True
    r2.font.color.rgb = COLOR_SECONDARY_RGB

    # Línea decorativa
    p_line = doc.add_paragraph()
    p_line.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_line.paragraph_format.space_after = Pt(36)
    r_line = p_line.add_run("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━")
    r_line.font.color.rgb = COLOR_PRIMARY_RGB
    r_line.font.size = Pt(10)

    # Título del Sistema
    p_sys = doc.add_paragraph()
    p_sys.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_sys.paragraph_format.space_after = Pt(14)
    r_sys = p_sys.add_run("SISTEMA INTEGRAL DE VENTANILLA ÚNICA\nDE MOVILIDAD Y TRANSPORTE")
    r_sys.font.name = 'Calibri'
    r_sys.font.size = Pt(19)
    r_sys.font.bold = True
    r_sys.font.color.rgb = COLOR_SECONDARY_RGB

    # Nombre del documento maestro
    p_doc = doc.add_paragraph()
    p_doc.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_doc.paragraph_format.space_after = Pt(24)
    r_doc = p_doc.add_run("MANUAL DE OPERACIÓN Y GUÍA DE USO GENERAL\n(MANUAL MAESTRO MULTI-ROL)")
    r_doc.font.name = 'Calibri'
    r_doc.font.size = Pt(15)
    r_doc.font.bold = True
    r_doc.font.color.rgb = COLOR_PRIMARY_RGB

    # Tarjeta de alcance multi-rol
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = table.cell(0, 0)
    cell.width = Inches(6.5)
    set_cell_background(cell, "EEF2FF")
    set_cell_margins(cell, top=200, bottom=200, left=260, right=260)
    set_cell_borders(cell,
                     top={'val': 'single', 'sz': '8', 'color': '818CF8'},
                     bottom={'val': 'single', 'sz': '8', 'color': '818CF8'},
                     left={'val': 'single', 'sz': '24', 'color': COLOR_PRIMARY_HEX},
                     right={'val': 'single', 'sz': '8', 'color': '818CF8'})
    
    p_card = cell.paragraphs[0]
    p_card.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_card.paragraph_format.space_after = Pt(3)
    r_c1 = p_card.add_run("COBERTURA INTEGRAL DE ROLES DE USUARIO\n")
    r_c1.font.name = 'Calibri'
    r_c1.font.size = Pt(11)
    r_c1.font.bold = True
    r_c1.font.color.rgb = COLOR_PRIMARY_RGB

    r_c2 = p_card.add_run("Ciudadano · Operador de Ventanilla · Agente de Tránsito / Jefe de Turno · Juez Calificador · Administrador General")
    r_c2.font.name = 'Calibri'
    r_c2.font.size = Pt(10)
    r_c2.font.color.rgb = COLOR_TEXT_RGB

    # Metadatos institucionales y control de versiones
    p_meta = doc.add_paragraph()
    p_meta.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_meta.paragraph_format.space_before = Pt(45)
    p_meta.paragraph_format.space_after = Pt(4)

    r_aud = p_meta.add_run("DOCUMENTO TÉCNICO Y OPERATIVO DE REFERENCIA OFICIAL\n")
    r_aud.font.name = 'Calibri'
    r_aud.font.size = Pt(10)
    r_aud.font.bold = True
    r_aud.font.color.rgb = COLOR_SECONDARY_RGB

    r_ver = p_meta.add_run("Versión de Plataforma: 6.0 Oficial | Módulo de Cobros: COB-01 a COB-12 Certificado\n")
    r_ver.font.name = 'Calibri'
    r_ver.font.size = Pt(9.5)
    r_ver.font.color.rgb = RGBColor(100, 116, 139)

    r_fec = p_meta.add_run("Actualizado con Evidencias Visuales: Noviembre 2026 | H. Ayuntamiento de Uriangato")
    r_fec.font.name = 'Calibri'
    r_fec.font.size = Pt(9.5)
    r_fec.font.color.rgb = RGBColor(100, 116, 139)

    doc.add_page_break()

def add_part_header(doc, part_num, part_title, part_description):
    """Inserta un separador de Parte principal con diseño institucional."""
    doc.add_page_break()

    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = table.cell(0, 0)
    cell.width = Inches(6.5)
    set_cell_background(cell, COLOR_PRIMARY_HEX)
    set_cell_margins(cell, top=260, bottom=260, left=300, right=300)

    p = cell.paragraphs[0]
    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
    p.paragraph_format.space_after = Pt(4)

    r_num = p.add_run(f"PARTE {part_num}\n")
    r_num.font.name = 'Calibri'
    r_num.font.size = Pt(12)
    r_num.font.bold = True
    r_num.font.color.rgb = RGBColor(147, 197, 253)

    r_tit = p.add_run(f"{part_title.upper()}\n")
    r_tit.font.name = 'Calibri'
    r_tit.font.size = Pt(16)
    r_tit.font.bold = True
    r_tit.font.color.rgb = RGBColor(255, 255, 255)

    r_desc = p.add_run(part_description)
    r_desc.font.name = 'Calibri'
    r_desc.font.size = Pt(10)
    r_desc.font.color.rgb = RGBColor(224, 231, 255)

    doc.add_paragraph().paragraph_format.space_after = Pt(12)

def add_figure_image(doc, img_filename, subfolder, caption_text, width_inches=6.0):
    """Incrusta una imagen centrada con su pie de figura formal."""
    img_path = os.path.join(CAPT_DIR, subfolder, img_filename)
    if not os.path.exists(img_path):
        print(f"[!] Advertencia: Imagen no encontrada: {img_path}")
        return

    p_img = doc.add_paragraph()
    p_img.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_img.paragraph_format.space_before = Pt(8)
    p_img.paragraph_format.space_after = Pt(3)
    p_img.paragraph_format.keep_with_next = True
    
    r_img = p_img.add_run()
    r_img.add_picture(img_path, width=Inches(width_inches))

    p_cap = doc.add_paragraph()
    p_cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_cap.paragraph_format.space_after = Pt(12)
    r_cap = p_cap.add_run(caption_text)
    r_cap.font.name = 'Calibri'
    r_cap.font.size = Pt(9.5)
    r_cap.font.italic = True
    r_cap.font.bold = True
    r_cap.font.color.rgb = RGBColor(71, 85, 105)

def add_figure_double_images(doc, img_file1, cap1, img_file2, cap2, subfolder="transito", col_width=3.1):
    """Incrusta dos imágenes verticales lado a lado en una tabla sin bordes."""
    path1 = os.path.join(CAPT_DIR, subfolder, img_file1)
    path2 = os.path.join(CAPT_DIR, subfolder, img_file2)

    table = doc.add_table(rows=2, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    
    # Fila 1: Imágenes
    cell_img1 = table.cell(0, 0)
    cell_img2 = table.cell(0, 1)
    cell_img1.width = Inches(col_width)
    cell_img2.width = Inches(col_width)

    set_cell_margins(cell_img1, top=60, bottom=40, left=60, right=60)
    set_cell_margins(cell_img2, top=60, bottom=40, left=60, right=60)

    p1 = cell_img1.paragraphs[0]
    p1.alignment = WD_ALIGN_PARAGRAPH.CENTER
    if os.path.exists(path1):
        p1.add_run().add_picture(path1, width=Inches(col_width - 0.2))

    p2 = cell_img2.paragraphs[0]
    p2.alignment = WD_ALIGN_PARAGRAPH.CENTER
    if os.path.exists(path2):
        p2.add_run().add_picture(path2, width=Inches(col_width - 0.2))

    # Fila 2: Pies de figura
    cell_cap1 = table.cell(1, 0)
    cell_cap2 = table.cell(1, 1)
    cell_cap1.width = Inches(col_width)
    cell_cap2.width = Inches(col_width)

    set_cell_margins(cell_cap1, top=20, bottom=80, left=40, right=40)
    set_cell_margins(cell_cap2, top=20, bottom=80, left=40, right=40)

    p_c1 = cell_cap1.paragraphs[0]
    p_c1.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r_c1 = p_c1.add_run(cap1)
    r_c1.font.name = 'Calibri'
    r_c1.font.size = Pt(9)
    r_c1.font.italic = True
    r_c1.font.bold = True
    r_c1.font.color.rgb = RGBColor(71, 85, 105)

    p_c2 = cell_cap2.paragraphs[0]
    p_c2.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r_c2 = p_c2.add_run(cap2)
    r_c2.font.name = 'Calibri'
    r_c2.font.size = Pt(9)
    r_c2.font.italic = True
    r_c2.font.bold = True
    r_c2.font.color.rgb = RGBColor(71, 85, 105)

    doc.add_paragraph().paragraph_format.space_after = Pt(8)

def main():
    print("================================================================================")
    print(" CONSTRUCCIÓN DEL MANUAL DE OPERACIÓN Y GUÍA DE USO GENERAL UNIFICADO")
    print(" Sistema Integral de Ventanilla Única de Movilidad y Transporte — Uriangato, Gto.")
    print("================================================================================\n")

    doc = docx.Document()
    apply_document_styles(doc)

    # PORTADA MAESTRA
    print("[1/8] Generando Portada Institucional y Metadatos...")
    add_master_cover_page(doc)

    # ÍNDICE GENERAL DEL MANUAL MAESTRO
    add_h1(doc, "ÍNDICE GENERAL DEL MANUAL MAESTRO")
    add_paragraph(doc, "Este manual unificado reúne de forma estructurada la totalidad de las guías de uso del sistema municipal, integrando los flujos de atención ciudadana, mostrador presencial, fiscalización de campo en calle, calificación judicial de infracciones y gobernanza administrativa general:")

    add_styled_table(doc,
                     ["Parte Temática", "Rol de Usuario que Aplica", "Superficie de Operación", "N° de Figuras"],
                     [
                         ["PARTE I: Visión General, Arquitectura y Reglas del Sistema", "Todos los Roles", "Infraestructura Global", "Diagramas y Tablas"],
                         ["PARTE II: Guía de Operación del Ciudadano", "Ciudadano / Solicitante", "Portal Web y Celular (/portal y /)", "19 Figuras"],
                         ["PARTE III: Guía de Operación del Operador de Ventanilla", "Operador de Ventanilla", "Panel Mostrador (/admin)", "19 Figuras"],
                         ["PARTE IV: Guía de Agentes de Tránsito y Mandos", "Agente de Tránsito / Jefe de Turno", "App Móvil de Campo (/agentes)", "15 Figuras"],
                         ["PARTE V: Guía de Operación del Juez Calificador", "Juez Calificador", "Panel Judicial (/juez)", "10 Figuras"],
                         ["PARTE VI: Guía del Administrador General", "Administrador General", "Panel Completo (/admin)", "27 Figuras"],
                         ["PARTE VII: Anexos Técnicos, Matrices y Glosario", "Todos los Roles", "Soporte Normativo y Técnico", "Tablas COB y Glosario"]
                     ],
                     [2.4, 1.8, 1.6, 0.7])

    # PARTE I
    print("[2/8] Redactando Parte I: Visión General, Arquitectura y Reglas...")
    agregar_parte1(doc, add_part_header)

    # PARTE II
    print("[3/8] Redactando Parte II: Guía del Ciudadano (19 figuras)...")
    agregar_parte2(doc, add_part_header, add_figure_image, add_figure_double_images)

    # PARTE III
    print("[4/8] Redactando Parte III: Guía del Operador de Ventanilla (19 figuras)...")
    agregar_parte3(doc, add_part_header, add_figure_image)

    # PARTE IV
    print("[5/8] Redactando Parte IV: Guía de Agentes de Tránsito (15 figuras)...")
    agregar_parte4(doc, add_part_header, add_figure_image, add_figure_double_images)

    # PARTE V
    print("[6/8] Redactando Parte V: Guía del Juez Calificador (10 figuras)...")
    agregar_parte5(doc, add_part_header, add_figure_image)

    # PARTE VI
    print("[7/8] Redactando Parte VI: Guía del Administrador General (27 figuras)...")
    agregar_parte6(doc, add_part_header, add_figure_image)

    # PARTE VII
    print("[8/8] Redactando Parte VII: Anexos Técnicos, Matrices y Glosario...")
    agregar_parte7(doc, add_part_header)

    # Guardado del documento maestro
    out_path = os.path.join(BASE_DIR, "Manual_de_Uso_General_Sistema_Movilidad.docx")
    print(f"\n[*] Guardando archivo maestro en:\n{out_path}")
    doc.save(out_path)

    # Verificación del archivo generado
    tamano_bytes = os.path.getsize(out_path)
    tamano_mb = tamano_bytes / (1024 * 1024)
    print(f"[+] ¡Éxito! Manual maestro generado correctamente ({tamano_mb:.2f} MB).")

if __name__ == "__main__":
    main()
