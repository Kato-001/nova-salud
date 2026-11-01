"""
Genera los diagramas del flujo de análisis por paquete y por caso de uso:
  09_cu_cuNN     diagrama de casos de uso de cada CU (para su descripción, 2.3)
  24_paq_pN      paquete de análisis (3.1.1)
  26_rel_pN      paquete y sus casos de uso con «include» (3.1.2)
  27_vista_pN    vista del paquete con sus actores y casos de uso (3.1.3)
  28_clase_cuNN  clases de análisis de cada caso de uso: «form», «control» y «entity» (3.3)
Los objetos y mensajes de cada CU se toman de generar_cu.py (diagramas de comunicación)
y los atributos de las entidades, de Bakend/database/01_esquema.sql.
Uso:  python generar_analisis.py
"""
import re
from pathlib import Path

AQUI = Path(__file__).resolve().parent
ESQUEMA = AQUI.parent.parent / 'Bakend' / 'database' / '01_esquema.sql'

import generar_cu  # noqa: E402  (también regenera los diagramas de comunicación, sin cambios)

PAQUETES = {
    'P1': ('P1 Seguridad y acceso', [1, 2, 3]),
    'P2': ('P2 Gestión médica', [4, 5, 6]),
    'P3': ('P3 Gestión de pacientes', [7, 8]),
    'P4': ('P4 Gestión de citas', [9, 10, 11, 12, 13, 14]),
    'P5': ('P5 Atención médica', [15, 16, 17, 18, 19]),
    'P6': ('P6 Reportes', [20]),
}
NOMBRES = {int(c[0]): c[1] for c in generar_cu.CU}
ACTORES = {
    1: ['Administrador', 'Recepcionista', 'Médico', 'Paciente'], 2: ['Administrador'], 3: ['Administrador'],
    4: ['Administrador'], 5: ['Administrador', 'Recepcionista'], 6: ['Administrador'], 7: ['Recepcionista', 'Administrador'],
    8: ['Paciente'], 9: ['Recepcionista', 'Paciente'], 10: ['Recepcionista', 'Administrador', 'Paciente'],
    11: ['Recepcionista'], 12: ['Recepcionista', 'Paciente'], 13: ['Recepcionista', 'Médico'],
    14: ['Médico', 'Recepcionista', 'Administrador'], 15: ['Médico'], 16: ['Médico', 'Recepcionista', 'Paciente'],
    17: ['Médico'], 18: ['Médico', 'Recepcionista', 'Administrador'], 19: ['Paciente'], 20: ['Administrador', 'Recepcionista'],
}
ALIAS = {'Administrador': 'ADM', 'Recepcionista': 'REC', 'Médico': 'MED', 'Paciente': 'PAC'}

# Campos de cada formulario («form»)
FORM = {
    1: ['login: String', 'clave: String', 'recordar: boolean'],
    2: ['nombre: String', 'descripcion: String', 'permisos: List<int>'],
    3: ['nombre: String', 'username: String', 'email: String', 'idrol: int', 'clave: String', 'estado: String'],
    4: ['nombre: String', 'descripcion: String', 'estado: String'],
    5: ['codigo: String', 'registroProfesional: String', 'nombres: String', 'apellidos: String', 'telefono: String',
        'email: String', 'especialidades: List<int>'],
    6: ['idMedico: int', 'diaSemana: int', 'horaInicio: Time', 'horaFin: Time', 'fechaBloqueo: Date', 'tipoBloqueo: String'],
    7: ['busqueda: String', 'ci: String', 'nombres: String', 'apellidos: String', 'fechaNacimiento: Date', 'sexo: String',
        'telefono: String', 'email: String'],
    8: ['ci: String', 'nombres: String', 'apellidos: String', 'fechaNacimiento: Date', 'telefono: String', 'email: String',
        'username: String', 'clave: String'],
    9: ['idEspecialidad: int', 'idMedico: int', 'fecha: Date'],
    10: ['idPaciente: int', 'idEspecialidad: int', 'idMedico: int', 'fecha: Date', 'horaInicio: Time', 'motivo: String'],
    11: ['idCita: int', 'nuevaFecha: Date', 'nuevaHora: Time', 'motivo: String'],
    12: ['idCita: int', 'motivoCancelacion: String'],
    13: ['idCita: int', 'nuevoEstado: String'],
    14: ['fecha: Date', 'idMedico: int', 'estado: String'],
    15: ['idCita: int', 'peso: Decimal', 'talla: Decimal', 'presionArterial: String', 'temperatura: Decimal',
         'diagnostico: String', 'observaciones: String'],
    16: ['idPaciente: int', 'antecedentesPersonales: String', 'antecedentesFamiliares: String', 'alergias: String'],
    17: ['idMedicamento: int', 'dosis: String', 'frecuencia: String', 'duracion: String', 'indicaciones: String'],
    18: ['idCatalogo: int', 'indicaciones: String', 'resultado: String', 'archivoResultado: File'],
    19: ['idPaciente: int', 'filtro: String'],
    20: ['tipo: String', 'desde: Date', 'hasta: Date', 'idMedico: int', 'idEspecialidad: int', 'estado: String'],
}

