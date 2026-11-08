# Arma el informe de Nova Salud con la tabla de contenido del Examen Parcial II.
#
# Reutiliza el contenido del informe anterior (Informe_NovaSolSalud_Grupo20.docx):
# reordena sus secciones según la nueva tabla de contenido, cambia el nombre del
# sistema, actualiza todas sus imágenes con las versiones vigentes de Docs/diagramas
# y Docs/capturas, y agrega las secciones nuevas (requisitos funcionales y no
# funcionales, implementación de la arquitectura del sistema y de los subsistemas,
# pruebas de caja negra y publicación del código).
#
# Uso: python armar_informe.py   →  Docs/Informe_NovaSalud_Grupo20.docx
# Después se actualiza el índice y se exporta el PDF con LibreOffice (indice_y_pdf.py).
import copy
import json
import re
import struct
import zipfile
from pathlib import Path

from lxml import etree

DOCS = Path(__file__).resolve().parents[1]
ORIGEN = DOCS / 'Informe_NovaSolSalud_Grupo20.docx'
SALIDA = DOCS / 'Informe_NovaSalud_Grupo20.docx'
PRUEBAS = DOCS / 'pruebas' / 'resultados.json'

NS = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
      'wp': 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing',
      'a': 'http://schemas.openxmlformats.org/drawingml/2006/main',
      'r': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
      'rel': 'http://schemas.openxmlformats.org/package/2006/relationships'}
W = '{%s}' % NS['w']
REL_IMG = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image'

# Cambios de nombre en todo el texto (orden importante: lo más específico primero)
RENOMBRES = [
    ('https://novasol-salud.vercel.app', 'https://nova-salud-clinica.vercel.app'),
    ('novasol-salud.vercel.app', 'nova-salud-clinica.vercel.app'),
    ('github.com/Kato-001/novasol-salud', 'github.com/Kato-001/nova-salud'),
    ('novasol-salud', 'nova-salud'), ('novasol_salud', 'nova_salud'),
    ('NovaSol_Salud.qea', 'Nova_Salud.qea'), ('NovaSol Salud', 'Nova Salud'), ('NovaSol', 'Nova Salud'),
    ('Octubre de 2026', 'Noviembre de 2026'),
]

paquete = zipfile.ZipFile(ORIGEN)
archivos = {n: paquete.read(n) for n in paquete.namelist()}
doc = etree.fromstring(archivos['word/document.xml'])
body = doc.find(W + 'body')
K = list(body)
rels = etree.fromstring(archivos['word/_rels/document.xml.rels'])
ruta_rel = {r.get('Id'): r.get('Target') for r in rels}


# ------------------------------------------------------------ Utilidades
def texto(e):
    return ''.join(e.itertext())


def nivel(e, n=None, t=None):
    """Cambia el nivel de título (Heading n) y, si se indica, su texto."""
    e = copy.deepcopy(e)
    st = e.find(f'{W}pPr/{W}pStyle')
    if n is not None:
        st.set(W + 'val', f'Heading{n}')
    if t is not None:
        ts = e.findall(f'.//{W}t')
        ts[0].text = t
        for x in ts[1:]:
            x.getparent().getparent().remove(x.getparent())
    return e


def bajar(bloque, pasos=1):
    """Copia un bloque y baja sus títulos 'pasos' niveles (Heading2 → Heading3…)."""
    salida = []
    for e in bloque:
        e = copy.deepcopy(e)
        st = e.find(f'{W}pPr/{W}pStyle')
        if st is not None and st.get(W + 'val', '').startswith('Heading'):
            st.set(W + 'val', f'Heading{min(6, int(st.get(W + "val")[7:]) + pasos)}')
        salida.append(e)
    return salida


def R(a, b):
    return [copy.deepcopy(e) for e in K[a:b]]


def plantilla(i):
    return copy.deepcopy(K[i])


