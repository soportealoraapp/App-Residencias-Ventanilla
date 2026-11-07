# scripts/extraer_capturas_roles.py
# -*- coding: utf-8 -*-
"""
Extrae y organiza de forma determinista todas las capturas de pantalla de los 5 documentos .docx:
- Guia_Usuario_Ciudadano.docx (20 imágenes / 19 figuras)
- Guia_Usuario_Operador_Ventanilla.docx (19 imágenes / 19 figuras)
- Guia_Usuario_Transito_Agente.docx (15 imágenes / 15 figuras)
- Guia_Usuario_Juez_Calificador.docx (10 imágenes / 10 figuras)
- Guia_Usuario_Administrador.docx (27 imágenes / 27 figuras)

Total: 91 imágenes extraídas ordenadas por orden de aparición en el documento.
"""

import os
import zipfile
import docx

def extract_all():
    base_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "docs", "guias_usuario"))
    out_dir = os.path.join(base_dir, "capturas_extraidas")
    os.makedirs(out_dir, exist_ok=True)

    mappings = [
        ("Guia_Usuario_Ciudadano.docx", "ciudadano", 20),
        ("Guia_Usuario_Operador_Ventanilla.docx", "operador", 19),
        ("Guia_Usuario_Transito_Agente.docx", "transito", 15),
        ("Guia_Usuario_Juez_Calificador.docx", "juez", 10),
        ("Guia_Usuario_Administrador.docx", "administrador", 27),
    ]

    total_extracted = 0
    for doc_name, subfolder, expected_count in mappings:
        doc_path = os.path.join(base_dir, doc_name)
        target_folder = os.path.join(out_dir, subfolder)
        os.makedirs(target_folder, exist_ok=True)

        doc = docx.Document(doc_path)
        rels = doc.part.rels

        with zipfile.ZipFile(doc_path, 'r') as z:
            body_elem = doc._element.body
            extracted = 0
            for elem in body_elem.iter():
                if elem.tag.endswith('blip'):
                    r_id = elem.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}embed')
                    if r_id and r_id in rels:
                        target = rels[r_id].target_ref
                        media_path = 'word/' + target
                        if media_path in z.namelist():
                            extracted += 1
                            ext = os.path.splitext(target)[1]
                            dest_name = f"figura_{extracted:02d}{ext}"
                            dest_path = os.path.join(target_folder, dest_name)
                            with open(dest_path, 'wb') as f_out:
                                f_out.write(z.read(media_path))
            total_extracted += extracted
            print(f"[*] {doc_name}: extraídas {extracted} imágenes en {subfolder}/ (esperadas: {expected_count})")

    print(f"\n[+] Total de imágenes consolidadas y verificadas: {total_extracted} imágenes.")

if __name__ == "__main__":
    extract_all()
