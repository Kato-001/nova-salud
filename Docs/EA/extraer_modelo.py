# Extrae de los diagramas del proyecto la información para el modelo de Enterprise Architect:
# actores, casos de uso por ciclo y paquete, relaciones include/extend, paquetes de análisis,
# modelo de dominio y clases persistentes. Genera modelo.json (lo usa crear_modelo_ea.ps1).
import json
import re
from pathlib import Path

DIAG = Path(__file__).resolve().parent.parent / 'diagramas'

CU = {
    1: ('Iniciar sesión', 'P1'), 2: ('Gestionar roles y permisos', 'P1'), 3: ('Gestionar usuarios', 'P1'),
    4: ('Gestionar especialidades', 'P2'), 5: ('Gestionar médicos', 'P2'), 6: ('Gestionar horarios', 'P2'),
    7: ('Gestionar pacientes', 'P3'), 8: ('Registrarse como paciente', 'P3'),
    9: ('Consultar disponibilidad', 'P4'), 10: ('Reservar ficha', 'P4'), 11: ('Reprogramar cita', 'P4'),
    12: ('Cancelar cita', 'P4'), 13: ('Registrar asistencia', 'P4'), 14: ('Consultar agenda médica', 'P4'),
    15: ('Registrar consulta médica', 'P5'), 16: ('Gestionar historial clínico', 'P5'), 17: ('Registrar tratamiento', 'P5'),
    18: ('Gestionar estudios médicos', 'P5'), 19: ('Consultar historial y resultados', 'P5'), 20: ('Generar reportes', 'P6'),
}
PAQ = {'P1': 'P1 Seguridad y acceso', 'P2': 'P2 Gestión médica', 'P3': 'P3 Gestión de pacientes',
       'P4': 'P4 Gestión de citas', 'P5': 'P5 Atención médica', 'P6': 'P6 Reportes', 'PORTAL': 'Portal del paciente'}
ACTORES = {'ADM': 'Administrador', 'REC': 'Recepcionista', 'MED': 'Médico', 'PAC': 'Paciente'}


def relaciones_cu(archivo):
    t = (DIAG / archivo).read_text(encoding='utf-8')
    usa = re.findall(r'^(ADM|REC|MED|PAC) --> CU(\d+)$', t, re.M)
    otras = re.findall(r'^CU(\d+) \.\.> CU(\d+) : <<(include|extend)>>$', t, re.M)
    return ([{'actor': ACTORES[a], 'cu': int(c)} for a, c in usa],
            [{'origen': int(a), 'destino': int(b), 'tipo': k} for a, b, k in otras])


def clases(archivo):
    t = (DIAG / archivo).read_text(encoding='utf-8')
    res = []
    for nombre, cuerpo in re.findall(r'^class (\w+) \{\n(.*?)\n\}', t, re.M | re.S):
        attrs = []
        for linea in cuerpo.splitlines():
            linea = linea.strip()
            if not linea:
                continue
            n, _, tipo = linea.partition(':')
            attrs.append({'nombre': n.strip(), 'tipo': tipo.strip()})
        res.append({'nombre': nombre, 'atributos': attrs})
    for nombre in re.findall(r'^class (\w+)$', t, re.M):
        res.append({'nombre': nombre, 'atributos': []})
    rel = []
    for a, ca, tipo, cb, b, etiqueta in re.findall(r'^(\w+) "([^"]+)" (--|\*--) "([^"]+)" (\w+)(?: : (.+))?$', t, re.M):
        rel.append({'a': a, 'card_a': ca, 'b': b, 'card_b': cb, 'composicion': tipo == '*--', 'nombre': etiqueta.strip()})
    return res, rel


usa1, inc1 = relaciones_cu('10a_cu_ciclo1.puml')
usa2, inc2 = relaciones_cu('10b_cu_ciclo2.puml')
dep = re.findall(r'^(PORTAL|P\d) \.\.> (P\d)', (DIAG / '21_paquetes_analisis.puml').read_text(encoding='utf-8'), re.M)
dom, rel_dom = clases('05_modelo_dominio.puml')
per, rel_per = clases('47_clases_persistentes.puml')

modelo = {
    'actores': list(ACTORES.values()),
    'ciclos': [
        {'nombre': 'Ciclo I', 'casos': [{'id': i, 'nombre': CU[i][0], 'paquete': PAQ[CU[i][1]]} for i in range(1, 8)], 'usa': usa1, 'relaciones': inc1},
        {'nombre': 'Ciclo II', 'casos': [{'id': i, 'nombre': CU[i][0], 'paquete': PAQ[CU[i][1]]} for i in range(8, 21)], 'usa': usa2, 'relaciones': inc2},
    ],
    'paquetes': list(PAQ.values()),
    'dependencias': [{'de': PAQ[a], 'a': PAQ[b]} for a, b in dep],
    'dominio': {'clases': dom, 'relaciones': rel_dom},
    'persistentes': {'clases': per, 'relaciones': rel_per},
}
salida = Path(__file__).with_name('modelo.json')
salida.write_text(json.dumps(modelo, ensure_ascii=False, indent=1), encoding='utf-8')
print(f"actores {len(modelo['actores'])} · casos {sum(len(c['casos']) for c in modelo['ciclos'])} · "
      f"usos {len(usa1) + len(usa2)} · include/extend {len(inc1) + len(inc2)} · dependencias {len(dep)} · "
      f"dominio {len(dom)}/{len(rel_dom)} · persistentes {len(per)}/{len(rel_per)}")