def con_texto(e, partes):
    """Reemplaza los runs de un párrafo por 'partes' [(texto, negrita, cursiva)]."""
    rpr0 = e.find(f'{W}r/{W}rPr')
    for r in e.findall(W + 'r'):
        e.remove(r)
    for b in e.findall(W + 'bookmarkStart') + e.findall(W + 'bookmarkEnd'):
        e.remove(b)
    for t, neg, cur in partes:
        r = etree.SubElement(e, W + 'r')
        rpr = copy.deepcopy(rpr0) if rpr0 is not None else etree.Element(W + 'rPr')
        for tag in ('b', 'bCs', 'i', 'iCs'):
            for x in rpr.findall(W + tag):
                rpr.remove(x)
        if neg:
            etree.SubElement(rpr, W + 'b'); etree.SubElement(rpr, W + 'bCs')
        if cur:
            etree.SubElement(rpr, W + 'i'); etree.SubElement(rpr, W + 'iCs')
        r.append(rpr)
        x = etree.SubElement(r, W + 't'); x.text = t
        x.set('{http://www.w3.org/XML/1998/namespace}space', 'preserve')
    return e


def partes_md(t):
    """'**negrita**' y '*cursiva*' a partes de con_texto()."""
    out = []
    for p in re.split(r'(\*\*[^*]+\*\*|\*[^*]+\*)', t):
        if p.startswith('**'):
            out.append((p[2:-2], True, False))
        elif p.startswith('*') and len(p) > 1:
            out.append((p[1:-1], False, True))
        elif p:
            out.append((p, False, False))
    return out


def H(n, t):
    return con_texto(nivel(K[{1: 18, 2: 19, 3: 47}.get(n, 47)], n), [(t, False, False)])


def P(t):
    return con_texto(plantilla(20), partes_md(t))


def VACIO():
    return plantilla(74)


# ------------------------------------------------------------- Tablas
fila_enc = K[73].findall(W + 'tr')[0]
fila_dat = K[73].findall(W + 'tr')[1]


def _celda(base, ancho, t, negrita=None, fondo=None):
    c = copy.deepcopy(base)
    c.find(f'{W}tcPr/{W}tcW').set(W + 'w', str(ancho))
    if fondo:
        sh = c.find(f'{W}tcPr/{W}shd')
        if sh is None:
            sh = etree.Element(W + 'shd'); c.find(W + 'tcPr').insert(2, sh)
        sh.set(W + 'val', 'clear'); sh.set(W + 'color', 'auto'); sh.set(W + 'fill', fondo)
    p0 = c.find(W + 'p')
    for p in c.findall(W + 'p')[1:]:
        c.remove(p)
    lineas = t.split('\n') if isinstance(t, str) else t
    for k, linea in enumerate(lineas):
        p = p0 if k == 0 else copy.deepcopy(p0)
        if k:
            c.append(p)
        rpr = copy.deepcopy(p.find(f'{W}r/{W}rPr'))
        for r in p.findall(W + 'r'):
            p.remove(r)
        partes = partes_md(linea) or [('', False, False)]
        for s, neg, cur in partes:
            r = etree.SubElement(p, W + 'r'); rr = copy.deepcopy(rpr); r.append(rr)
            if neg or negrita:
                if rr.find(W + 'b') is None:
                    etree.SubElement(rr, W + 'b'); etree.SubElement(rr, W + 'bCs')
            if cur:
                etree.SubElement(rr, W + 'i')
            x = etree.SubElement(r, W + 't'); x.text = s
            x.set('{http://www.w3.org/XML/1998/namespace}space', 'preserve')
    return c


def _tabla(filas_xml, anchos):
    t = copy.deepcopy(K[73])
    for tr in t.findall(W + 'tr'):
        t.remove(tr)
    grid = t.find(W + 'tblGrid')
    for g in list(grid):
        grid.remove(g)
    for a in anchos:
        etree.SubElement(grid, W + 'gridCol').set(W + 'w', str(a))
    for f in filas_xml:
        t.append(f)
    return t


def titulo_tabla(t):
    return [con_texto(plantilla(71), [('Tabla 0', True, False)]), con_texto(plantilla(72), [(t, False, True)])]


def TABLA(titulo, encabezado, filas, anchos):
    xml = []
    tr = copy.deepcopy(fila_enc)
    base = tr.findall(W + 'tc')[0]
    for c in tr.findall(W + 'tc'):
        tr.remove(c)
    for t, a in zip(encabezado, anchos):
        tr.append(_celda(base, a, t))
    xml.append(tr)
    for f in filas:
        tr = copy.deepcopy(fila_dat)
        base = tr.findall(W + 'tc')[0]
        for c in tr.findall(W + 'tc'):
            tr.remove(c)
        for t, a in zip(f, anchos):
            tr.append(_celda(base, a, t))
        xml.append(tr)
    return titulo_tabla(titulo) + [_tabla(xml, anchos), VACIO()]


