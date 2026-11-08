-- =====================================================================
-- Nova Salud · 03 · Datos iniciales y de demostración
-- ---------------------------------------------------------------------
-- Contraseña de todas las cuentas de ejemplo: HeadSalud2026
-- (hash generado con password_hash() de PHP; cámbiela al ingresar)
-- =====================================================================

set search_path = clinica, extensions, public;

-- ------------------------------------------------------------- Roles
insert into rol (nombre, descripcion) values
    ('ADMINISTRADOR', 'Configura y supervisa el sistema: usuarios, médicos, especialidades y reportes'),
    ('RECEPCIONISTA', 'Registra pacientes y programa, reprograma y cancela citas'),
    ('MEDICO',        'Consulta su agenda y registra la atención de sus pacientes'),
    ('PACIENTE',      'Reserva sus citas y consulta su historial desde el portal');

-- ---------------------------------------------------------- Permisos
insert into permiso (codigo, nombre, modulo) values
    ('panel.ver',               'Ver el panel principal',                     'General'),
    ('usuarios.gestionar',      'Administrar usuarios',                        'Seguridad'),
    ('roles.gestionar',         'Administrar roles y permisos',               'Seguridad'),
    ('configuracion.gestionar', 'Modificar la configuración del sistema',     'Seguridad'),
    ('bitacora.ver',            'Consultar la bitácora',                       'Seguridad'),
    ('especialidades.gestionar','Administrar especialidades',                  'Gestión médica'),
    ('medicos.ver',             'Consultar médicos',                           'Gestión médica'),
    ('medicos.gestionar',       'Registrar y modificar médicos',              'Gestión médica'),
    ('horarios.gestionar',      'Definir horarios y bloqueos de agenda',      'Gestión médica'),
    ('pacientes.ver',           'Consultar pacientes y su historial',         'Pacientes'),
    ('pacientes.gestionar',     'Registrar, modificar y desactivar pacientes','Pacientes'),
    ('citas.ver',               'Consultar citas',                             'Citas'),
    ('citas.gestionar',         'Programar, reprogramar y cancelar citas',    'Citas'),
    ('citas.asistencia',        'Confirmar citas y registrar inasistencias',  'Citas'),
    ('agenda.ver',              'Ver la agenda de todos los médicos',         'Citas'),
    ('agenda.propia',           'Ver la agenda propia (médico)',              'Citas'),
    ('consultas.registrar',     'Atender citas y registrar la consulta',      'Atención médica'),
    ('estudios.gestionar',      'Ver estudios solicitados y registrar resultados', 'Atención médica'),
    ('estudios.catalogo',       'Administrar el catálogo de estudios',        'Atención médica'),
    ('reportes.ver',           'Generar reportes',                            'Reportes'),
    ('portal.paciente',         'Usar el portal del paciente',                'Portal');

insert into rolpermiso (idrol, idpermiso)
select r.idrol, p.idpermiso from rol r cross join permiso p
where (r.nombre = 'ADMINISTRADOR' and p.codigo not in ('agenda.propia', 'consultas.registrar', 'portal.paciente'))
   or (r.nombre = 'RECEPCIONISTA' and p.codigo in ('panel.ver', 'medicos.ver', 'pacientes.ver', 'pacientes.gestionar',
        'citas.ver', 'citas.gestionar', 'citas.asistencia', 'agenda.ver', 'reportes.ver', 'estudios.gestionar'))
   or (r.nombre = 'MEDICO' and p.codigo in ('panel.ver', 'agenda.propia', 'consultas.registrar', 'estudios.gestionar'))
   or (r.nombre = 'PACIENTE' and p.codigo = 'portal.paciente');

-- ----------------------------------------------------- Configuración
insert into configuracion (clave, valor, descripcion) values
    ('NOMBRE_CLINICA',        'Clínica Vida Salud', 'Nombre que se muestra en la aplicación'),
    ('DURACION_CITA_MINUTOS', '30',  'Duración de cada cita en minutos (bloques fijos)'),
    ('DIAS_MAX_RESERVA',      '60',  'Días máximos de anticipación para reservar'),
    ('HORAS_MIN_CANCELACION', '2',   'Horas mínimas de anticipación para que el paciente cancele desde el portal'),
    ('HORAS_RECORDATORIO',    '24',  'Horas antes de la cita en que se muestra el recordatorio');