TIPOS = {'integer': 'int', 'varchar': 'String', 'text': 'String', 'date': 'Date', 'time': 'Time',
         'timestamptz': 'DateTime', 'boolean': 'boolean', 'numeric': 'Decimal'}


def leer_esquema():
    tablas, actual = {}, None
    for linea in ESQUEMA.read_text(encoding='utf-8').splitlines():
        m = re.match(r'create table (\w+) \(', linea)
        if m:
            actual = tablas.setdefault(m.group(1), [])
            continue
        if actual is None:
            continue
        if linea.startswith(')'):
            actual = None
            continue
        m = re.match(r'\s{4}(\w+)\s+(integer|varchar|text|date|time|timestamptz|boolean|numeric)\b', linea)
        if m and m.group(1) not in ('constraint', 'primary'):
            actual.append(f'{m.group(1)}: {TIPOS[m.group(2)]}')
    return tablas


TABLAS = leer_esquema()


def metodo(texto):
    t = re.sub(r'^\d+(\.\d+)?:\s*', '', texto.strip())
    if '(' in t:
        return t[:t.index(')') + 1]
    palabras = t.split()
    return palabras[0] + ''.join(p.capitalize() for p in palabras[1:]) + '()'


def entidades(nombre):
    """'Rol / Permiso' se representa con dos clases."""
    return [n.strip() for n in nombre.split('/')]


def guardar(nombre, lineas):
    (AQUI / f'{nombre}.puml').write_text('\n'.join(lineas) + '\n', encoding='utf-8')


def actores_puml(ids):
    return [f'actor "{a}" as {ALIAS[a]}' for a in ids]


# ------------------------------------------------------------------ 09 · CU de cada caso de uso
for n, nombre in NOMBRES.items():
    l = [f'@startuml 09_cu_cu{n:02d}', '!include estilo.iuml', f'title CU{n} {nombre}']
    if n == 1:
        l += ['actor "Usuario" as USR'] + actores_puml(ACTORES[1])
        l += [f'usecase "CU1 {nombre}" as UC', 'USR -right- UC']
        l += [f'{ALIAS[a]} -up-|> USR' for a in ACTORES[1]]
    else:
        l += ['left to right direction'] + actores_puml(ACTORES[n]) + [f'usecase "CU{n} {nombre}" as UC']
        l += [f'{ALIAS[a]} -- UC' for a in ACTORES[n]]
    guardar(f'09_cu_cu{n:02d}', l + ['@enduml'])

