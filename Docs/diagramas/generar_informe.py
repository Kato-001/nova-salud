# Genera los diagramas PlantUML agregados para el informe final (estructura por ciclos):
# modelo de dominio, actividad de atención, casos de uso y paquetes por ciclo,
# clases de análisis por ciclo, clases persistentes y componentes por paquete.
# Uso: python generar_informe.py  (luego renderizar con plantuml)
import re
from pathlib import Path

AQUI = Path(__file__).parent


def escribir(nombre, cuerpo):
    # "class X { a\n b }" en una línea -> un atributo por línea
    cuerpo = re.sub(r'^(class \S+) \{ (.*) \}$',
                    lambda m: m.group(1) + ' {\n  ' + m.group(2).replace('\\n ', '\n  ') + '\n}',
                    cuerpo, flags=re.M)
    (AQUI / f'{nombre}.puml').write_text(f'@startuml {nombre}\n!include estilo.iuml\n{cuerpo.strip()}\n@enduml\n', encoding='utf-8')


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
       'P4': 'P4 Gestión de citas', 'P5': 'P5 Atención médica', 'P6': 'P6 Reportes'}

# ------------------------------------------------------------------ Modelo de dominio
escribir('05_modelo_dominio', '''
title Modelo de dominio · Clínica Vida Salud
hide circle
hide methods
skinparam linetype ortho
class Paciente { ci\\n nombres\\n apellidos\\n fechaNacimiento\\n sexo\\n teléfono }
class HistoriaClinica { antecedentes\\n alergias\\n enfermedadesCrónicas }
class ContactoEmergencia { nombre\\n parentesco\\n teléfono }
class Medico { registroProfesional\\n nombres\\n apellidos }
class Especialidad { nombre\\n descripción }
class Horario { díaSemana\\n horaInicio\\n horaFin }
class BloqueoAgenda { fecha\\n tipo\\n motivo }
class Cita { fecha\\n horaInicio\\n horaFin\\n modalidad\\n motivo }
class EstadoCita { nombre\\n ocupaHorario }
class HistorialCita { acción\\n motivo\\n fecha }
class Consulta { diagnóstico\\n signosVitales\\n observaciones }
class Tratamiento { dosis\\n frecuencia\\n duración }
class Medicamento { nombre\\n presentación }
class Estudio { indicaciones\\n estado\\n resultado }
class CatalogoEstudio { nombre\\n categoría }
class Usuario { usuario\\n correo\\n contraseña }
class Rol { nombre }
class Recepcionista
class Administrador

Paciente "1" -- "1" HistoriaClinica : tiene
Paciente "1" -- "0..*" ContactoEmergencia : registra
Paciente "1" -- "0..*" Cita : solicita
Medico "1..*" -- "1..*" Especialidad : atiende
Medico "1" -- "1..*" Horario : trabaja en
Medico "1" -- "0..*" BloqueoAgenda : no atiende en
Medico "1" -- "0..*" Cita : atiende
Cita "0..*" -- "1" Especialidad : de
Cita "0..*" -- "1" EstadoCita : está en
Cita "1" -- "1..*" HistorialCita : registra cambios
Cita "1" -- "0..1" Consulta : genera
Consulta "1" -- "0..*" Tratamiento : indica
Tratamiento "0..*" -- "1" Medicamento : usa
Consulta "1" -- "0..*" Estudio : solicita
Estudio "0..*" -- "1" CatalogoEstudio : es de tipo
Recepcionista "1" -- "0..*" Cita : programa
Usuario "0..*" -- "1" Rol : tiene
Usuario "0..1" -- "0..1" Paciente : accede como
Usuario "0..1" -- "0..1" Medico : accede como
Administrador -- Medico : administra
''')

