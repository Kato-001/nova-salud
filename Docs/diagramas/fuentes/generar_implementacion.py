# Genera los diagramas de componentes del flujo de trabajo de implementación:
#   61_impl_sistema.puml   7.2 Implementación de la arquitectura del sistema
#   62_impl_pN.puml        7.3 Implementación de la arquitectura de cada subsistema
# Estereotipos según la clase: «main», «file», «library», «engine», «script», «table»,
# «PDF», «Excel», «form», «report» y «class». Los nombres son los archivos reales.
from pathlib import Path

AQUI = Path(__file__).resolve().parent


def escribir(nombre, cuerpo):
    (AQUI / f'{nombre}.puml').write_text(f'@startuml {nombre}\n!include estilo.iuml\n{cuerpo}\n@enduml\n', encoding='utf-8')


def ident(texto):
    return ''.join(c if c.isalnum() else '_' for c in texto)


# --------------------------------------------------------------- 7.2 Sistema
PAQUETES = [('P1', 'P1 Seguridad\ny acceso'), ('P2', 'P2 Gestión\nmédica'), ('P3', 'P3 Gestión\nde pacientes'),
            ('P4', 'P4 Gestión\nde citas'), ('P5', 'P5 Atención\nmédica'), ('P6', 'P6 Reportes'), ('PO', 'Portal del\npaciente')]
TABLAS_BD = ['usuario', 'rol', 'paciente', 'medico', 'especialidad', 'horario', 'cita', 'historialcita',
             'consulta', 'tratamiento', 'estudio', 'bitacora']

s = ['title Implementación de la arquitectura del sistema · Nova Salud', 'skinparam nodesep 25', 'skinparam ranksep 45']
for k, n in PAQUETES:
    s.append(f'package "{n.replace(chr(10), " ")}" as {k} {{\n}}')
s.append('component "Sistema Web\\nNova Salud\\n(api/index.php)" as MAIN <<main>>')
s.append('package "Configuración" {\n'
         '  component ".env\\n(variables de entorno)" as fEnv <<file>>\n'
         '  component "vercel.json · .htaccess\\n· router.php" as fRut <<file>>\n}')
s.append('package "Librerías" {\n'
         '  component "PDO\\n(pdo_pgsql)" as lPdo <<library>>\n'
         '  component "Mailer\\n(SMTP Gmail)" as lMail <<library>>\n'
         '  component "app.js · citas.js\\napp.css" as lJs <<library>>\n}')
bd = ['package "B.D" {', '  component "PostgreSQL 17\\n(Supabase)" as eng <<engine>>',
      '  component "01_esquema.sql … 06_procedimientos\\n_triggers.sql" as scr <<script>>']
for t in TABLAS_BD:
    bd.append(f'  component "{t}" as t_{t} <<table>>')
bd.append('}')
s.append('\n'.join(bd))
s.append('package "Reporte" {\n'
         '  component "Reporte impreso\\n(.pdf)" as rPdf <<PDF>>\n'
         '  component "Reporte exportado\\n(.csv · Excel)" as rCsv <<Excel>>\n'
         '  component "P6_Reportes/index.php" as rVis <<file>>\n'
         '  component "ReporteController.php\\nmodels/Reporte.php" as rCtl <<file>>\n}')
for k, _ in PAQUETES:
    s.append(f'MAIN .up.> {k}')
s += ['MAIN .left.> fEnv', 'fRut .right.> MAIN', 'MAIN .right.> lPdo', 'MAIN .right.> lMail', 'MAIN .right.> lJs',
      'lPdo ..> eng : pdo_pgsql', 'MAIN ..> scr', 'scr ..> eng']
# Tablas en dos filas: el motor apunta a la primera y la segunda queda debajo
mitad = len(TABLAS_BD) // 2
for t in TABLAS_BD[:mitad]:
    s.append(f'eng ..> t_{t}')
for a, b in zip(TABLAS_BD[:mitad], TABLAS_BD[mitad:]):
    s.append(f't_{a} -[hidden]down- t_{b}')
s += ['MAIN ..> rVis', 'rVis ..> rPdf : imprimir', 'rVis ..> rCtl', 'rCtl ..> rCsv : exportar']
escribir('61_impl_sistema', '\n'.join(s))

