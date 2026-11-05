# scripts/generar_todas_las_guias.py
# -*- coding: utf-8 -*-
"""
Script Orquestador Maestro para Generar y Verificar las 5 Guías de Usuario en Word (.docx)
Sistema Integral de Ventanilla Única de Movilidad y Transporte de Uriangato
"""

import os
import sys
import docx

# Agregar directorio actual al path
sys.path.insert(0, os.path.dirname(__file__))

from crear_guia_juez import generar_guia_juez
from crear_guia_transito import generar_guia_transito
from crear_guia_administrador import generar_guia_administrador
from crear_guia_operador import generar_guia_operador
from crear_guia_ciudadano import generar_guia_ciudadano

def main():
    base_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
    output_dir = os.path.join(base_dir, "docs", "guias_usuario")
    os.makedirs(output_dir, exist_ok=True)

    guias = [
        ("Juez Calificador", "Guia_Usuario_Juez_Calificador.docx", generar_guia_juez),
        ("Agentes de Tránsito", "Guia_Usuario_Transito_Agente.docx", generar_guia_transito),
        ("Administrador", "Guia_Usuario_Administrador.docx", generar_guia_administrador),
        ("Operador de Ventanilla", "Guia_Usuario_Operador_Ventanilla.docx", generar_guia_operador),
        ("Ciudadano", "Guia_Usuario_Ciudadano.docx", generar_guia_ciudadano),
    ]

    print("================================================================================")
    print(" GENERACIÓN MAESTRA DE GUÍAS DE USUARIO EN FORMATO WORD (.DOCX)")
    print(" Municipio de Uriangato, Guanajuato — Dirección de Movilidad y Transporte")
    print("================================================================================\n")

    resumen = []

    for rol_nombre, archivo_nombre, func_generar in guias:
        ruta_completa = os.path.join(output_dir, archivo_nombre)
        print(f"[*] Generando guía para: {rol_nombre}...")
        func_generar(ruta_completa)
        
        # Validar archivo generado
        tamano_bytes = os.path.getsize(ruta_completa)
        tamano_kb = tamano_bytes / 1024
        
        # Inspeccionar elementos con python-docx
        doc = docx.Document(ruta_completa)
        num_parrafos = len(doc.paragraphs)
        num_tablas = len(doc.tables)
        
        # Contar placeholders de capturas de pantalla
        num_placeholders = 0
        for table in doc.tables:
            for row in table.rows:
                for cell in row.cells:
                    if "ESPACIO RESERVADO PARA CAPTURA DE PANTALLA" in cell.text:
                        num_placeholders += 1

        resumen.append({
            'rol': rol_nombre,
            'archivo': archivo_nombre,
            'tamano_kb': f"{tamano_kb:.1f} KB",
            'parrafos': num_parrafos,
            'tablas': num_tablas,
            'placeholders_imagenes': num_placeholders
        })
        print(f"    -> OK: {archivo_nombre} ({tamano_kb:.1f} KB, {num_parrafos} párrafos, {num_placeholders} recuadros para capturas)\n")

    print("================================================================================")
    print(" RESUMEN CONSOLIDADO DE DOCUMENTOS GENERADOS")
    print("================================================================================")
    print(f"{'Rol':<24} | {'Archivo Word (.docx)':<36} | {'Tamaño':<9} | {'Capturas'}")
    print("-" * 80)
    for r in resumen:
        print(f"{r['rol']:<24} | {r['archivo']:<36} | {r['tamano_kb']:<9} | {r['placeholders_imagenes']} recuadros")
    print("================================================================================")
    print(f"\nTodos los archivos están disponibles en:\n{output_dir}\n")

if __name__ == "__main__":
    main()