# ------------------------------------------------------------------ Actividad: atención médica
escribir('06_act_atencion', '''
title Modelo de negocio · Atención médica, tratamiento y estudios
|Paciente|
start
:Llega a la clínica a la hora de su cita;
|Recepcionista|
:Busca la cita del día;
if (¿El paciente se presentó?) then (sí)
  :Marca la cita como atendida\\no la deriva al médico;
else (no)
  :Registra la inasistencia;
  stop
endif
|Médico|
:Revisa el historial clínico\\ny las citas anteriores;
:Registra motivo, signos vitales\\ny examen físico;
:Registra el diagnóstico;
if (¿Requiere tratamiento?) then (sí)
  :Indica medicamentos, dosis,\\nfrecuencia y duración;
endif
if (¿Requiere estudios?) then (sí)
  :Solicita estudios del catálogo;
  |Recepcionista|
  :Registra el resultado\\ncuando llega del laboratorio;
  |Paciente|
  :Consulta el resultado en el portal;
endif
|Médico|
:Guarda la consulta\\n(la cita queda ATENDIDA);
stop
''')


# ------------------------------------------------------------------ Casos de uso por ciclo
def cu_ciclo(nombre, titulo, ids, actores, enlaces):
    paquetes = {}
    for i in ids:
        paquetes.setdefault(CU[i][1], []).append(i)
    cuerpo = [f'title {titulo}', 'left to right direction', 'skinparam packageStyle rectangle']
    cuerpo += [f'actor "{a}" as {k}' for k, a in actores]
    cuerpo.append('rectangle "Nova Salud" {')
    for p, lista in paquetes.items():
        cuerpo.append(f'  package "{PAQ[p]}" {{')
        cuerpo += [f'    usecase "CU{i} {CU[i][0]}" as CU{i}' for i in lista]
        cuerpo.append('  }')
    cuerpo.append('}')
    cuerpo += enlaces
    escribir(nombre, '\n'.join(cuerpo))


cu_ciclo('10a_cu_ciclo1', 'Ciclo I · Actores y casos de uso (CU1 a CU7)', range(1, 8),
         [('ADM', 'Administrador'), ('REC', 'Recepcionista'), ('MED', 'Médico'), ('PAC', 'Paciente')],
         ['ADM --> CU1', 'REC --> CU1', 'MED --> CU1', 'PAC --> CU1', 'ADM --> CU2', 'ADM --> CU3',
          'ADM --> CU4', 'ADM --> CU5', 'REC --> CU5', 'ADM --> CU6', 'REC --> CU7', 'ADM --> CU7'])
cu_ciclo('10b_cu_ciclo2', 'Ciclo II · Actores y casos de uso (CU8 a CU20)', range(8, 21),
         [('PAC', 'Paciente'), ('REC', 'Recepcionista'), ('MED', 'Médico'), ('ADM', 'Administrador')],
         ['PAC --> CU8', 'PAC --> CU9', 'REC --> CU9', 'PAC --> CU10', 'REC --> CU10', 'REC --> CU11',
          'PAC --> CU12', 'REC --> CU12', 'REC --> CU13', 'MED --> CU13', 'MED --> CU14', 'REC --> CU14',
          'MED --> CU15', 'MED --> CU16', 'REC --> CU16', 'MED --> CU17', 'MED --> CU18', 'REC --> CU18',
          'PAC --> CU19', 'ADM --> CU20', 'REC --> CU20', 'ADM --> CU14',
          'CU10 ..> CU9 : <<include>>', 'CU11 ..> CU9 : <<include>>', 'CU17 ..> CU15 : <<extend>>', 'CU18 ..> CU15 : <<extend>>'])

# ------------------------------------------------------------------ Paquetes del Ciclo I
escribir('21a_paquetes_ciclo1', '''
title Ciclo I · Vista de paquetes de análisis
skinparam packageStyle folder
package "P3 Gestión de pacientes" as P3 {
}
package "P2 Gestión médica" as P2 {
}
package "P1 Seguridad y acceso" as P1 {
}
P2 ..> P1 : <<use>>
P3 ..> P1 : <<use>>
note right of P1
  Servicio común: autenticación,
  permisos por rol y bitácora
end note
note bottom of P2
  Datos maestros para el Ciclo II:
  especialidades, médicos y horarios
end note
''')