# ------------------------------------------------------- 7.3 Subsistemas
# (paquete, título, [(vista, estereotipo, clase, [tablas])])
SUB = [
    ('p1', 'P1 Seguridad y acceso', [
        ('login.php', 'form', 'AuthController', ['usuario', 'sesion', 'bitacora']),
        ('recuperar.php\nrecuperar_codigo.php', 'form', 'RecuperacionController', ['recuperacion', 'usuario']),
        ('usuarios/index.php\nusuarios/form.php', 'form', 'UsuarioController', ['usuario', 'rol']),
        ('roles/index.php', 'form', 'RolController', ['rol', 'permiso', 'rolpermiso']),
        ('configuracion/index.php', 'form', 'ConfiguracionController', ['configuracion']),
        ('bitacora/index.php', 'report', 'BitacoraController', ['bitacora']),
    ]),
    ('p2', 'P2 Gestión médica', [
        ('especialidades/index.php', 'form', 'EspecialidadController', ['especialidad']),
        ('medicos/index.php\nmedicos/form.php', 'form', 'MedicoController', ['medico', 'medicoespecialidad', 'usuario']),
        ('medicos/ver.php', 'report', 'MedicoController', ['medico', 'cita']),
        ('horarios/index.php', 'form', 'HorarioController', ['horario', 'bloqueoagenda']),
    ]),
    ('p3', 'P3 Gestión de pacientes', [
        ('pacientes/index.php\npacientes/form.php', 'form', 'PacienteController', ['paciente', 'historiaclinica']),
        ('pacientes/ver.php', 'report', 'PacienteController', ['paciente', 'contactoemergencia', 'cita']),
        ('registro.php', 'form', 'AuthController', ['usuario', 'paciente']),
    ]),
    ('p4', 'P4 Gestión de citas', [
        ('citas/nueva.php\n+ citas.js', 'form', 'CitaController', ['cita', 'horario', 'bloqueoagenda']),
        ('citas/reprogramar.php', 'form', 'CitaController', ['cita', 'historialcita']),
        ('citas/ver.php', 'form', 'CitaController', ['cita', 'historialcita', 'notificacion']),
        ('agenda/index.php', 'report', 'AgendaController', ['cita', 'horario']),
    ]),
    ('p5', 'P5 Atención médica', [
        ('consulta.php', 'form', 'AtencionController', ['consulta', 'tratamiento', 'medicamento']),
        ('estudios/index.php\nestudios/resultado.php', 'form', 'EstudioController', ['estudio', 'notificacion']),
        ('estudios/catalogo.php', 'form', 'EstudioController', ['catalogoestudio']),
    ]),
    ('p6', 'P6 Reportes', [
        ('P6_Reportes/index.php', 'report', 'ReporteController', ['cita', 'paciente', 'medico', 'especialidad']),
    ]),
    ('portal', 'Portal del paciente', [
        ('Portal/reservar.php', 'form', 'PortalController', ['cita', 'notificacion']),
        ('Portal/citas.php', 'report', 'PortalController', ['cita']),
        ('Portal/perfil.php', 'form', 'PortalController', ['paciente', 'historiaclinica', 'contactoemergencia']),
        ('Portal/historial.php', 'report', 'PortalController', ['consulta', 'tratamiento', 'estudio']),
    ]),
]

for clave, titulo, filas in SUB:
    vistas, clases, tablas, enlaces = [], {}, {}, []
    for i, (vista, est, clase, tbls) in enumerate(filas):
        v = f'v{i}'
        vistas.append(f'  component "{vista.replace(chr(10), chr(92) + "n")}" as {v} <<{est}>>')
        c = 'c_' + ident(clase)
        clases.setdefault(c, f'  component "{clase.replace(chr(10), chr(92) + "n")}" as {c} <<class>>')
        enlaces.append(f'{v} ..> {c}')
        for t in tbls:
            tablas.setdefault(t, f'  component "{t}" as t_{t} <<table>>')
            enlaces.append(f'{c} ..> t_{t}')
    cuerpo = [f'title Implementación de la arquitectura del subsistema {titulo}',
              'skinparam nodesep 20', 'skinparam ranksep 55',
              'package "Interfaz (Frontend/views)" {', *vistas, '}',
              'package "Lógica (Bakend/app)" {', *clases.values(), '}',
              'package "Datos (PostgreSQL · esquema clinica)" {', *tablas.values(), '}',
              *dict.fromkeys(enlaces)]
    escribir(f'62_impl_{clave}', '\n'.join(cuerpo))

print('Diagramas de implementación: 1 del sistema y', len(SUB), 'de subsistemas')
