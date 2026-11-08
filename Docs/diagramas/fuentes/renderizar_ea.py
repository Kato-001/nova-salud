# Renderiza los diagramas .puml con la apariencia de Enterprise Architect.
# No modifica los .puml: copia cada uno a una carpeta temporal, cambia el estilo por
# estilo_ea.iuml y convierte el título en el marco del diagrama con la etiqueta de tipo
# de EA ("uc", "class", "sd"…). Las imágenes .png se guardan en esta carpeta.
#
# Uso: python renderizar_ea.py [archivo.puml ...]
# Requiere Java, plantuml.jar (~/.plantuml/plantuml.jar) y Graphviz.
import os
import re
import subprocess
import sys
import tempfile
from pathlib import Path

AQUI = Path(__file__).resolve().parent
JAR = Path.home() / '.plantuml' / 'plantuml.jar'
DOT = r'C:\Program Files\Graphviz\bin\dot.exe'

# Etiqueta del marco según el tipo de diagrama (prefijo del nombre del archivo)
TIPOS = [
    (r'^0[126]_', 'act'), (r'^09_', 'uc'), (r'^2[4567]_', 'pkg'), (r'^03_', 'uc'), (r'^05_', 'class'), (r'^1[0-7]', 'uc'),
    (r'^2[15]', 'pkg'), (r'^22', 'class'), (r'^23_', 'stm'), (r'^30_', 'sd'),
    (r'^4[01]_', 'deployment'), (r'^42_', 'pkg'), (r'^4[34]_', 'cmp'), (r'^4[4567]_', 'class'),
    (r'^50_', 'sd'), (r'^6[012]_', 'cmp'),
]
SIN_CAMBIO = ('18_', '19_', '20_')   # prototipos de interfaz (Salt): se dejan como bocetos


def tipo(nombre: str) -> str:
    if nombre.startswith('44_'):
        return 'class'
    for patron, etiqueta in TIPOS:
        if re.match(patron, nombre):
            return etiqueta
    return 'class'


def preparar(origen: Path, destino: Path) -> None:
    t = origen.read_text(encoding='utf-8')
    if not origen.name.startswith(SIN_CAMBIO):
        t = t.replace('!include estilo.iuml', f'!include {(AQUI / "estilo_ea.iuml").as_posix()}')
        t = re.sub(r'^title (.+)$', lambda m: f'mainframe **{tipo(origen.name)}** {m.group(1)}', t, count=1, flags=re.M)
    else:
        t = t.replace('!include estilo.iuml', f'!include {(AQUI / "estilo.iuml").as_posix()}')
    destino.write_text(t, encoding='utf-8')


def main() -> None:
    archivos = [AQUI / a for a in sys.argv[1:]] or sorted(AQUI.glob('*.puml'))
    with tempfile.TemporaryDirectory() as tmp:
        copias = []
        for f in archivos:
            d = Path(tmp) / f.name
            preparar(f, d)
            copias.append(str(d))
        env = dict(os.environ, GRAPHVIZ_DOT=DOT)
        r = subprocess.run(['java', '-jar', str(JAR), '-charset', 'UTF-8', '-tpng', '-o', str(AQUI), *copias],
                           env=env, capture_output=True, text=True)
        if r.returncode:
            print(r.stdout, r.stderr)
            sys.exit(r.returncode)
    print(f'{len(archivos)} diagramas renderizados con estilo Enterprise Architect')


if __name__ == '__main__':
    main()