-- ---------------------------------------------------- Estados de cita
insert into estadocita (codigo, nombre, descripcion, ocupahorario) values
    ('PROGRAMADA',   'Programada',   'La cita está reservada',            true),
    ('CONFIRMADA',   'Confirmada',   'Se ha confirmado la asistencia',    true),
    ('ATENDIDA',     'Atendida',     'La consulta se realizó',            true),
    ('CANCELADA',    'Cancelada',    'La cita fue cancelada',             false),
    ('INASISTENCIA', 'Inasistencia', 'El paciente no se presentó',        true);

-- ----------------------------------------------------- Especialidades
insert into especialidad (nombre, descripcion) values
    ('Medicina General', 'Atención primaria, control y prevención'),
    ('Pediatría',        'Atención de niños y adolescentes'),
    ('Ginecología',      'Salud de la mujer'),
    ('Odontología',      'Salud bucal y dental'),
    ('Cardiología',      'Corazón y sistema circulatorio'),
    ('Dermatología',     'Piel, cabello y uñas');

insert into medicamento (nombre, presentacion) values
    ('Paracetamol', 'Tabletas 500 mg'), ('Ibuprofeno', 'Tabletas 400 mg'),
    ('Amoxicilina', 'Cápsulas 500 mg'), ('Omeprazol', 'Cápsulas 20 mg'),
    ('Loratadina', 'Tabletas 10 mg'),   ('Salbutamol', 'Inhalador 100 mcg'),
    ('Enalapril', 'Tabletas 10 mg'),    ('Metformina', 'Tabletas 850 mg');

insert into catalogoestudio (nombre, categoria, descripcion) values
    ('Hemograma completo', 'Laboratorio', 'Recuento de células sanguíneas'),
    ('Glicemia en ayunas', 'Laboratorio', 'Nivel de glucosa en sangre'),
    ('Radiografía de tórax', 'Imagen', 'Placa PA y lateral'),
    ('Ecografía abdominal', 'Imagen', 'Abdomen completo'),
    ('Electrocardiograma', 'Cardiología', 'Actividad eléctrica del corazón');

-- ------------------------------------------------- Usuarios y personas
do $$
declare
    h text := '$2y$10$vBmekxWiBWqFD0ELjcEgU.6VUHflTKfSW9InBmYFVJaY4JzdQIPs.';  -- password_hash('HeadSalud2026')
    r_admin int := (select idrol from rol where nombre = 'ADMINISTRADOR');
    r_recep int := (select idrol from rol where nombre = 'RECEPCIONISTA');
    r_med   int := (select idrol from rol where nombre = 'MEDICO');
    r_pac   int := (select idrol from rol where nombre = 'PACIENTE');
    m1 int; m2 int; m3 int; m4 int; u int;