def FICHA(titulo, pares, anchos=(2300, 7060)):
    xml = []
    for k, v in pares:
        tr = copy.deepcopy(fila_dat)
        base = tr.findall(W + 'tc')[0]
        for c in tr.findall(W + 'tc'):
            tr.remove(c)
        tr.append(_celda(base, anchos[0], k, negrita=True, fondo='DEEAF6'))
        tr.append(_celda(base, anchos[1], v))
        xml.append(tr)
    return titulo_tabla(titulo) + [_tabla(xml, list(anchos)), VACIO()]


# ------------------------------------------------------------ Imágenes
MAX_CX, MAX_CY = 5943600, 7000000          # 6.5" de ancho y ~7.6" de alto útiles
nuevas_media = {}
siguiente_rid = [max(int(r.get('Id')[3:]) for r in rels) + 1]
siguiente_doc = [9000]


def tam_png(datos):
    return struct.unpack('>II', datos[16:24])


def fuente(nombre):
    for carpeta in ('diagramas', 'capturas', ''):
        f = DOCS / carpeta / nombre
        if f.is_file():
            return f
    return None


def ajustar(drawing, w, h, cx=None):
    cx = cx or int(drawing.find('.//wp:extent', NS).get('cx'))
    cx = min(cx, MAX_CX)
    cy = int(cx * h / w)
    if cy > MAX_CY:
        cx, cy = int(cx * MAX_CY / cy), MAX_CY
    for e in (drawing.find('.//wp:extent', NS), drawing.find('.//a:ext', NS)):
        e.set('cx', str(cx)); e.set('cy', str(cy))


def FIGURA(archivo, titulo, nota='Elaboración propia en notación UML con el estilo de Enterprise Architect.', ancho=MAX_CX):
    f = fuente(archivo)
    datos = f.read_bytes()
    rid = f'rId{siguiente_rid[0]}'; siguiente_rid[0] += 1
    destino = f'media/nova_{rid}.png'
    nuevas_media['word/' + destino] = datos
    etree.SubElement(rels, f'{{{NS["rel"]}}}Relationship', Id=rid, Type=REL_IMG, Target=destino)
    img = plantilla(2173)
    img.find('.//a:blip', NS).set(f'{{{NS["r"]}}}embed', rid)
    for d in (img.find('.//wp:docPr', NS), img.find('.//{http://schemas.openxmlformats.org/drawingml/2006/picture}cNvPr')):
        d.set('id', str(siguiente_doc[0])); d.set('name', archivo); d.set('descr', titulo)
    siguiente_doc[0] += 1
    w, h = tam_png(datos)
    ajustar(img, w, h, min(ancho, int(w * 9525)))
    salida = [con_texto(plantilla(2171), [('Figura 0', True, False)]), con_texto(plantilla(2172), [(titulo, False, True)]), img]
    if nota:
        salida.append(con_texto(plantilla(2174), [('Nota. ', False, True), (nota, False, False)]))
    return salida


# =============================================================== Contenido
pr = json.loads(PRUEBAS.read_text(encoding='utf-8'))
C = []
add = C.extend

# ---- Carátula e índice
add(R(0, 18))

# ---- 1) PERFIL
add([K[18]])
add(R(19, 23))
add([nivel(K[23], 3, '1.1.1 Antecedente')] + R(24, 37))
add([nivel(K[37], 3, '1.1.2 Justificación')] + R(38, 44))
add([nivel(K[57], 2, '1.2 Objetivos'), nivel(K[58], 3, 'Objetivo general'), copy.deepcopy(K[59]),
     nivel(K[60], 3, 'Objetivos específicos')] + R(61, 69))
add([nivel(K[44], 2, '1.3 Descripción del problema')] + R(45, 47) + [nivel(K[47], 3, '1.3.1 Desafíos identificados')]
    + R(48, 54) + [nivel(K[54], 3, '1.3.2 Formulación del problema')] + R(55, 57))
add([nivel(K[69], 2, '1.4 Alcance')] + R(70, 77))

add([H(3, '1.4.1 Requisitos funcionales'),
     P('Los requisitos funcionales describen lo que el sistema debe hacer. Provienen del enunciado del proyecto y se cumplen con los casos de uso indicados.')])