# ------------------------------------------------------------------ 24, 26 y 27 · paquetes
for clave, (nombre, cus) in PAQUETES.items():
    k = clave[1]
    guardar(f'24_paq_p{k}', [f'@startuml 24_paq_p{k}', '!include estilo.iuml', f'title Paquete {nombre}',
                             'skinparam packageStyle folder', f'package "{nombre}" as PK {{', '}', '@enduml'])

    l = [f'@startuml 26_rel_p{k}', '!include estilo.iuml', f'title Relación del paquete {nombre} con sus casos de uso',
         'left to right direction', f'folder "{nombre}" as PK']
    for c in cus:
        l += [f'usecase "CU{c}\\n{NOMBRES[c]}" as CU{c}', f'PK ..> CU{c} : <<include>>']
    guardar(f'26_rel_p{k}', l + ['@enduml'])

    usados = [a for a in ALIAS if any(a in ACTORES[c] for c in cus)]
    l = [f'@startuml 27_vista_p{k}', '!include estilo.iuml', f'title Vista del paquete {nombre}',
         'skinparam packageStyle folder', 'left to right direction', f'package "{nombre}" {{']
    l += ['  ' + x for x in actores_puml(usados)]
    l += [f'  usecase "CU{c}\\n{NOMBRES[c]}" as CU{c}' for c in cus]
    l += [f'  {ALIAS[a]} --> CU{c}' for c in cus for a in ACTORES[c]]
    guardar(f'27_vista_p{k}', l + ['}', '@enduml'])

# ------------------------------------------------------------------ 28 · clases de análisis por CU
for cod, nombre, actor, objetos, mensajes in generar_cu.CU:
    n = int(cod)
    alias = {'A': None}
    clases = {}                      # alias de clase -> (tipo, nombre)
    for tipo, a, nom in objetos:
        if tipo == 'entity':
            for i, e in enumerate(entidades(nom)):
                clases[f'{a}{i or ""}'] = ('entity', e)
            alias[a] = [f'{a}{i or ""}' for i in range(len(entidades(nom)))]
        else:
            clases[a] = (tipo, nom)
            alias[a] = [a]
    metodos = {c: [] for c in clases}
    llamadas = []
    for desde, hacia, texto in mensajes:
        for destino in alias[hacia] or []:
            for linea in texto.split('\n'):
                m = metodo(linea)
                if m not in metodos[destino]:
                    metodos[destino].append(m)
            if desde != 'A':
                for origen in alias[desde]:
                    if (origen, destino) not in llamadas and clases[origen][0] != 'entity' \
                            and clases[destino][0] != 'boundary':
                        llamadas.append((origen, destino))

    l = [f'@startuml 28_clase_cu{n:02d}', '!include estilo.iuml', f'title Análisis de clases · CU{n} {nombre}',
         'allowmixing', 'hide circle', 'skinparam classAttributeIconSize 0']
    l += actores_puml(ACTORES[n]) if n != 1 else ['actor "Usuario" as USR']
    for a, (tipo, nom) in clases.items():
        est = {'boundary': 'form', 'control': 'control', 'entity': 'entity'}[tipo]
        l.append(f'class "{nom}" as {a} <<{est}>> {{')
        if tipo == 'boundary':
            l += [f'  + {x}' for x in FORM[n]]
            l += ['  --', '  + mostrarInterfaz()'] + [f'  + {m}' for m in metodos[a]] + ['  + mostrarMensaje(texto: String)']
        elif tipo == 'control':
            usa = [clases[d][1] for o, d in llamadas if o == a and clases[d][0] == 'entity']
            l += [f'  - modelo{u}: {u}' for u in dict.fromkeys(usa)]
            l += ['  --'] + [f'  + {m}' for m in metodos[a]]
        else:
            tabla = nom.lower().replace(' ', '')
            atributos = TABLAS.get(tabla, [])
            l += [f'  + {x}' for x in atributos[:9]] + (['  + …'] if len(atributos) > 9 else [])
            l += ['  --'] + [f'  + {m}' for m in metodos[a]]
        l.append('}')
    formularios = [a for a, (t, _) in clases.items() if t == 'boundary']
    for f in formularios:
        l += [f'{ALIAS[x]} --> {f}' for x in ACTORES[n]] if n != 1 else [f'USR --> {f}']
    for o, d in llamadas:
        if clases[o][0] == 'boundary' and clases[d][0] == 'control':
            l.append(f'{o} "1" --> "1" {d}')
        elif clases[d][0] == 'entity':
            l.append(f'{o} "1" --> "0..*" {d}')
        else:
            l.append(f'{o} ..> {d}')
    guardar(f'28_clase_cu{n:02d}', l + ['@enduml'])

print('Diagramas de análisis generados: 20 CU, 6 paquetes x 3 y 20 clases de análisis')
