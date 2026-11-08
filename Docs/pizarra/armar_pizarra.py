# Transcribe a Word lo que la ingeniera escribió en la pizarra (Doc. 2do Ex. Parcial).
# Uso: python armar_pizarra.py  →  Docs/Pizarra_Doc_2do_Ex_Parcial.docx
from pathlib import Path

from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.shared import Cm, Pt, RGBColor

AQUI = Path(__file__).resolve().parent
FOTOS = AQUI.parents[1] / 'Documentos de ejemplos'
SALIDA = AQUI.parent / 'Pizarra_Doc_2do_Ex_Parcial.docx'
AZUL = RGBColor(0x1F, 0x4E, 0x79)

d = Document()
for s in d.sections:
    s.top_margin = s.bottom_margin = Cm(2.5)
    s.left_margin = s.right_margin = Cm(2.5)
d.styles['Normal'].font.name = 'Calibri'
d.styles['Normal'].font.size = Pt(11)


def titulo(t, n):
    h = d.add_heading(t, n)
    for r in h.runs:
        r.font.color.rgb = AZUL


def parrafo(t, negrita=False, cursiva=False, sangria=0):
    p = d.add_paragraph()
    p.paragraph_format.left_indent = Cm(sangria)
    r = p.add_run(t)
    r.bold, r.italic = negrita, cursiva
    return p


def vineta(t, nivel=0):
    p = d.add_paragraph(t, style='List Bullet 2' if nivel else 'List Bullet')
    return p


def figura(archivo, pie, ancho=16):
    d.add_picture(str(archivo), width=Cm(ancho))
    d.paragraphs[-1].alignment = WD_ALIGN_PARAGRAPH.CENTER
    p = d.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run(pie)
    r.italic = True
    r.font.size = Pt(9)


# ------------------------------------------------------------------- Portada
t = d.add_paragraph()
t.alignment = WD_ALIGN_PARAGRAPH.CENTER
r = t.add_run('Doc. 2do Ex. Parcial')
r.bold, r.font.size, r.font.color.rgb = True, Pt(20), AZUL
t = d.add_paragraph()
t.alignment = WD_ALIGN_PARAGRAPH.CENTER
t.add_run('Transcripción de lo explicado en la pizarra por la Ing. Angelica Garzon Cuellar\n'
          'Ingeniería en Requisitos y Modelado · Grupo # 20').italic = True

# ------------------------------------------------------- 7) F.T. Implementación
titulo('7) F.T. Implementación', 1)

titulo('7.1) Herramientas de desarrollo App. Web', 2)
vineta('Características del L.P. (lenguaje de programación), S.O. (sistema operativo), BD (base de datos) '
       'y todas las herramientas de desarrollo que utilizó en su trabajo.')
parrafo('Nota al margen en la pizarra: Análisis · Diseño · trazabilidad (con el símbolo de paquete).', cursiva=True)

titulo('7.2) Implementación de la Arquitectura del Sistema', 2)
vineta('Diagrama de Componente.')

titulo('7.3) Implementación de la Arq. del Subsistemas', 2)
vineta('Diagrama de Componente por cada paquete.')

# ------------------------------------------------------------ 8) F.T. Pruebas
titulo('8) F.T. Pruebas', 1)
vineta('Probar Casos de Uso → Caja Negra:')
for x in ('Interfaz Usr. (interfaz de usuario)', 'Entrada', 'Condición', 'Salida'):
    vineta(x, 1)
vineta('→ Procedimientos.')

titulo('Cierre del documento', 1)
for x in ('CONCLUSIÓN', 'RECOMENDACIÓN', 'BIBLIOGRAFÍA',
          'Publicación Código (Git / GitHub): URL + QR', 'ANEXOS'):
    vineta(x)

# ------------------------------------------------------ Diagrama de componente
titulo('Diagrama de Componente', 1)
vineta('Aspecto estático.')
vineta('Conjunto de notaciones que permiten: visualizar, documentar, especificar y construir los artefactos del sistema.')
vineta('Diag = Elementos + Relaciones.')
vineta('Componente: se dibuja como un rectángulo con el ícono de componente, con el símbolo de componente o '
       'como un rectángulo con el estereotipo «componente».')
vineta('Paquete: carpeta con el nombre (Nombre.Ext).')
vineta('Notaciones para integrar los elementos + relaciones.')
vineta('* Un componente representa un recurso físico: source, script, objeto, ejecutable, interface, API, paneles, …')
figura(AQUI / 'pz_notacion.png', 'Notación de componente y paquete', 13)

# ------------------------------------------- Implementación de Arq. Sistema
titulo('Implementación de Arq. Sistema (ejemplo)', 1)
parrafo('Elementos del ejemplo dibujado en la pizarra:')
vineta('Paquetes P1 Nomb1, P2 Nomb2, …, Pn Nombn, de los que depende el componente principal.')
vineta('«main» Sistema Web (Gestión de Consultas Médicas): componente principal en el centro.')
vineta('«File» NombF y «library» NombL1 a los lados del componente principal.')
vineta('Paquete B.D: «engine» PostgreSQL, «script» BDClinica.sql y las tablas «table» THistorial, TPaciente, '
       'TMedico, TEspMed, THorario y TFicha.')
vineta('Paquete Reporte: «PDF» y «MS Excel» con los archivos «file» RHorarioFichaGst, RPacientes, RHistorial y RConsultas.')
vineta('Las relaciones son dependencias (flechas punteadas).')
figura(AQUI / 'pz_sistema.png', 'Ejemplo de la pizarra: implementación de la arquitectura del sistema', 16.5)

# ----------------------------------------- Implementación de Arq. SubSistema
titulo('Implementación Arq. SubSistema (ejemplo)', 1)
parrafo('P1: Gestionar Consultas Esp. Médicas', negrita=True)
parrafo('El subsistema se dibuja en tres filas separadas por líneas, con dependencias de arriba hacia abajo:')
vineta('Interfaz: «form» FRegFicha, «form» FConsultaMExt, «Reporte» RConsultaExt, «form» FHistorialExt.')
vineta('Clases: «class» GFichaExt, «class» GConsultaExt, «class» RConsultaExt y otra «class» (sin nombre en la pizarra).')
vineta('Tablas: «table» TFichaExt, «table» TConsultaExt, «table» TPaciente, «table» THistorial.')
parrafo('P2. SubSistema: ________', sangria=0.5)
parrafo('P3. SubSistema: ________', sangria=0.5)
parrafo('(Se repite el mismo diagrama para cada paquete.)', cursiva=True)
figura(AQUI / 'pz_subsistema.png', 'Ejemplo de la pizarra: implementación de la arquitectura del subsistema P1', 15)

# ------------------------------------------------------------- Fotos originales
fotos = sorted(FOTOS.glob('photo_*.jpg')) if FOTOS.is_dir() else []
if fotos:
    d.add_page_break()
    titulo('Anexo: fotos de la pizarra', 1)
    from PIL import Image
    for i, f in enumerate(fotos, 1):
        ancho, alto = Image.open(f).size
        figura(f, f'Foto {i} de la pizarra', 8.5 if alto > ancho else 15)   # las fotos verticales, más angostas

d.save(SALIDA)
print('Documento generado:', SALIDA.name)
