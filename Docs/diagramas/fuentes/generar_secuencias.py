"""
Genera los diagramas de secuencia (diseño) de los 20 casos de uso de Nova Salud.
Los participantes y mensajes corresponden a las clases reales de app/.
Uso:  python generar_secuencias.py
"""
from pathlib import Path

ENCABEZADO = """@startuml 50_sec_cu{cod}
!include estilo.iuml
title Diagrama de secuencia · CU{num} {nombre}
autonumber "<b>0."
hide footbox
"""

SEC = {
"01": ("Iniciar sesión", """
actor Usuario as U
boundary "login.php" as V
participant Router as R
control AuthController as AC
control Auth as A
entity "usuario / rol\\n/ permiso" as DB
entity Bitacora as B
U -> V : escribe usuario/correo y contraseña
V -> R : POST /login (token CSRF)
R -> R : Csrf::verificar()
R -> AC : ingresar()
AC -> AC : Validator: campos obligatorios
AC -> A : intentar(login, clave)
A -> B : contar LOGIN_FALLIDO de la IP (10 min)
alt 5 o más intentos
  A --> AC : AppException("Demasiados intentos")
else
  A -> DB : SELECT por username, email o CI
  A -> A : password_verify(clave, hash)
  alt credenciales inválidas
    A -> B : registrar(LOGIN_FALLIDO)
    A --> AC : AppException("Usuario o contraseña incorrectos")
  else cuenta INACTIVA
    A --> AC : AppException("Cuenta desactivada")
  else correcto
    A -> A : session_regenerate_id(true)
    A -> DB : UPDATE ultimoacceso
    A -> B : registrar(INICIAR_SESION)
    A --> AC : usuario + rol + permisos
  end
end
AC --> U : 302 a /panel o /portal\\n(o /login con mensaje)
"""),
"02": ("Gestionar roles y permisos", """
actor Administrador as U
boundary "roles/index.php" as V
control RolController as C
entity Rol as R
entity "permiso /\\nrolpermiso" as P
entity Bitacora as B
U -> V : abre Roles y permisos
V -> C : index()
C -> R : todos()
C -> P : permisos() y matrizPermisos()
C --> V : roles + matriz
U -> V : marca permisos de un rol y Guardar
V -> C : POST /roles/permisos (idrol, permisos[])
C -> C : Auth::requerir(roles.gestionar)
opt rol ADMINISTRADOR
  C -> C : agrega permisos fijos (usuarios, roles)
end
C -> P : asignarPermisos(idrol, ids) en transacción
C -> B : registrar(PERMISOS)
C --> U : "Permisos guardados"
"""),
"03": ("Gestionar usuarios", """
actor Administrador as U
boundary "usuarios/form.php" as V
control UsuarioController as C
participant Validator as VA
entity Usuario as M
entity Bitacora as B
U -> V : completa nombre, usuario, correo, rol, contraseña
V -> V : validación JavaScript
V -> C : POST /usuarios/guardar
C -> VA : requerido, regex, email, password, coincide
C -> M : existeUsername() / existeEmail()
alt datos inválidos
  C --> V : redirige con errores por campo
else válidos
  C -> M : crear(password_hash)
  C -> B : registrar(CREAR)
  C --> U : "Usuario creado"
end
U -> C : POST /usuarios/estado (desactivar)
C -> M : cambiarEstado(INACTIVO)
M -> M : elimina sus sesiones abiertas
C -> B : registrar(DESACTIVAR)
"""),
"04": ("Gestionar especialidades", """
actor Administrador as U
boundary "especialidades/index.php" as V
control EspecialidadController as C
entity Especialidad as E
entity Bitacora as B
U -> V : nombre y descripción
V -> C : POST /especialidades/guardar
C -> E : existeNombre(normalizar(nombre))
alt ya existe
  C --> V : "Ya existe una especialidad con ese nombre"
else nueva
  C -> E : guardar(nombre en Título)
  C -> B : registrar(CREAR)
  C --> V : "Especialidad registrada"
end
U -> C : POST /especialidades/estado
C -> E : cambiarEstado()
"""),
"05": ("Gestionar médicos", """
actor Administrador as U
boundary "medicos/form.php" as V
control MedicoController as C
participant Validator as VA
entity Medico as M
entity Usuario as US
entity MedicoEspecialidad as ME
U -> V : datos, especialidades y cuenta
V -> C : POST /medicos/guardar
C -> VA : código, registro, nombres, correo,\\nespecialidades, usuario, contraseña
C -> M : existe(código / registro)
C -> US : existeUsername / existeEmail
group transacción
  C -> US : crear(rol MEDICO, password_hash)
  C -> M : crear()
  C -> ME : asignarEspecialidades()
end
C --> U : redirige a definir horarios
"""),
"06": ("Gestionar horarios", """
actor Administrador as U
boundary "horarios/index.php" as V
control HorarioController as C
entity Horario as H
entity BloqueoAgenda as BA
entity Cita as CI
U -> V : día, hora inicial, hora final
V -> C : POST /horarios/guardar
C -> C : fin > inicio y cubre al menos una cita
C -> H : seCruza(médico, día, inicio, fin)
alt se cruza
  C --> V : "Se cruza con otro horario activo"
else
  C -> H : crear()
end
U -> V : bloquear fecha (feriado, permiso…)
V -> C : POST /bloqueos/guardar
C -> CI : citasEnBloqueo()
C -> BA : crearBloqueo()
C --> V : aviso si hay citas afectadas
"""),
"07": ("Gestionar pacientes", """
actor Recepcionista as U
boundary "pacientes/form.php" as V
control PacienteController as C
participant Validator as VA
entity Paciente as P
entity HistoriaClinica as H
U -> V : CI, nombres, nacimiento, sexo, teléfono…
V -> C : POST /pacientes/guardar
C -> VA : validador(datos)
C -> P : existeCi(ci)
alt CI duplicado
  C --> V : "Ya existe un paciente con ese CI"
else
  group transacción
    C -> P : crear(código PAC-####)
    C -> H : crear(número HC-####)
  end
  C --> U : ficha del paciente
end
U -> C : GET /pacientes/ver (historial)
C -> P : buscar() + Cita::delPaciente()
"""),
"08": ("Registrarse como paciente", """
actor Paciente as U
boundary "registro.php" as V
control AuthController as C
participant Validator as VA
entity Usuario as US
entity Paciente as P
control Auth as A
U -> V : datos personales y de la cuenta
V -> C : POST /registro
C -> VA : validador del paciente + usuario,\\ncontraseña y aceptación
alt inválido
  C --> V : errores por campo
else válido
  group transacción
    C -> US : crear(rol PACIENTE)
    C -> P : crear() + historia clínica
  end
  C -> A : intentar(usuario, clave)
  C --> U : portal del paciente
end
"""),
"09": ("Consultar disponibilidad", """
actor Recepcionista as U
boundary "citas.js" as JS
control ApiController as API
control Disponibilidad as D
entity Configuracion as CF
entity "medico /\\nhorario" as H
entity BloqueoAgenda as BA
entity Cita as CI
U -> JS : elige médico y fecha
JS -> API : GET /api/disponibilidad?medico&fecha
API -> D : bloques(médico, fecha)
D -> CF : DURACION_CITA_MINUTOS
D -> D : fecha >= hoy y <= DIAS_MAX_RESERVA
D -> H : médico activo y horarios del día
D -> BA : bloqueos del día
D -> CI : citas que ocupan horario
loop cada bloque de N minutos
  D -> D : libre = no pasado, sin cita, sin bloqueo
end
D --> API : bloques [inicio, fin, libre]
API --> JS : JSON
JS --> U : solo se habilitan los libres
"""),
"10": ("Reservar ficha", """
actor Recepcionista as U
boundary "citas/nueva.php\\n+ citas.js" as V
control CitaController as CC
control CitaService as CS
control Disponibilidad as D
database PostgreSQL as DB
U -> V : paciente, especialidad, médico, fecha y hora libre
V -> CC : POST /citas/guardar
CC -> CS : programar(datos)
CS -> CS : Validator (ids, fecha >= hoy, hora)
CS -> DB : BEGIN
CS -> DB : SELECT medico FOR UPDATE
CS -> DB : paciente activo y médico atiende especialidad
CS -> D : estaLibre(médico, fecha, hora)
alt ocupado o fuera de horario
  CS -> DB : ROLLBACK
  CS --> CC : AppException(motivo)
  CC --> V : formulario con el mensaje
else libre
  CS -> DB : INSERT cita (trigger validar_cita\\n+ restricción EXCLUDE)
  CS -> DB : INSERT historialcita (PROGRAMAR)
  CS -> DB : INSERT notificacion (aviso + recordatorio)
  CS -> DB : COMMIT
  CS -> DB : INSERT bitacora (CREAR)
  CC --> U : "Cita CM-#### programada"
end
"""),
"11": ("Reprogramar cita", """
actor Recepcionista as U
boundary "citas/reprogramar.php" as V
control CitaController as CC
control CitaService as CS
control Disponibilidad as D
database PostgreSQL as DB
U -> V : nueva fecha, hora y motivo
V -> CC : POST /citas/reprogramar
CC -> CS : reprogramar(cita, fecha, hora, motivo)
CS -> DB : BEGIN y SELECT cita FOR UPDATE
alt estado no es PROGRAMADA ni CONFIRMADA
  CS --> CC : "registre una cita nueva"
else
  CS -> DB : SELECT medico FOR UPDATE
  CS -> D : estaLibre(..., excluir = cita)
  CS -> DB : UPDATE cita (fecha, hora, PROGRAMADA)
  CS -> DB : INSERT historialcita (anterior → nueva)
  CS -> DB : reemplaza recordatorio
  CS -> DB : COMMIT
end
CC --> U : detalle de la cita
"""),
"12": ("Cancelar cita", """
actor Recepcionista as U
boundary "citas/ver.php\\n(modal con motivo)" as V
control CitaController as CC
control CitaService as CS
database PostgreSQL as DB
U -> V : Cancelar cita + motivo
V -> CC : POST /citas/cancelar
CC -> CS : cancelar(cita, motivo)
CS -> DB : BEGIN y SELECT cita FOR UPDATE
alt estado final (atendida, cancelada, inasistencia)
  CS --> CC : AppException("no se puede cancelar")
else
  CS -> DB : UPDATE cita SET CANCELADA
  CS -> DB : INSERT historialcita (CANCELAR, motivo)
  CS -> DB : DELETE recordatorios + INSERT aviso
  CS -> DB : COMMIT
  note right : la cita no se borra y\\ndeja de ocupar el horario
end
CC --> U : detalle con historial
"""),
"13": ("Registrar asistencia", """
actor Recepcionista as U
boundary "citas/ver.php" as V
control CitaController as CC
control CitaService as CS
database PostgreSQL as DB
U -> V : Confirmar / Atendida / No asistió
V -> CC : POST /citas/estado
CC -> CS : cambiarEstado(cita, nuevo)
CS -> DB : SELECT cita FOR UPDATE
CS -> CS : validarTransicion()\\n(TRANSICIONES y fecha <= hoy)
alt transición no permitida
  CS --> CC : AppException
else
  CS -> DB : UPDATE codigoestado
  CS -> DB : INSERT historialcita
  CS -> DB : INSERT bitacora
end
CC --> U : mensaje y nuevo estado
"""),
"14": ("Consultar agenda médica", """
actor Médico as U
boundary "agenda/index.php" as V
control AgendaController as C
control Auth as A
entity Cita as CI
control Disponibilidad as D
U -> V : elige fecha (y estado)
V -> C : GET /agenda?fecha
C -> A : requerir(agenda.ver | agenda.propia)
alt médico (solo agenda propia)
  C -> C : idmedico = médico de la sesión
else recepción / administrador
  C -> C : idmedico = filtro elegido
end
C -> CI : agenda(fecha, médico, estado)
C -> D : bloques(médico, fecha)
C --> V : citas intercaladas con bloques libres
"""),
"15": ("Registrar consulta médica", """
actor Médico as U
boundary "consulta.php" as V
control AtencionController as C
control CitaService as CS
entity Consulta as CO
database PostgreSQL as DB
U -> V : signos vitales, diagnóstico,\\ntratamientos y estudios
V -> C : POST /atencion/guardar
C -> C : citaDelMedico() y fecha <= hoy
C -> C : Validator (diagnóstico, rangos, PA 120/80)
C -> CS : atender(cita, consulta, tratamientos, estudios)
group transacción
  CS -> DB : SELECT cita FOR UPDATE + validarTransicion
  CS -> CO : crear(consulta, tratamientos, estudios)
  CS -> DB : UPDATE cita SET ATENDIDA
  CS -> DB : INSERT historialcita (ATENDER)
end
C --> U : "Consulta registrada"
"""),
"16": ("Gestionar historial clínico", """
actor Médico as U
boundary "pacientes/ver.php\\n(tab Historia clínica)" as V
control PacienteController as C
entity Paciente as P
entity "Consulta /\\nTratamiento / Estudio" as CO
entity HistoriaClinica as H
U -> V : abre la historia del paciente
V -> C : ver(id, tab=clinica)
C -> P : buscar(id)
C -> CO : consultas(paciente) con tratamientos y estudios
C --> V : antecedentes + consultas
U -> V : actualiza antecedentes / alergias
V -> C : POST /pacientes/historia
C -> H : actualizarHistoria()
C --> U : "Historia clínica actualizada"
"""),
"17": ("Registrar tratamiento", """
actor Médico as U
boundary "consulta.php" as V
control AtencionController as C
entity Medicamento as MD
entity Consulta as CO
entity Tratamiento as T
V -> MD : medicamentos activos
U -> V : + Agregar medicamento\\n(dosis, frecuencia, duración)
V -> C : POST /atencion/guardar (tratamientos[])
loop cada fila
  C -> C : medicamento válido y campos completos
end
C -> CO : crear(consulta)
loop cada tratamiento
  CO -> T : INSERT tratamiento
end
"""),
"18": ("Gestionar estudios médicos", """
actor Médico as M
actor Recepcionista as R
boundary "consulta.php /\\nestudios/*.php" as V
control EstudioController as C
entity CatalogoEstudio as CE
entity Estudio as E
entity Notificacion as N
M -> V : solicita estudio en la consulta
V -> CE : catalogo() activos
V -> E : solicitar(consulta, estudio, indicaciones)
R -> V : abre bandeja de estudios
V -> C : index()
C -> E : listar(filtros)
R -> V : registra resultado
V -> C : POST /estudios/resultado
C -> C : Validator (resultado, enlace https)
group transacción
  C -> E : registrarResultado(COMPLETADO)
  C -> N : INSERT RESULTADO (aviso al paciente)
end
"""),
"19": ("Consultar historial y resultados", """
actor Paciente as U
boundary "portal/historial.php" as V
control PortalController as C
entity Paciente as P
entity "Consulta /\\nTratamiento" as CO
entity Estudio as E
U -> V : Historial médico
V -> C : historial()
C -> C : Auth::requerir(portal.paciente)
C -> P : paciente de la sesión
C -> CO : consultas(paciente)
C -> E : delPaciente(paciente)
C --> V : diagnósticos, tratamientos y resultados
note right : solo ve sus propios datos
"""),
"20": ("Generar reportes", """
actor Recepcionista as U
boundary "reportes/index.php" as V
control ReporteController as C
entity Reporte as R
entity Bitacora as B
U -> V : tipo, rango de fechas y filtros
V -> C : GET /reportes?tipo&desde&hasta
C -> C : filtros() (rango máximo 1 año)
alt resumen
  C -> R : resumen(), porEstado(), porDia()
else por médico / especialidad / estado
  C -> R : porMedico() / porEspecialidad() / porEstado()
else agenda diaria
  C -> R : agendaDiaria(día)
end
C --> V : tabla con totales y gráficos
opt Exportar CSV
  U -> C : formato=csv
  C -> B : registrar(EXPORTAR)
  C --> U : archivo .csv
end
"""),
}

dest = Path(__file__).parent
for cod, (nombre, cuerpo) in SEC.items():
    texto = ENCABEZADO.format(cod=cod, num=int(cod), nombre=nombre) + cuerpo.strip() + "\n@enduml\n"
    (dest / f"50_sec_cu{cod}.puml").write_text(texto, encoding="utf-8")
print(len(SEC), "diagramas de secuencia generados")
