# Pruebas de caja negra de los casos de uso de Nova Salud.
# Cada prueba indica interfaz, entrada, condición, salida esperada y procedimiento,
# se ejecuta por HTTP como lo haría un usuario y guarda el resultado en resultados.json.
#
# Uso: python pruebas_caja_negra.py [URL_BASE]   (por defecto http://127.0.0.1:8099)
# El servidor local debe tener un SMTP configurado (para la prueba de recuperación).
import datetime as dt
import json
import re
import sys
from pathlib import Path

import requests

BASE = (sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8099').rstrip('/')
CLAVE = 'HeadSalud2026'
HOY = dt.date.today()
estado = {}   # datos que comparten las pruebas (paciente y cita creados)


class Sesion:
    def __init__(self, usuario=None):
        self.s = requests.Session()
        if usuario:
            r = self.post('/login', login=usuario, password=CLAVE)
            assert '/login' not in r.url, f'no se pudo ingresar como {usuario}'

    def token(self, ruta='/login'):
        html = self.s.get(BASE + ruta).text
        m = re.search(r'name="_csrf" value="([^"]+)"', html) or re.search(r'data-csrf="([^"]+)"', html)
        return m.group(1) if m else ''

    def get(self, ruta, **kw):
        return self.s.get(BASE + ruta, **kw)

    def post(self, ruta, origen=None, **datos):
        datos['_csrf'] = self.token(origen or ruta)
        return self.s.post(BASE + ruta, data=datos)


def avisos(r):
    """Mensajes flash que muestra la página a la que se redirigió."""
    m = re.search(r'id="flash-data">([^<]*)', r.text)
    return ' | '.join(f['mensaje'] for f in json.loads(m.group(1))) if m else ''


def proximo(dia_semana):
    """Próxima fecha (a partir de mañana) del día de la semana ISO dado (1 = lunes)."""
    d = HOY + dt.timedelta(days=1)
    while d.isoweekday() != dia_semana:
        d += dt.timedelta(days=1)
    return d.isoformat()


def libre(ses, medico, fecha, n=0):
    b = ses.get(f'/api/disponibilidad?medico={medico}&fecha={fecha}').json()
    libres = [x['inicio'] for x in b.get('bloques', []) if x['libre']]
    return libres[n]


# ----------------------------------------------------------------- Pruebas
def cu1_login_ok():
    r = Sesion().post('/login', login='admin', password=CLAVE)
    return r.url.endswith('/panel'), 'Ingresa al panel del administrador'


def cu1_login_mal():
    r = Sesion().post('/login', login='mrojas', password='claveIncorrecta9')
    return 'incorrectos' in avisos(r), avisos(r)


def cu1_sin_sesion():
    r = requests.get(BASE + '/pacientes', allow_redirects=False)
    return r.status_code == 302 and r.headers['Location'].endswith('/login'), f'HTTP {r.status_code} → {r.headers.get("Location")}'


def cu1_recuperar_no_existe():
    r = Sesion().post('/recuperar', email='nadie.registrado@correo.com')
    return 'Si el correo está registrado' in avisos(r), avisos(r)


def cu1_recuperar_codigo_mal():
    s = Sesion()
    s.post('/recuperar', email='jperez@correo.com')
    r = s.post('/recuperar/codigo', codigo='000000', password='NuevaClave2026', password_confirmacion='NuevaClave2026')
    return 'Código incorrecto' in avisos(r), avisos(r)


def cu2_permisos():
    r = Sesion('mrojas').get('/roles')
    return r.status_code == 403, f'HTTP {r.status_code} · Acceso denegado'


def cu3_usuario_duplicado():
    r = Sesion('admin').post('/usuarios/guardar', origen='/usuarios/nuevo', nombre='Usuario Prueba', username='admin',
                              email='otro.correo@vidasalud.com', idrol='2', password='Clave12345', password_confirmacion='Clave12345')
    return 'en uso' in avisos(r), avisos(r)


def cu3_clave_debil():
    r = Sesion('admin').post('/usuarios/guardar', origen='/usuarios/nuevo', nombre='Usuario Prueba', username='prueba.debil',
                              email='prueba.debil@vidasalud.com', idrol='2', password='abc', password_confirmacion='abc')
    return 'caracteres' in avisos(r), avisos(r)


def cu4_especialidad_duplicada():
    r = Sesion('admin').post('/especialidades/guardar', origen='/especialidades', nombre='  pediatria ')
    return 'Ya existe' in avisos(r), avisos(r)


def cu5_medico_duplicado():
    r = Sesion('admin').post('/medicos/guardar', origen='/medicos/nuevo', codigo='MED-001', registroprofesional='RP-99999',
                              nombres='Prueba', apellidos='Duplicado', email='prueba.med@vidasalud.com',
                              **{'especialidades[]': '1'}, username='prueba.med', password='Clave12345', password_confirmacion='Clave12345')
    return 'código' in avisos(r).lower(), avisos(r)


def cu6_horario_invertido():
    r = Sesion('admin').post('/horarios/guardar', origen='/horarios?medico=1', idmedico='1', diasemana='1', horainicio='13:00', horafin='12:00')
    return 'posterior' in avisos(r), avisos(r)


def cu6_horario_cruzado():
    r = Sesion('admin').post('/horarios/guardar', origen='/horarios?medico=1', idmedico='1', diasemana='1', horainicio='10:00', horafin='13:00')
    return 'cruza' in avisos(r), avisos(r)


def cu7_registrar():
    ci = str(9_000_000 + int(dt.datetime.now().timestamp()) % 900_000)   # CI distinto en cada ejecución
    r = Sesion('mrojas').post('/pacientes/guardar', origen='/pacientes/nuevo', ci=ci, nombres='Prueba', apellidos='Caja Negra',
                               fechanacimiento='1995-03-10', sexo='F', telefono='70000001')
    m = re.search(r'id=(\d+)', r.url)
    if m:
        estado['paciente'] = m.group(1)
    return 'registrado correctamente' in avisos(r), avisos(r) + f' (CI {ci})'


def cu7_ci_duplicado():
    r = Sesion('mrojas').post('/pacientes/guardar', origen='/pacientes/nuevo', ci='1234567', nombres='Otra', apellidos='Persona',
                               fechanacimiento='1990-01-01', sexo='M', telefono='70000002')
    return 'CI' in avisos(r), avisos(r)


def cu7_datos_invalidos():
    r = Sesion('mrojas').post('/pacientes/guardar', origen='/pacientes/nuevo', ci='ABC', nombres='1', apellidos='X',
                               fechanacimiento='2099-01-01', sexo='X', telefono='12', email='malo')
    return '/pacientes/nuevo' in r.url and avisos(r) != '', avisos(r)


def cu8_autoregistro_duplicado():
    r = Sesion().post('/registro', ci='9123456', nombres='Nuevo', apellidos='Paciente', fechanacimiento='2000-02-02', sexo='M',
                      telefono='71111111', email='nuevo.paciente@correo.com', username='jperez',
                      password='Clave12345', password_confirmacion='Clave12345', acepta='1')
    return 'en uso' in avisos(r), avisos(r)


def cu9_fecha_pasada():
    j = Sesion('mrojas').get('/api/disponibilidad?medico=1&fecha=2026-01-05').json()
    return not j['ok'] and 'pasadas' in j['motivo'], j['motivo']


def cu9_dia_sin_atencion():
    j = Sesion('mrojas').get(f'/api/disponibilidad?medico=1&fecha={proximo(7)}').json()
    return not j['ok'] and 'no atiende' in j['motivo'], j['motivo']


def cu10_programar():
    s = Sesion('mrojas')
    f = proximo(3)
    h = libre(s, 1, f)
    estado.update(fecha=f, hora=h)
    r = s.post('/citas/guardar', origen='/citas/nueva', idpaciente=estado['paciente'], idespecialidad='1', idmedico='1',
               fecha=f, hora=h, modalidad='PRESENCIAL', motivo='Prueba de caja negra')
    m = re.search(r'id=(\d+)', r.url)
    if m:
        estado['cita'] = m.group(1)
    return 'programada' in avisos(r), avisos(r)


def cu10_superpuesta():
    r = Sesion('mrojas').post('/citas/guardar', origen='/citas/nueva', idpaciente='3', idespecialidad='1', idmedico='1',
                              fecha=estado['fecha'], hora=estado['hora'], modalidad='PRESENCIAL')
    return 'no está disponible' in avisos(r), avisos(r)


def cu10_fuera_horario():
    r = Sesion('mrojas').post('/citas/guardar', origen='/citas/nueva', idpaciente=estado['paciente'], idespecialidad='1',
                              idmedico='1', fecha=estado['fecha'], hora='15:00', modalidad='PRESENCIAL')
    return 'fuera del horario' in avisos(r), avisos(r)


def cu11_reprogramar():
    s = Sesion('mrojas')
    h = libre(s, 1, estado['fecha'], 1)
    r = s.post('/citas/reprogramar', origen=f'/citas/reprogramar?id={estado["cita"]}', idcita=estado['cita'],
               fecha=estado['fecha'], hora=h, motivo='Cambio pedido por el paciente')
    return 'reprogramada' in avisos(r), avisos(r)


def cu13_asistencia_futura():
    r = Sesion('mrojas').post('/citas/estado', origen=f'/citas/ver?id={estado["cita"]}', idcita=estado['cita'], estado='ATENDIDA')
    return 'día de la cita' in avisos(r), avisos(r)


def cu12_cancelar():
    r = Sesion('mrojas').post('/citas/cancelar', origen=f'/citas/ver?id={estado["cita"]}', idcita=estado['cita'],
                              motivo='El paciente no podrá asistir')
    return 'cancelada' in avisos(r).lower(), avisos(r)


def cu12_reprogramar_cancelada():
    r = Sesion('mrojas').get(f'/citas/reprogramar?id={estado["cita"]}')
    return '/citas/ver' in r.url, avisos(r) or 'Redirige al detalle de la cita'


def cu14_agenda():
    r = Sesion('mrojas').get('/agenda?medico=3&fecha=2026-11-05')
    medicos = set(re.findall(r'Dr\(a\)\. ([^<]+)</h2>', r.text))
    return r.status_code == 200 and 'Suárez' in r.text and 'Mendoza Ríos' not in ''.join(medicos), \
        f'Agenda del 05/11/2026 con {r.text.count("agenda-item ")} cita(s) del Dr. Suárez'


def cu15_cita_ajena():
    r = Sesion('pmolina').get('/atencion?cita=239')
    return r.status_code == 403, f'HTTP {r.status_code} · Acceso denegado'


def cu16_historia():
    r = Sesion('mrojas').get('/pacientes/ver?id=1&tab=clinica')
    return r.status_code == 200 and 'Alergias' in r.text, 'Muestra antecedentes, alergias y consultas'


def cu17_tratamiento_incompleto():
    r = Sesion('rsuarez').post('/atencion/guardar', origen='/atencion?cita=239', idcita='239', diagnostico='Control',
                               **{'tratamientos[0][idmedicamento]': '2', 'tratamientos[0][dosis]': ''})
    return 'Complete' in avisos(r), avisos(r)


def cu18_resultado_vacio():
    s = Sesion('mrojas')
    m = re.search(r'resultado\?id=(\d+)', s.get('/estudios?estado=SOLICITADO').text)
    r = s.post('/estudios/resultado', origen=f'/estudios/resultado?id={m.group(1)}', idestudio=m.group(1), estado='COMPLETADO', resultado='')
    return 'resultado' in avisos(r).lower(), avisos(r)


def cu19_portal():
    s = Sesion('jperez')
    ok = s.get('/portal/historial').status_code == 200 and s.get('/panel').status_code == 403
    return ok, 'Ve su historial y resultados; /panel responde 403'


def cu20_csv():
    r = Sesion('admin').get('/reportes?tipo=medico&formato=csv')
    return 'csv' in r.headers.get('Content-Type', '') and 'Médico;Total' in r.text, 'Descarga ' + r.headers.get('Content-Type', '')


def cu20_permiso():
    r = Sesion('rsuarez').get('/reportes')
    return r.status_code == 403, f'HTTP {r.status_code} · Acceso denegado'


# (id, CU, prueba, interfaz, entrada, condición, salida esperada, procedimiento, función)
PRUEBAS = [
    ('P01', 'CU1', 'Ingresar con datos válidos', '/login', 'Usuario admin y su contraseña', 'Cuenta activa',
     'Ingresa al panel según su rol', 'Escribir usuario y contraseña y pulsar Ingresar', cu1_login_ok),
    ('P02', 'CU1', 'Ingresar con contraseña incorrecta', '/login', 'Usuario mrojas y una contraseña errónea', 'Contraseña no coincide',
     '"Usuario o contraseña incorrectos"', 'Escribir una contraseña errónea y pulsar Ingresar', cu1_login_mal),
    ('P03', 'CU1', 'Abrir una página interna sin sesión', '/pacientes', 'Ninguna', 'No hay sesión iniciada',
     'Redirige a /login', 'Escribir la dirección en el navegador sin haber ingresado', cu1_sin_sesion),
    ('P04', 'CU1', 'Recuperar contraseña con un correo no registrado', '/recuperar', 'nadie.registrado@correo.com',
     'El correo no pertenece a ninguna cuenta', 'Mismo mensaje que con un correo registrado (no revela cuentas)',
     'Elegir "¿Olvidaste tu contraseña?" y enviar el correo', cu1_recuperar_no_existe),
    ('P05', 'CU1', 'Recuperar contraseña con un código incorrecto', '/recuperar/codigo', 'Código 000000 y nueva contraseña',
     'El código no coincide', '"Código incorrecto. Le quedan N intento(s)"', 'Solicitar el código y escribir uno equivocado', cu1_recuperar_codigo_mal),
    ('P06', 'CU2', 'Recepcionista abre roles y permisos', '/roles', 'Sesión de mrojas', 'El rol no tiene roles.gestionar',
     'HTTP 403 Acceso denegado', 'Ingresar como recepcionista y abrir /roles', cu2_permisos),
    ('P07', 'CU3', 'Registrar un usuario con nombre repetido', '/usuarios/nuevo', 'Usuario "admin"', 'El usuario ya existe',
     '"Ese nombre de usuario ya está en uso"', 'Completar el formulario y pulsar Guardar', cu3_usuario_duplicado),
    ('P08', 'CU3', 'Registrar un usuario con contraseña débil', '/usuarios/nuevo', 'Contraseña "abc"', 'Menos de 8 caracteres sin números',
     'Mensaje de contraseña inválida', 'Completar el formulario y pulsar Guardar', cu3_clave_debil),
    ('P09', 'CU4', 'Registrar una especialidad repetida', '/especialidades', '"  pediatria " (ya existe "Pediatría")',
     'Nombre normalizado repetido', '"Ya existe una especialidad con ese nombre"', 'Escribir el nombre y pulsar Agregar', cu4_especialidad_duplicada),
    ('P10', 'CU5', 'Registrar un médico con código repetido', '/medicos/nuevo', 'Código MED-001', 'El código ya existe',
     'Mensaje de código repetido', 'Completar el formulario y pulsar Registrar', cu5_medico_duplicado),
    ('P11', 'CU6', 'Registrar un horario con hora final menor', '/horarios', 'Lunes 13:00 a 12:00', 'Hora final ≤ hora inicial',
     '"La hora final debe ser posterior a la inicial"', 'Elegir día y horas y pulsar Agregar horario', cu6_horario_invertido),
    ('P12', 'CU6', 'Registrar un horario que se cruza', '/horarios', 'Lunes 10:00 a 13:00 (ya atiende 08:00–12:00)',
     'Se superpone con otro horario activo', '"Se cruza con otro horario activo del médico ese día"', 'Agregar el horario al Dr. Mendoza', cu6_horario_cruzado),
    ('P13', 'CU7', 'Registrar un paciente nuevo', '/pacientes/nuevo', 'CI nuevo, nombres, nacimiento, sexo, teléfono', 'Datos válidos y CI no registrado',
     '"Paciente registrado correctamente"', 'Completar el formulario y pulsar Registrar paciente', cu7_registrar),
    ('P14', 'CU7', 'Registrar un paciente con CI duplicado', '/pacientes/nuevo', 'CI 1234567 (Juan Pérez)', 'El CI ya existe',
     '"Ya existe un paciente registrado con ese CI"', 'Completar el formulario y pulsar Registrar paciente', cu7_ci_duplicado),
    ('P15', 'CU7', 'Registrar un paciente con datos inválidos', '/pacientes/nuevo', 'CI "ABC", nacimiento 2099, sexo X, correo "malo"',
     'Datos con formato inválido (se omite la validación del navegador)', 'El servidor rechaza y vuelve al formulario',
     'Enviar el formulario directamente al servidor', cu7_datos_invalidos),
    ('P16', 'CU8', 'Autoregistro con un usuario existente', '/registro', 'Usuario "jperez"', 'El usuario ya existe',
     '"Ese nombre de usuario ya está en uso"', 'Completar el registro y pulsar Crear mi cuenta', cu8_autoregistro_duplicado),
    ('P17', 'CU9', 'Consultar horarios en una fecha pasada', '/citas/nueva (asistente)', 'Dr. Mendoza, 05/01/2026', 'La fecha ya pasó',
     '"No se pueden reservar citas en fechas pasadas"', 'Elegir médico y una fecha pasada', cu9_fecha_pasada),
    ('P18', 'CU9', 'Consultar horarios un día sin atención', '/citas/nueva (asistente)', 'Dr. Mendoza, próximo domingo',
     'El médico no atiende ese día', '"El médico no atiende los domingos"', 'Elegir médico y un domingo', cu9_dia_sin_atencion),
    ('P19', 'CU10', 'Programar una cita en horario libre', '/citas/nueva', 'Paciente de P13, Medicina General, Dr. Mendoza, miércoles, primer horario libre',
     'El horario está libre', '"Cita CM-#### programada para …"', 'Seguir el asistente y pulsar Confirmar cita', cu10_programar),
    ('P20', 'CU10', 'Programar una cita superpuesta', '/citas/nueva', 'Otro paciente, mismo médico, fecha y hora de P19',
     'El médico ya tiene una cita a esa hora', '"El horario … ya no está disponible"', 'Enviar la misma hora para otro paciente', cu10_superpuesta),
    ('P21', 'CU10', 'Programar una cita fuera del horario', '/citas/nueva', 'Dr. Mendoza a las 15:00 (atiende 08:00–12:00)',
     'La hora no pertenece al horario del médico', '"… fuera del horario de atención del médico"', 'Enviar una hora fuera del horario', cu10_fuera_horario),
    ('P22', 'CU11', 'Reprogramar una cita', '/citas/reprogramar', 'Cita de P19, nuevo horario libre y motivo',
     'La cita está programada y el horario está libre', '"Cita reprogramada para …"', 'Abrir la cita, Reprogramar, elegir hora y guardar', cu11_reprogramar),
    ('P23', 'CU13', 'Registrar asistencia de una cita futura', '/citas/ver', 'Cita de P19 · Marcar como atendida',
     'La fecha de la cita aún no llega', '"La asistencia solo se registra el día de la cita o después"', 'Pulsar Marcar como atendida', cu13_asistencia_futura),
    ('P24', 'CU12', 'Cancelar una cita con motivo', '/citas/ver', 'Cita de P19 y motivo', 'La cita está programada',
     'Estado Cancelada; el horario queda libre y el historial se conserva', 'Pulsar Cancelar cita, escribir el motivo y confirmar', cu12_cancelar),
    ('P25', 'CU12', 'Reprogramar una cita cancelada', '/citas/reprogramar', 'Cita cancelada de P24', 'La cita está en un estado final',
     'No permite reprogramar y vuelve al detalle', 'Abrir Reprogramar de la cita cancelada', cu12_reprogramar_cancelada),
    ('P26', 'CU14', 'Consultar la agenda de un médico por fecha', '/agenda', 'Dr. Suárez, 05/11/2026', 'Hay citas ese día',
     'Solo las citas de ese médico y fecha', 'Elegir médico y fecha en la agenda', cu14_agenda),
    ('P27', 'CU15', 'Médico intenta atender una cita de otro médico', '/atencion', 'Sesión de pmolina, cita del Dr. Suárez',
     'La cita no es del médico en sesión', 'HTTP 403 Acceso denegado', 'Abrir la dirección de atención de una cita ajena', cu15_cita_ajena),
    ('P28', 'CU16', 'Consultar la historia clínica', '/pacientes/ver?tab=clinica', 'Paciente Juan Pérez', 'Sesión de recepción',
     'Antecedentes, alergias y consultas anteriores', 'Abrir la ficha y la pestaña Historia clínica', cu16_historia),
    ('P29', 'CU17', 'Registrar un tratamiento incompleto', '/atencion', 'Medicamento sin dosis, frecuencia ni duración',
     'Faltan datos obligatorios del tratamiento', '"Complete medicamento, dosis, frecuencia y duración …"', 'Agregar un medicamento incompleto y guardar', cu17_tratamiento_incompleto),
    ('P30', 'CU18', 'Registrar un resultado vacío', '/estudios/resultado', 'Estado "Con resultado" sin texto', 'Falta el resultado',
     '"Escriba el resultado del estudio"', 'Abrir un estudio solicitado y guardar sin resultado', cu18_resultado_vacio),
    ('P31', 'CU19', 'Paciente consulta su historial', '/portal/historial', 'Sesión de jperez', 'Rol paciente',
     'Ve su historial y resultados; no accede al panel interno', 'Abrir Historial médico y luego /panel', cu19_portal),
    ('P32', 'CU20', 'Exportar un reporte a CSV', '/reportes', 'Reporte por médico · Exportar CSV', 'Sesión de administrador',
     'Descarga un archivo CSV con totales', 'Elegir el reporte y pulsar Exportar CSV', cu20_csv),
    ('P33', 'CU20', 'Médico intenta abrir los reportes', '/reportes', 'Sesión de rsuarez', 'El rol no tiene reportes.ver',
     'HTTP 403 Acceso denegado', 'Ingresar como médico y abrir /reportes', cu20_permiso),
]


def main():
    resultados = []
    for pid, cu, nombre, interfaz, entrada, condicion, esperada, proc, fn in PRUEBAS:
        try:
            ok, obtenido = fn()
        except Exception as e:   # una prueba fallida no detiene las demás
            ok, obtenido = False, f'Error: {e}'
        resultados.append(dict(id=pid, cu=cu, prueba=nombre, interfaz=interfaz, entrada=entrada, condicion=condicion,
                               esperada=esperada, procedimiento=proc, obtenido=obtenido, ok=ok))
        print(('✔' if ok else '✘'), pid, cu, nombre, '→', obtenido)
    salida = Path(__file__).with_name('resultados.json')
    salida.write_text(json.dumps({'fecha': dt.datetime.now().strftime('%d/%m/%Y %H:%M'), 'base': BASE,
                                  'pruebas': resultados}, ensure_ascii=False, indent=1), encoding='utf-8')
    print(f'\n{sum(r["ok"] for r in resultados)}/{len(resultados)} pruebas correctas · {salida.name}')
    # Limpieza: el paciente de prueba se desactiva (no se elimina, RN11); su cita ya quedó cancelada en P24
    if 'paciente' in estado:
        r = Sesion('mrojas').post('/pacientes/estado', origen=f'/pacientes/ver?id={estado["paciente"]}', id=estado['paciente'])
        print('Limpieza:', avisos(r))


if __name__ == '__main__':
    main()