begin
    insert into usuario (nombre, username, email, password, idrol) values
        ('Administrador General', 'admin',  'admin@vidasalud.com',     h, r_admin),
        ('María Fernanda Rojas',  'mrojas', 'recepcion@vidasalud.com', h, r_recep),
        ('Jorge Cuéllar',         'jcuellar','jcuellar@vidasalud.com', h, r_recep);

    -- Médicos (cada uno con su cuenta de acceso)
    insert into usuario (nombre, username, email, password, idrol) values ('Dr. Carlos Mendoza', 'cmendoza', 'cmendoza@vidasalud.com', h, r_med) returning idusuario into u;
    insert into medico (codigo, registroprofesional, nombres, apellidos, telefono, email, idusuario)
    values ('MED-001', 'RP-12345', 'Carlos', 'Mendoza Ríos', '70011223', 'cmendoza@vidasalud.com', u) returning idmedico into m1;

    insert into usuario (nombre, username, email, password, idrol) values ('Dra. Lucía Vargas', 'lvargas', 'lvargas@vidasalud.com', h, r_med) returning idusuario into u;
    insert into medico (codigo, registroprofesional, nombres, apellidos, telefono, email, idusuario)
    values ('MED-002', 'RP-67890', 'Lucía', 'Vargas Peña', '70044556', 'lvargas@vidasalud.com', u) returning idmedico into m2;

    insert into usuario (nombre, username, email, password, idrol) values ('Dr. Roberto Suárez', 'rsuarez', 'rsuarez@vidasalud.com', h, r_med) returning idusuario into u;
    insert into medico (codigo, registroprofesional, nombres, apellidos, telefono, email, idusuario)
    values ('MED-003', 'RP-24680', 'Roberto', 'Suárez Antelo', '70077889', 'rsuarez@vidasalud.com', u) returning idmedico into m3;

    insert into usuario (nombre, username, email, password, idrol) values ('Dra. Patricia Molina', 'pmolina', 'pmolina@vidasalud.com', h, r_med) returning idusuario into u;
    insert into medico (codigo, registroprofesional, nombres, apellidos, telefono, email, idusuario)
    values ('MED-004', 'RP-13579', 'Patricia', 'Molina Justiniano', '70022334', 'pmolina@vidasalud.com', u) returning idmedico into m4;

    insert into medicoespecialidad (idmedico, idespecialidad)
    select m1, idespecialidad from especialidad where nombre in ('Medicina General', 'Cardiología') union all
    select m2, idespecialidad from especialidad where nombre in ('Pediatría') union all
    select m3, idespecialidad from especialidad where nombre in ('Odontología') union all
    select m4, idespecialidad from especialidad where nombre in ('Ginecología', 'Medicina General');

    -- Horarios de atención (1 = lunes … 6 = sábado)
    insert into horario (diasemana, horainicio, horafin, idmedico)
    select d, time '08:00', time '12:00', m1 from generate_series(1, 5) d union all
    select d, time '14:00', time '18:00', m2 from unnest(array[1, 3, 5]) d union all
    select d, time '08:00', time '12:00', m2 from unnest(array[2, 4]) d union all
    select d, time '09:00', time '13:00', m3 from generate_series(1, 6) d union all
    select d, time '14:00', time '18:00', m4 from unnest(array[2, 4]) d union all
    select 6, time '08:00', time '12:00', m4;

    -- Pacientes (dos con cuenta en el portal)
    insert into usuario (nombre, username, email, password, idrol) values ('Juan Pérez Quispe', 'jperez', 'jperez@correo.com', h, r_pac) returning idusuario into u;
    insert into paciente (codigo, ci, nombres, apellidos, fechanacimiento, sexo, telefono, email, direccion, tiposangre, idusuario)
    values ('PAC-0001', '1234567', 'Juan', 'Pérez Quispe', '1990-05-14', 'M', '71234567', 'jperez@correo.com', 'Av. Banzer #123', 'O+', u);

    insert into usuario (nombre, username, email, password, idrol) values ('María López Rivero', 'mlopez', 'mlopez@correo.com', h, r_pac) returning idusuario into u;
    insert into paciente (codigo, ci, nombres, apellidos, fechanacimiento, sexo, telefono, email, direccion, tiposangre, idusuario)
    values ('PAC-0002', '7654321', 'María', 'López Rivero', '1985-11-02', 'F', '72345678', 'mlopez@correo.com', 'Calle Sucre #45', 'A+', u);

    insert into paciente (codigo, ci, nombres, apellidos, fechanacimiento, sexo, telefono, email, direccion, tiposangre) values
        ('PAC-0003', '4567890', 'Ana',     'Gutiérrez Flores', '2018-03-21', 'F', '73456789', null, 'Barrio Equipetrol', 'B+'),
        ('PAC-0004', '5678901', 'Luis',    'Fernández Rojas',  '1972-07-09', 'M', '74567890', 'lfernandez@correo.com', null, 'O-'),
        ('PAC-0005', '6789012', 'Carla',   'Méndez Saavedra',  '1995-01-30', 'F', '75678901', 'cmendez@correo.com', 'Av. Cristo Redentor', 'AB+'),
        ('PAC-0006', '7890123', 'Pedro',   'Rivas Montaño',    '1960-12-12', 'M', '76789012', null, null, 'A-'),
        ('PAC-0007', '8901234', 'Sofía',   'Aguilera Paz',     '2015-09-05', 'F', '77890123', null, 'Urb. Las Palmas', 'O+'),
        ('PAC-0008', '9012345', 'Diego',   'Coca Vaca',        '1988-04-17', 'M', '78901234', 'dcoca@correo.com', null, null);

    insert into historiaclinica (numero, alergias, enfermedadescronicas, idpaciente)
    select 'HC-' || substr(codigo, 5), case when idpaciente % 3 = 0 then 'Penicilina' else 'Ninguna conocida' end,
           case when fechanacimiento < date '1975-01-01' then 'Hipertensión arterial' end, idpaciente
    from paciente;

    insert into contactoemergencia (nombre, parentesco, telefono, idpaciente)
    select 'Rosa Quispe', 'Madre', '72223344', idpaciente from paciente where codigo = 'PAC-0001' union all
    select 'Hugo Gutiérrez', 'Padre', '73334455', idpaciente from paciente where codigo = 'PAC-0003';