add(TABLA('Requisitos funcionales', ['Código', 'Requisito funcional', 'Descripción', 'CU'], [
    ['RF01', 'Inicio de sesión y control de acceso', 'Validar usuario y contraseña (password_hash/password_verify), iniciar sesión segura, mostrar el menú según el rol, cerrar sesión, impedir el acceso sin sesión y recuperar la contraseña con un código enviado al correo.', 'CU1–CU3'],
    ['RF02', 'Gestión de pacientes', 'Registrar, listar, buscar por CI, nombre o apellido, modificar y desactivar pacientes sin perder su historial. El CI no se repite.', 'CU7, CU8'],
    ['RF03', 'Gestión de médicos', 'Registrar médicos con una o varias especialidades, buscarlos, modificarlos, consultar su agenda y activarlos o desactivarlos.', 'CU5'],
    ['RF04', 'Gestión de especialidades', 'Administrar especialidades con nombre normalizado único, descripción y estado.', 'CU4'],
    ['RF05', 'Gestión de horarios de atención', 'Definir los horarios de cada médico y la duración de la cita; calcular los horarios disponibles; bloquear feriados y permisos.', 'CU6, CU9'],
    ['RF06', 'Registro de citas', 'Programar citas eligiendo paciente, especialidad, médico, fecha y una hora disponible; generar un código único.', 'CU10'],
    ['RF07', 'Prevención de citas duplicadas', 'Impedir que un médico tenga citas superpuestas o fuera de su horario, verificando en el servidor y en la base de datos.', 'CU10, CU11'],
    ['RF08', 'Reprogramación de citas', 'Cambiar la fecha y hora de una cita programada o confirmada, verificando la disponibilidad.', 'CU11'],
    ['RF09', 'Cancelación de citas', 'Cancelar con motivo, sin eliminar la cita; la cita cancelada deja de ocupar el horario.', 'CU12'],
    ['RF10', 'Control de asistencia', 'Controlar los estados programada, confirmada, atendida, cancelada e inasistencia y sus transiciones válidas.', 'CU13, CU15'],
    ['RF11', 'Consulta de agenda médica', 'Consultar la agenda de un médico por fecha, con filtros por médico, fecha y estado.', 'CU14'],
    ['RF12', 'Historial de citas por paciente', 'Ver todas las citas de un paciente, incluidas las pasadas y canceladas, con médico, especialidad, estado y fecha de registro.', 'CU7, CU16, CU19'],
    ['RF13', 'Reportes', 'Reportes de citas por fecha, médico, especialidad y estado, pacientes registrados, resumen del período y agenda diaria, con totales y exportación.', 'CU20'],
], [900, 2100, 5160, 1200]))
add([H(3, '1.4.2 Requisitos no funcionales'),
     P('Los requisitos no funcionales describen cualidades que el sistema debe cumplir además de sus funciones.')])
add(TABLA('Requisitos no funcionales', ['Código', 'Requisito', 'Cómo se cumple'], [
    ['RNF01', 'Seguridad', 'Contraseñas con password_hash(); consultas parametrizadas con PDO; protección CSRF y XSS; sesiones seguras; permisos por rol verificados en el servidor; bitácora de auditoría; bloqueo tras 5 intentos fallidos.'],
    ['RNF02', 'Usabilidad', 'Interfaz en español, asistente paso a paso para programar citas, mensajes claros, validación inmediata en el navegador, modo oscuro.'],
    ['RNF03', 'Rendimiento', 'Respuesta menor a un segundo; índices en las columnas de búsqueda; servidor y base de datos en la misma región (São Paulo).'],
    ['RNF04', 'Disponibilidad', 'Publicado en la nube (Vercel y Supabase), accesible las 24 horas desde cualquier navegador.'],
    ['RNF05', 'Integridad de datos', 'Base de datos normalizada; claves primarias y foráneas; restricciones CHECK, UNIQUE y EXCLUDE; triggers con las reglas de negocio.'],
    ['RNF06', 'Portabilidad', 'Funciona en Vercel, en Apache (XAMPP o Laragon) y con el servidor integrado de PHP, en Windows o Linux.'],
    ['RNF07', 'Adaptabilidad', 'Diseño adaptable a computadoras, tabletas y celulares.'],
    ['RNF08', 'Mantenibilidad', 'Arquitectura MVC con capa de servicios, código comentado y organizado por paquetes, publicado en GitHub.'],
], [1000, 1900, 6460]))