# ------------------------------------------------------------------ Paquetes y casos de uso
def paq_cu(nombre, titulo, paquetes):
    cuerpo = [f'title {titulo}', 'skinparam packageStyle folder', 'left to right direction']
    for p in paquetes:
        cuerpo.append(f'package "{PAQ[p]}" as {p} {{')
        cuerpo += [f'  usecase "CU{i} {n}" as CU{i}' for i, (n, q) in CU.items() if q == p]
        cuerpo.append('}')
    escribir(nombre, '\n'.join(cuerpo))


paq_cu('25_paq_cu_ciclo1', 'Ciclo I · Relación entre paquetes y casos de uso', ['P1', 'P2', 'P3'])
paq_cu('25_paq_cu_ciclo2', 'Ciclo II · Relación entre paquetes y casos de uso', ['P3', 'P4', 'P5', 'P6'])

# ------------------------------------------------------------------ Clases de análisis por ciclo
escribir('22a_clases_ciclo1', '''
title Ciclo I · Diagrama de clases de análisis (P1, P2 y P3)
left to right direction
hide empty members
boundary IU_Login
boundary IU_Usuarios
boundary IU_Roles
boundary IU_Especialidades
boundary IU_Medicos
boundary IU_Horarios
boundary IU_Pacientes
control CtrlSesion
control CtrlUsuario
control CtrlRol
control CtrlEspecialidad
control CtrlMedico
control CtrlHorario
control CtrlPaciente
entity Usuario
entity Rol
entity Permiso
entity Bitacora
entity Especialidad
entity Medico
entity Horario
entity BloqueoAgenda
entity Paciente
entity HistoriaClinica
entity ContactoEmergencia
IU_Login --> CtrlSesion
IU_Usuarios --> CtrlUsuario
IU_Roles --> CtrlRol
IU_Especialidades --> CtrlEspecialidad
IU_Medicos --> CtrlMedico
IU_Horarios --> CtrlHorario
IU_Pacientes --> CtrlPaciente
CtrlSesion --> Usuario
CtrlSesion --> Bitacora
CtrlUsuario --> Usuario
CtrlUsuario --> Rol
CtrlRol --> Rol
CtrlRol --> Permiso
CtrlEspecialidad --> Especialidad
CtrlMedico --> Medico
CtrlMedico --> Especialidad
CtrlMedico --> Usuario
CtrlHorario --> Horario
CtrlHorario --> BloqueoAgenda
CtrlPaciente --> Paciente
CtrlPaciente --> HistoriaClinica
CtrlPaciente --> ContactoEmergencia
''')
escribir('22b_clases_ciclo2', '''
title Ciclo II · Diagrama de clases de análisis (P3 a P6 y portal)
left to right direction
hide empty members
boundary IU_Registro
boundary IU_ProgramarCita
boundary IU_DetalleCita
boundary IU_Reprogramar
boundary IU_Agenda
boundary IU_Consulta
boundary IU_Estudios
boundary IU_Portal
boundary IU_Reportes
control CtrlRegistro
control CtrlDisponibilidad
control CtrlCita
control CtrlAgenda
control CtrlAtencion
control CtrlEstudio
control CtrlPortal
control CtrlReporte
entity Paciente
entity Medico
entity Horario
entity Cita
entity EstadoCita
entity HistorialCita
entity Notificacion
entity Consulta
entity Tratamiento
entity Medicamento
entity Estudio
entity CatalogoEstudio
IU_Registro --> CtrlRegistro
IU_ProgramarCita --> CtrlDisponibilidad
IU_ProgramarCita --> CtrlCita
IU_DetalleCita --> CtrlCita
IU_Reprogramar --> CtrlCita
IU_Agenda --> CtrlAgenda
IU_Consulta --> CtrlAtencion
IU_Estudios --> CtrlEstudio
IU_Portal --> CtrlPortal
IU_Reportes --> CtrlReporte
CtrlRegistro --> Paciente
CtrlDisponibilidad --> Horario
CtrlDisponibilidad --> Cita
CtrlCita --> CtrlDisponibilidad
CtrlCita --> Cita
CtrlCita --> EstadoCita
CtrlCita --> HistorialCita
CtrlCita --> Notificacion
CtrlAgenda --> Cita
CtrlAgenda --> Medico
CtrlAtencion --> Consulta
CtrlAtencion --> Tratamiento
CtrlAtencion --> Medicamento
CtrlAtencion --> Cita
CtrlEstudio --> Estudio
CtrlEstudio --> CatalogoEstudio
CtrlPortal --> CtrlCita
CtrlPortal --> Paciente
CtrlReporte --> Cita
CtrlReporte --> Paciente
''')