end $$;

-- ------------------------------------------------- Citas de demostración
-- Se generan alrededor de la fecha actual (pasadas, de hoy y futuras).
-- El trigger de validación se desactiva solo durante la carga, porque
-- las citas históricas están en el pasado por definición.
alter table cita disable trigger cita_validar;

do $$
declare
    v_dia date; v_h record; v_slot time; v_i int; v_n int := 0; v_pac int[]; v_esp int;
    v_estado text; v_id int; v_motivos text[] := array['Control general', 'Dolor de cabeza', 'Chequeo anual',
        'Fiebre y malestar', 'Control de presión', 'Dolor abdominal', 'Revisión de resultados', 'Consulta de rutina'];
begin
    select array_agg(idpaciente order by idpaciente) into v_pac from paciente;
    for v_dia in select d::date from generate_series(ahora_local()::date - 21, ahora_local()::date + 12, interval '1 day') d loop
        for v_h in select * from horario where diasemana = extract(isodow from v_dia) order by idmedico loop
            v_i := 0;
            v_slot := v_h.horainicio;
            while v_slot + interval '30 minutes' <= v_h.horafin loop
                if (v_i + extract(day from v_dia)::int + v_h.idmedico) % 3 = 0 then
                    v_n := v_n + 1;
                    v_estado := case
                        when v_dia < ahora_local()::date then
                            case when v_n % 9 = 0 then 'CANCELADA' when v_n % 7 = 0 then 'INASISTENCIA' else 'ATENDIDA' end
                        else
                            case when v_n % 8 = 0 then 'CANCELADA' when v_n % 3 = 0 then 'CONFIRMADA' else 'PROGRAMADA' end
                    end;
                    select idespecialidad into v_esp from medicoespecialidad
                    where idmedico = v_h.idmedico order by idespecialidad offset (v_n % 2) limit 1;
                    if v_esp is null then
                        select idespecialidad into v_esp from medicoespecialidad where idmedico = v_h.idmedico limit 1;
                    end if;
                    begin
                        insert into cita (fecha, horainicio, horafin, motivo, idpaciente, idmedico, idespecialidad,
                                          codigoestado, fecharegistro, idusuarioregistro)
                        values (v_dia, v_slot, v_slot + interval '30 minutes', v_motivos[1 + v_n % 8],
                                v_pac[1 + (v_n * 5) % array_length(v_pac, 1)], v_h.idmedico, v_esp, v_estado,
                                ((v_dia - 4)::timestamp + time '10:00') at time zone 'America/La_Paz', (select idusuario from usuario where username = 'mrojas'))
                        returning idcita into v_id;

                        insert into historialcita (accion, fechanueva, horanueva, motivo, fecha, idcita, codigoestado, idusuario)
                        values ('PROGRAMAR', v_dia, v_slot, null, ((v_dia - 4)::timestamp + time '10:00') at time zone 'America/La_Paz', v_id, 'PROGRAMADA',
                                (select idusuario from usuario where username = 'mrojas'));
                        if v_estado <> 'PROGRAMADA' then
                            insert into historialcita (accion, motivo, fecha, idcita, codigoestado, idusuario)
                            values (case v_estado when 'CONFIRMADA' then 'CONFIRMAR' when 'CANCELADA' then 'CANCELAR'
                                                  when 'ATENDIDA' then 'ATENDER' else 'INASISTENCIA' end,
                                    case v_estado when 'CANCELADA' then 'El paciente avisó que no podrá asistir' end,
                                    least((v_dia + v_slot) at time zone 'America/La_Paz', now()), v_id, v_estado, null);
                        end if;
                        if v_estado = 'ATENDIDA' then
                            insert into consulta (fechahora, motivo, peso, talla, presionarterial, temperatura, frecuenciacardiaca,
                                                  examenfisico, diagnostico, observaciones, idcita)
                            values ((v_dia + v_slot) at time zone 'America/La_Paz', v_motivos[1 + v_n % 8], 60 + v_n % 25, 155 + v_n % 30,
                                    (110 + v_n % 30) || '/' || (70 + v_n % 15), 36.4 + (v_n % 8) / 10.0, 65 + v_n % 25,
                                    'Paciente lúcido, orientado, sin signos de alarma.',
                                    (array['Resfrío común', 'Cefalea tensional', 'Paciente sano', 'Gastritis leve',
                                           'Hipertensión controlada', 'Faringitis aguda'])[1 + v_n % 6],
                                    'Control en 30 días si persisten los síntomas.', v_id);
                        end if;
                    exception when exclusion_violation then
                        null;  -- el paciente ya tenía otra cita a esa hora: se omite
                    end;
                end if;
                v_i := v_i + 1;
                v_slot := v_slot + interval '30 minutes';
            end loop;
        end loop;
    end loop;