# ---- 2) MARCO TEÓRICO
add([nivel(K[261], 1, '2. Marco teórico')] + R(262, 273))

# ---- 3) MODELO DE NEGOCIO
add([nivel(K[250], 1, '3. Modelo de negocio')] + R(251, 261))
add([nivel(K[244], 2, '3.1 Modelo de dominio')] + R(245, 250))
add([nivel(K[374], 2, '3.2 Diagrama de actividad')] + R(375, 388))

# ---- 4) FT CAPTURA DE REQUISITOS
cap = R(388, 843)
for e in cap:
    t = texto(e)
    for viejo, nuevo in [('Capítulo 2. Flujo de trabajo: captura de requisitos', '4. FT Captura de requisitos'),
                         ('2.1 Identificar actores y casos de uso', '4.1 Encontrar actores y casos de uso'),
                         ('2.2 Priorizar los casos de uso', '4.2 Priorizar casos de uso'),
                         ('2.3 Detallar un caso de uso', '4.3 Detallar un caso de uso'),
                         ('2.4 Prototipar interfaz de usuario', '4.4 Prototipar interfaz de usuario'),
                         ('2.5 Estructurar modelo de casos de uso', '4.5 Estructurar modelo de casos de uso')]:
        if t == viejo:
            con_texto(e, [(nuevo, False, False)])
add(cap)

# ---- 5) FT ANÁLISIS
ana = R(843, 1207)
for e in ana:
    t = texto(e)
    m = {'Capítulo 3. Flujo de trabajo: análisis': '5. FT Análisis',
         '3.1 Análisis de la arquitectura': '5.1 Análisis de arquitectura',
         '3.1.1 Identificar paquetes': '5.1.1 Identificar paquetes',
         '3.1.2 Relacionar paquetes y casos de uso': '5.1.2 Relacionar paquetes y casos de uso',
         '3.1.3 Vista de paquetes': '5.1.3 Vista de paquetes',
         '3.2 Analizar un caso de uso': '5.2 Analizar casos de uso',
         '3.3 Análisis de clase': '5.3 Análisis de clases',
         '3.4 Análisis de paquetes': '5.4 Análisis de paquete'}.get(t)
    if m and e.find(f'{W}pPr/{W}pStyle') is not None and e.find(f'{W}pPr/{W}pStyle').get(W + 'val').startswith('Heading'):
        con_texto(e, [(m, False, False)])
add(ana)

# ---- 6) FT DISEÑO
add([nivel(K[1207], 1, '6. FT Diseño'), nivel(K[1208], 2, '6.1 Diseño de arquitectura (física y lógica)')] + R(1209, 1210)
    + [nivel(K[1210], 3, '6.1.1 Diseño físico de la arquitectura (diagrama de despliegue)')] + R(1211, 1220)
    + [nivel(K[1220], 3, '6.1.2 Diseño lógico de la arquitectura (diagrama de paquetes en capas)')] + R(1221, 1229))
add([nivel(K[2065], 2, '6.2 Diseñar caso de uso (diagrama de secuencia)')] + R(2066, 2153))
datos = bajar(K[1229:2065], 1)
datos[0] = nivel(datos[0], 2, '6.3 Diseño de datos (lógico y físico)')
for e in datos:
    t = texto(e)
    if t == '5.1 Diseño de datos lógico':
        con_texto(e, [('6.3.1 Diseño de datos lógico', False, False)])
    elif t == '5.2 Diseño de datos físico':
        con_texto(e, [('6.3.2 Diseño de datos físico', False, False)])
add(datos)

# ---- 7) FT IMPLEMENTACIÓN
add([H(1, '7. FT Implementación'),
     P('El flujo de trabajo de implementación convierte el modelo de diseño en código fuente, scripts y archivos ejecutables. '
       'Se describen las herramientas de desarrollo y la implementación de la arquitectura del sistema y de cada subsistema '
       'mediante diagramas de componentes.'),
     H(2, '7.1 Herramientas de desarrollo de la aplicación web'),
     P('A continuación se describen las características del lenguaje de programación, la base de datos, los sistemas operativos '
       'y las demás herramientas utilizadas en el trabajo.')])
add(R(2155, 2168))
add([nivel(K[187], 3, 'Herramientas de software')] + R(188, 193) + [nivel(K[193], 3, 'Herramientas de hardware')] + R(194, 199))