# ------------------------------------------------------------------ Componentes por paquete
def componentes(nombre, titulo, capas, enlaces, tablas):
    """capas: [(título, [(alias, etiqueta), ...]), ...]; enlaces: ['A --> B', ...] con los alias."""
    c = [f'title {titulo}', 'left to right direction']
    for titulo_capa, lista in capas:
        c.append(f'package "{titulo_capa}" {{')
        c += [f'  component "{etq}" as {a}' for a, etq in lista]
        c.append('}')
    c.append('component "Database (PDO)" as PDO')
    c.append(f'database "PostgreSQL\\n{tablas}" as DB')
    c += enlaces
    c.append('PDO --> DB : pdo_pgsql')
    escribir(nombre, '\n'.join(c))


V, K, S, M = 'Presentación (Frontend/views)', 'Control (Bakend/app/controllers)', 'Lógica de negocio (Bakend/app/services · core)', 'Acceso a datos (Bakend/app/models)'

componentes('60_comp_p1', 'Componentes del paquete P1 Seguridad y acceso', [
    (V, [('vLogin', 'login.php'), ('vRec', 'recuperar.php\\nrecuperar_codigo.php'), ('vUsu', 'usuarios/*.php'),
         ('vRol', 'roles/index.php'), ('vConf', 'configuracion/index.php'), ('vBit', 'bitacora/index.php')]),
    (K, [('cAuth', 'AuthController'), ('cRec', 'RecuperacionController'), ('cUsu', 'UsuarioController'),
         ('cRol', 'RolController'), ('cConf', 'ConfiguracionController'), ('cBit', 'BitacoraController')]),
    (S, [('sAuth', 'Auth · Session'), ('sMail', 'Mailer (SMTP)')]),
    (M, [('mUsu', 'Usuario'), ('mRec', 'Recuperacion'), ('mRol', 'Rol'), ('mConf', 'Configuracion'), ('mBit', 'Bitacora')]),
], ['vLogin --> cAuth', 'vRec --> cRec', 'vUsu --> cUsu', 'vRol --> cRol', 'vConf --> cConf', 'vBit --> cBit',
    'cAuth --> sAuth', 'sAuth --> mUsu', 'sAuth --> mBit', 'cRec --> sMail', 'cRec --> mRec', 'cRec --> mUsu',
    'cUsu --> mUsu', 'cRol --> mRol', 'cConf --> mConf', 'cBit --> mBit',
    'mUsu --> PDO', 'mRec --> PDO', 'mRol --> PDO', 'mConf --> PDO', 'mBit --> PDO'],
    'usuario, rol, permiso,\\nrolpermiso, bitacora,\\nconfiguracion, recuperacion, sesion')

componentes('60_comp_p2', 'Componentes del paquete P2 Gestión médica', [
    (V, [('vEsp', 'especialidades/index.php'), ('vMed', 'medicos/index.php\\nmedicos/form.php\\nmedicos/ver.php'), ('vHor', 'horarios/index.php')]),
    (K, [('cEsp', 'EspecialidadController'), ('cMed', 'MedicoController'), ('cHor', 'HorarioController')]),
    (M, [('mEsp', 'Especialidad'), ('mMed', 'Medico'), ('mHor', 'Horario'), ('mUsu', 'Usuario')]),
], ['vEsp --> cEsp', 'vMed --> cMed', 'vHor --> cHor', 'cEsp --> mEsp', 'cMed --> mMed', 'cMed --> mEsp',
    'cMed --> mUsu', 'cHor --> mHor', 'mEsp --> PDO', 'mMed --> PDO', 'mHor --> PDO', 'mUsu --> PDO'],
    'especialidad, medico,\\nmedicoespecialidad,\\nhorario, bloqueoagenda')