end $$;

alter table cita enable trigger cita_validar;

-- Tratamientos para algunas consultas
insert into tratamiento (dosis, frecuencia, duracion, indicaciones, idconsulta, idmedicamento)
select '1 tableta', 'Cada 8 horas', '5 días', 'Tomar después de las comidas', c.idconsulta,
       (select idmedicamento from medicamento order by idmedicamento offset (c.idconsulta % 8) limit 1)
from consulta c where c.idconsulta % 2 = 0;

-- Recordatorios de las próximas citas
insert into notificacion (tipo, canal, mensaje, programadapara, idcita)
select 'RECORDATORIO', 'APP',
       'Recuerde su cita ' || c.codigo || ' del ' || to_char(c.fecha, 'DD/MM/YYYY') || ' a las ' || to_char(c.horainicio, 'HH24:MI'),
       ((c.fecha + c.horainicio) - interval '24 hours') at time zone 'America/La_Paz', c.idcita
from cita c where c.fecha >= ahora_local()::date and c.codigoestado in ('PROGRAMADA', 'CONFIRMADA');

-- Bloqueo de ejemplo dentro de dos semanas para el Dr. Suárez
insert into bloqueoagenda (fecha, tipo, motivo, idmedico)
select ahora_local()::date + 14, 'PERMISO', 'Capacitación externa', idmedico from medico where codigo = 'MED-003';

-- Estudios médicos de ejemplo (CU18 / CU19): unos con resultado y otros pendientes
insert into catalogoestudio (nombre, categoria, descripcion) values
    ('Perfil lipídico', 'Laboratorio', 'Colesterol total, HDL, LDL y triglicéridos'),
    ('Radiografía dental panorámica', 'Imagen', 'Ortopantomografía');

insert into estudio (indicaciones, estado, fechasolicitud, fecharesultado, resultado, idconsulta, idcatalogo)
select 'En ayunas de 8 horas',
       case when c.idconsulta % 3 = 0 then 'SOLICITADO' else 'COMPLETADO' end,
       c.fechahora,
       case when c.idconsulta % 3 = 0 then null else c.fechahora + interval '2 days' end,
       case when c.idconsulta % 3 = 0 then null
            else (array['Valores dentro de los rangos normales.',
                        'Glucosa 98 mg/dL (normal). Sin alteraciones.',
                        'Leve elevación de colesterol LDL (145 mg/dL). Control en 3 meses.',
                        'Sin hallazgos patológicos.'])[1 + c.idconsulta % 4] end,
       c.idconsulta,
       (select idcatalogo from catalogoestudio order by idcatalogo offset (c.idconsulta % 5) limit 1)
from consulta c where c.idconsulta % 4 = 1;

insert into notificacion (tipo, canal, mensaje, programadapara, enviadaen, idcita, idestudio)
select 'RESULTADO', 'APP', 'Ya está disponible el resultado de su estudio: ' || ce.nombre,
       e.fecharesultado, e.fecharesultado, co.idcita, e.idestudio
from estudio e join consulta co on co.idconsulta = e.idconsulta
join catalogoestudio ce on ce.idcatalogo = e.idcatalogo
where e.estado = 'COMPLETADO';


-- ---------------------------------------------------- Últimos accesos de demostración
-- Simula que las cuentas de prueba ya ingresaron al sistema en los últimos días
-- (el sistema actualiza este dato en cada inicio de sesión).
update usuario u set ultimoacceso = now() - make_interval(hours => x.horas)
from (values ('admin', 3), ('mrojas', 5), ('cmendoza', 20), ('pmolina', 26),
             ('lvargas', 30), ('jperez', 44), ('rsuarez', 50), ('mlopez', 70)) as x(username, horas)
where u.username = x.username;