add([H(2, '7.2 Implementación de la arquitectura del sistema'),
     P('El diagrama de componentes representa el aspecto estático de la implementación: los artefactos físicos del sistema '
       '(archivos fuente, scripts, librerías, tablas y reportes) y sus dependencias. El componente principal **«main» Sistema Web '
       'Nova Salud** (api/index.php) recibe todas las peticiones y depende de los paquetes P1 a P6 y del Portal del paciente.'),
     P('Los estereotipos utilizados son: **«main»** el programa principal; **«file»** archivos de configuración y de código; '
       '**«library»** librerías (PDO, el envío de correos y los recursos del navegador); **«engine»** el motor de base de datos; '
       '**«script»** los scripts SQL; **«table»** las tablas; **«PDF»** y **«Excel»** los reportes impreso y exportado.')])
add(FIGURA('61_impl_sistema.png', 'Diagrama de componentes de la implementación de la arquitectura del sistema'))
add([P('El código fuente se organiza en las siguientes carpetas:')] + R(2175, 2190) + R(2191, 2192))

add([H(2, '7.3 Implementación de la arquitectura de los subsistemas'),
     P('Cada paquete se implementa como un subsistema con tres capas de componentes: la interfaz (**«form»** formularios y '
       '**«report»** pantallas de consulta, en Frontend/views), la lógica (**«class»** controladores en Bakend/app) y los datos '
       '(**«table»** tablas de PostgreSQL). Las dependencias muestran qué clase atiende cada pantalla y qué tablas utiliza.')])
for clave, nombre, desc in [
    ('p1', 'P1 Seguridad y acceso', 'Inicio de sesión, recuperación de contraseña, usuarios, roles y permisos, configuración y bitácora.'),
    ('p2', 'P2 Gestión médica', 'Especialidades, médicos con sus especialidades y horarios de atención con bloqueos de agenda.'),
    ('p3', 'P3 Gestión de pacientes', 'Registro, búsqueda, ficha e historia clínica de los pacientes y autoregistro en el portal.'),
    ('p4', 'P4 Gestión de citas', 'Programación con disponibilidad real, reprogramación, cancelación, asistencia y agenda médica.'),
    ('p5', 'P5 Atención médica', 'Registro de la consulta con tratamientos y estudios, resultados y catálogo de estudios.'),
    ('p6', 'P6 Reportes', 'Reportes por fecha, médico, especialidad y estado, con exportación a CSV e impresión a PDF.'),
    ('portal', 'Portal del paciente', 'Reserva, consulta y cancelación de citas, perfil, información médica e historial del paciente.'),
]:
    add([H(3, nombre), P(desc)])
    add(FIGURA(f'62_impl_{clave}.png', f'Diagrama de componentes del subsistema {nombre}'))

# ---- 8) FT PRUEBAS
add([H(1, '8. FT Pruebas'),
     H(2, '8.1 Estrategia de pruebas'),
     P('Se aplicaron **pruebas de caja negra** a los casos de uso: se prueba el sistema desde afuera, como lo haría el usuario, '
       'sin mirar el código. En cada prueba se define la **interfaz de usuario** utilizada, la **entrada** (datos que se envían), '
       'la **condición** que se quiere comprobar, la **salida** esperada y el **procedimiento** que se sigue.'),
     P(f'Las pruebas se ejecutaron el {pr["fecha"]} con el programa Docs/pruebas/pruebas_caja_negra.py, que envía las peticiones '
       'al sistema igual que un navegador, incluso saltándose la validación de JavaScript para comprobar la validación del servidor. '
       f'Resultado: **{sum(p["ok"] for p in pr["pruebas"])} de {len(pr["pruebas"])} pruebas correctas.**'),
     H(2, '8.2 Casos de prueba por caso de uso')])
nombres_cu = {'CU1': 'Iniciar sesión', 'CU2': 'Gestionar roles y permisos', 'CU3': 'Gestionar usuarios',
              'CU4': 'Gestionar especialidades', 'CU5': 'Gestionar médicos', 'CU6': 'Gestionar horarios',
              'CU7': 'Gestionar pacientes', 'CU8': 'Registrarse como paciente', 'CU9': 'Consultar disponibilidad',
              'CU10': 'Reservar ficha', 'CU11': 'Reprogramar cita', 'CU12': 'Cancelar cita', 'CU13': 'Registrar asistencia',
              'CU14': 'Consultar agenda médica', 'CU15': 'Registrar consulta médica', 'CU16': 'Gestionar historial clínico',
              'CU17': 'Registrar tratamiento', 'CU18': 'Gestionar estudios médicos', 'CU19': 'Consultar historial y resultados',
              'CU20': 'Generar reportes'}