componentes('60_comp_p3', 'Componentes del paquete P3 Gestión de pacientes', [
    (V, [('vLis', 'pacientes/index.php'), ('vFic', 'pacientes/form.php\\npacientes/ver.php'), ('vReg', 'registro.php')]),
    (K, [('cPac', 'PacienteController'), ('cAuth', 'AuthController\\n(registro)')]),
    (M, [('mPac', 'Paciente'), ('mUsu', 'Usuario')]),
], ['vLis --> cPac', 'vFic --> cPac', 'vReg --> cAuth', 'cPac --> mPac', 'cAuth --> mPac', 'cAuth --> mUsu',
    'mPac --> PDO', 'mUsu --> PDO'],
    'paciente, historiaclinica,\\ncontactoemergencia, usuario')

componentes('60_comp_p4', 'Componentes del paquete P4 Gestión de citas', [
    (V, [('vNue', 'citas/nueva.php\\n+ citas.js'), ('vVer', 'citas/index.php\\ncitas/ver.php'), ('vRep', 'citas/reprogramar.php'), ('vAge', 'agenda/index.php')]),
    (K, [('cCit', 'CitaController'), ('cApi', 'ApiController (JSON)'), ('cAge', 'AgendaController')]),
    (S, [('sCit', 'CitaService'), ('sDis', 'Disponibilidad')]),
    (M, [('mCit', 'Cita'), ('mHor', 'Horario'), ('mNot', 'Notificacion')]),
], ['vNue --> cCit', 'vNue ..> cApi : fetch', 'vVer --> cCit', 'vRep --> cCit', 'vAge --> cAge',
    'cCit --> sCit', 'cApi --> sDis', 'cAge --> sDis', 'cAge --> mCit', 'sCit --> sDis', 'sCit --> mCit', 'sCit --> mNot',
    'sDis --> mHor', 'sDis --> mCit', 'mCit --> PDO', 'mHor --> PDO', 'mNot --> PDO'],
    'cita, estadocita,\\nhistorialcita, notificacion\\n(trigger validar_cita)')

componentes('60_comp_p5', 'Componentes del paquete P5 Atención médica', [
    (V, [('vCon', 'consulta.php'), ('vEst', 'estudios/index.php'), ('vRes', 'estudios/resultado.php'), ('vCat', 'estudios/catalogo.php')]),
    (K, [('cAte', 'AtencionController'), ('cEst', 'EstudioController')]),
    (M, [('mCon', 'Consulta'), ('mEst', 'Estudio'), ('mCit', 'Cita')]),
], ['vCon --> cAte', 'vEst --> cEst', 'vRes --> cEst', 'vCat --> cEst', 'cAte --> mCon', 'cAte --> mCit',
    'cAte --> mEst', 'cEst --> mEst', 'mCon --> PDO', 'mEst --> PDO', 'mCit --> PDO'],
    'consulta, tratamiento,\\nmedicamento, estudio,\\ncatalogoestudio')

componentes('60_comp_p6', 'Componentes del paquete P6 Reportes', [
    (V, [('vRep', 'reportes/index.php\\n(CSV · imprimir PDF)')]),
    (K, [('cRep', 'ReporteController')]),
    (M, [('mRep', 'Reporte')]),
], ['vRep --> cRep', 'cRep --> mRep', 'mRep --> PDO'],
    'cita, paciente, medico,\\nespecialidad (consultas)')

componentes('60_comp_portal', 'Componentes del Portal del paciente', [
    (V, [('vIni', 'Portal/inicio.php'), ('vRes', 'Portal/reservar.php'), ('vCit', 'Portal/citas.php'), ('vPer', 'Portal/perfil.php'), ('vHis', 'Portal/historial.php')]),
    (K, [('cPor', 'PortalController')]),
    (S, [('sCit', 'CitaService')]),
    (M, [('mPac', 'Paciente'), ('mCit', 'Cita'), ('mCon', 'Consulta'), ('mEst', 'Estudio'), ('mNot', 'Notificacion')]),
], ['vIni --> cPor', 'vRes --> cPor', 'vCit --> cPor', 'vPer --> cPor', 'vHis --> cPor', 'cPor --> sCit',
    'cPor --> mPac', 'cPor --> mCon', 'cPor --> mEst', 'cPor --> mNot', 'sCit --> mCit',
    'mPac --> PDO', 'mCit --> PDO', 'mCon --> PDO', 'mEst --> PDO', 'mNot --> PDO'],
    'paciente, cita, consulta,\\nestudio, notificacion')