cu_actual = None
for p in pr['pruebas']:
    if p['cu'] != cu_actual:
        cu_actual = p['cu']
        add([H(3, f'{cu_actual} {nombres_cu.get(cu_actual, "")}'.strip())])
    add(FICHA(f'Prueba {p["id"]}: {p["prueba"]}', [
        ('Caso de uso', p['cu']), ('Interfaz de usuario', p['interfaz']), ('Entrada', p['entrada']),
        ('Condición', p['condicion']), ('Salida esperada', p['esperada']), ('Procedimiento', p['procedimiento']),
        ('Resultado obtenido', p['obtenido']), ('Estado', '✔ Correcta' if p['ok'] else '✘ Falló')]))
add([H(2, '8.3 Interfaz de usuario en las pruebas')])
for img, t in [('03_login_error.png', 'Prueba P02: ingreso con contraseña incorrecta'),
               ('34_validacion_cliente.png', 'Prueba P15: datos inválidos marcados en el formulario'),
               ('46_cita_cancelada_historial.png', 'Prueba P24: cita cancelada con su historial conservado'),
               ('05_error_403.png', 'Pruebas P06, P27 y P33: acceso denegado según el rol')]:
    add(FIGURA(img, t, nota='Captura de pantalla del sistema Nova Salud.'))
add([H(2, '8.4 Resumen de resultados')])
add(TABLA('Resumen de las pruebas de caja negra', ['N.º', 'CU', 'Prueba', 'Resultado'],
          [[p['id'], p['cu'], p['prueba'], '✔ Correcta' if p['ok'] else '✘ Falló'] for p in pr['pruebas']], [800, 900, 5960, 1700]))
add([H(2, '8.5 Pruebas mínimas del enunciado')] + R(2270, 2275))

# ---- CONCLUSIÓN, RECOMENDACIÓN, BIBLIOGRAFÍA
add([nivel(K[2225], 1, 'Conclusión')] + R(2226, 2232))
add([nivel(K[2232], 1, 'Recomendación')] + R(2233, 2239))
add([nivel(K[2239], 1, 'Bibliografía')] + R(2240, 2256))

# ---- PUBLICACIÓN DEL CÓDIGO
add([H(1, 'Publicación del código fuente'),
     P('El código fuente organizado y comentado se publicó en GitHub. El sistema funciona en línea y puede probarse con las '
       'cuentas del Anexo A.')] + R(2258, 2262))
add(FIGURA('qr-repositorio.png', 'Código QR del repositorio en GitHub (https://github.com/Kato-001/nova-salud)', nota=None, ancho=1600000))
add(FIGURA('qr-demo.png', 'Código QR del sistema en línea (https://nova-salud-clinica.vercel.app)', nota=None, ancho=1600000))

# ---- ANEXOS
add([nivel(K[2256], 1, 'Anexos')])
add([nivel(K[2262], 2, 'Anexo A. Cuentas de acceso')] + R(2263, 2269))
add([nivel(K[2275], 2, 'Anexo B. Manual breve de uso')] + R(2276, 2281))
add([nivel(K[77], 2, 'Anexo C. Entrevista')] + R(78, 88))
ish = bajar(K[273:373], 1)
ish[0] = nivel(ish[0], 2, 'Anexo D. Método de Ishikawa')
add(ish)
add([H(2, 'Anexo E. Perfil ampliado del proyecto'),
     P('Este anexo amplía el perfil con los elementos del sistema, la tecnología, los costos, los beneficios y los posibles clientes.')])
add(bajar(K[88:186], 2) + bajar(K[199:244], 2))
add([nivel(K[2281], 2, 'Anexo F. Modelo en Enterprise Architect')] + R(2282, 2288))
add([nivel(K[2288], 2, 'Anexo G. Script de creación de las tablas')] + R(2289, 2617))

# Saltos de página: se quitan los saltos sueltos heredados del informe anterior y
# cada capítulo principal (Heading1) empieza en una página nueva
for i, e in enumerate(C):
    if i < 19:
        continue                                   # carátula, índice y "1. Perfil"
    for br in e.findall(f'.//{W}br'):
        if br.get(W + 'type') == 'page':
            br.getparent().remove(br)
    st = e.find(f'{W}pPr/{W}pStyle')
    if st is not None and st.get(W + 'val') == 'Heading1':
        ppr = e.find(W + 'pPr')
        if ppr.find(W + 'pageBreakBefore') is None:
            ppr.insert(1, etree.Element(W + 'pageBreakBefore'))

# ======================================================= Ensamblar el cuerpo
for e in list(body):
    body.remove(e)
for e in C:
    body.append(e)

# Nombre del sistema en todo el texto
for t in doc.iter(W + 't'):
    if t.text:
        for a, b in RENOMBRES:
            t.text = t.text.replace(a, b)

# Numeración correlativa de tablas y figuras (formato APA)
n = {'Tabla': 0, 'Figura': 0}
for p in body.iter(W + 'p'):
    m = re.fullmatch(r'(Tabla|Figura) \d+', texto(p).strip())
    if m:
        n[m.group(1)] += 1
        ts = p.findall(f'.//{W}t')
        ts[0].text = f'{m.group(1)} {n[m.group(1)]}'
        for x in ts[1:]:
            x.text = ''

# Imágenes existentes: versión vigente del diagrama o captura y proporción correcta
actualizadas = 0
for dr in body.iter(W + 'drawing'):
    nombre = dr.find('.//wp:docPr', NS).get('name')
    rid = dr.find('.//a:blip', NS).get(f'{{{NS["r"]}}}embed')
    f = fuente(nombre) if nombre else None
    if f and rid in ruta_rel and not ruta_rel[rid].startswith('media/nova_'):
        datos = f.read_bytes()
        archivos['word/' + ruta_rel[rid]] = datos
        ajustar(dr, *tam_png(datos))
        actualizadas += 1

# Códigos QR de la carátula (imágenes sin nombre de archivo): se reemplazan por los vigentes
for dr in body.iter(W + 'drawing'):
    nombre = dr.find('.//wp:docPr', NS).get('name')
    rid = dr.find('.//a:blip', NS).get(f'{{{NS["r"]}}}embed')
    if nombre in ('qr', 'Imagen2'):
        archivos['word/' + ruta_rel[rid]] = (DOCS / ('qr-repositorio.png' if nombre == 'qr' else 'qr-demo.png')).read_bytes()
        actualizadas += 1

# Nombre del sistema también en las descripciones de las imágenes (texto alternativo)
for e in doc.iter():
    for atributo in ('descr', 'name', 'title'):
        v = e.get(atributo)
        if v and 'NovaSol' in v:
            for a, b in RENOMBRES:
                v = v.replace(a, b)
            e.set(atributo, v)

# Quitar imágenes que ya no se usan
usados = set(re.findall(r'r:embed="(rId\d+)"', etree.tostring(doc, encoding='unicode')))
for r in list(rels):
    if r.get('Type') == REL_IMG and r.get('Id') not in usados:
        archivos.pop('word/' + r.get('Target'), None)
        rels.remove(r)

archivos['word/document.xml'] = etree.tostring(doc, xml_declaration=True, encoding='UTF-8', standalone=True)
archivos['word/_rels/document.xml.rels'] = etree.tostring(rels, xml_declaration=True, encoding='UTF-8', standalone=True)
archivos.update(nuevas_media)
for nombre in list(archivos):
    if re.match(r'word/(header|footer)\d*\.xml|docProps/(core|app)\.xml', nombre):
        s = archivos[nombre].decode('utf-8')
        for a, b in RENOMBRES:
            s = s.replace(a, b)
        archivos[nombre] = s.encode('utf-8')

with zipfile.ZipFile(SALIDA, 'w', zipfile.ZIP_DEFLATED) as z:
    for nombre in ['[Content_Types].xml'] + [x for x in archivos if x != '[Content_Types].xml']:
        z.writestr(nombre, archivos[nombre])

print(f'{SALIDA.name}: {len(C)} bloques · {n["Figura"]} figuras · {n["Tabla"]} tablas · '
      f'{actualizadas} imágenes actualizadas · {len(nuevas_media)} nuevas')