print('Diagramas generados')

# ------------------------------------------------------------------ Clases persistentes (diseño lógico)
escribir('47_clases_persistentes', '''
title Diseño de datos lógico · Diagrama de clases persistentes
hide circle
hide methods
skinparam linetype ortho
skinparam classAttributeFontSize 11
class Rol { idRol : int\\n nombre : String\\n descripcion : String }
class Permiso { idPermiso : int\\n codigo : String\\n nombre : String\\n modulo : String }
class Usuario { idUsuario : int\\n nombre : String\\n username : String\\n email : String\\n password : String\\n estado : String\\n ultimoAcceso : DateTime }
class Bitacora { idBitacora : int\\n accion : String\\n modulo : String\\n descripcion : String\\n ip : String\\n fecha : DateTime }
class Especialidad { idEspecialidad : int\\n nombre : String\\n descripcion : String\\n estado : String }
class Medico { idMedico : int\\n codigo : String\\n registroProfesional : String\\n nombres : String\\n apellidos : String\\n telefono : String\\n estado : String }
class Horario { idHorario : int\\n diaSemana : int\\n horaInicio : Time\\n horaFin : Time\\n estado : String }
class BloqueoAgenda { idBloqueo : int\\n fecha : Date\\n horaInicio : Time\\n horaFin : Time\\n tipo : String }
class Paciente { idPaciente : int\\n codigo : String\\n ci : String\\n nombres : String\\n apellidos : String\\n fechaNacimiento : Date\\n sexo : char\\n telefono : String\\n tipoSangre : String\\n estado : String }
class HistoriaClinica { idHistoria : int\\n numero : String\\n antecedentes : String\\n alergias : String }
class ContactoEmergencia { idContacto : int\\n nombre : String\\n parentesco : String\\n telefono : String }
class EstadoCita { codigo : String\\n nombre : String\\n ocupaHorario : boolean }
class Cita { idCita : int\\n codigo : String\\n fecha : Date\\n horaInicio : Time\\n horaFin : Time\\n modalidad : String\\n motivo : String }
class HistorialCita { idHistorial : int\\n accion : String\\n fechaAnterior : Date\\n fechaNueva : Date\\n motivo : String }
class Notificacion { idNotificacion : int\\n tipo : String\\n canal : String\\n mensaje : String }
class Consulta { idConsulta : int\\n fechaHora : DateTime\\n peso : decimal\\n talla : decimal\\n diagnostico : String }
class Tratamiento { idTratamiento : int\\n dosis : String\\n frecuencia : String\\n duracion : String }
class Medicamento { idMedicamento : int\\n nombre : String\\n presentacion : String }
class Estudio { idEstudio : int\\n estado : String\\n fechaSolicitud : DateTime\\n resultado : String }
class CatalogoEstudio { idCatalogo : int\\n nombre : String\\n categoria : String }

Rol "1..*" -- "1..*" Permiso
Usuario "0..*" -- "1" Rol
Usuario "1" -- "0..*" Bitacora
Medico "1" -- "1" Usuario
Medico "1..*" -- "1..*" Especialidad
Medico "1" *-- "1..*" Horario
Medico "1" *-- "0..*" BloqueoAgenda
Paciente "0..1" -- "0..1" Usuario
Paciente "1" *-- "1" HistoriaClinica
Paciente "1" *-- "0..*" ContactoEmergencia
Cita "0..*" -- "1" Paciente
Cita "0..*" -- "1" Medico
Cita "0..*" -- "1" Especialidad
Cita "0..*" -- "1" EstadoCita
Cita "1" *-- "1..*" HistorialCita
Cita "1" -- "0..*" Notificacion
Cita "1" -- "0..1" Consulta
Consulta "1" *-- "0..*" Tratamiento
Tratamiento "0..*" -- "1" Medicamento
Consulta "1" *-- "0..*" Estudio
Estudio "0..*" -- "1" CatalogoEstudio
''')


