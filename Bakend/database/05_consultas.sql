-- =====================================================================
-- Nova Salud · 05 · Consultas SQL (diseño físico de datos)
-- ---------------------------------------------------------------------
-- 30 consultas de ejemplo sobre el esquema "clinica":
--   C01–C10 consultas simples · C11–C20 consultas múltiples (JOIN)
--   C21–C30 subconsultas
-- Solo leen datos: se pueden ejecutar sin riesgo sobre la base real.
-- =====================================================================

set search_path = clinica, extensions, public;

-- ---------------------------------------------------------------------
-- Consultas simples
-- ---------------------------------------------------------------------

-- C01 · Pacientes activos ordenados por apellido
select codigo, ci, nombres, apellidos, telefono
from paciente
where estado = 'ACTIVO'
order by apellidos, nombres;

-- C02 · Especialidades que atiende la clínica
select idespecialidad, nombre, descripcion
from especialidad
where estado = 'ACTIVO'
order by nombre;

-- C03 · Médicos activos con su registro profesional
select codigo, nombres || ' ' || apellidos as medico, registroprofesional, telefono
from medico
where estado = 'ACTIVO'
order by apellidos;

-- C04 · Citas programadas o confirmadas de los próximos 7 días
select codigo, fecha, horainicio, horafin, codigoestado
from cita
where fecha between current_date and current_date + 7
  and codigoestado in ('PROGRAMADA', 'CONFIRMADA')
order by fecha, horainicio;

-- C05 · Cantidad de citas por estado
select codigoestado, count(*) as total
from cita
group by codigoestado
order by total desc;

-- C06 · Búsqueda de pacientes por CI, nombre o apellido (RF02)
select codigo, ci, nombres, apellidos
from paciente
where ci ilike '%123%' or nombres || ' ' || apellidos ilike '%pérez%';

-- C07 · Estados de cita que ocupan el horario del médico (RN09)
select codigo, nombre
from estadocita
where ocupahorario
order by codigo;

-- C08 · Citas registradas por mes
select to_char(date_trunc('month', fecha), 'YYYY-MM') as mes, count(*) as citas
from cita
group by date_trunc('month', fecha)
order by mes;

-- C09 · Últimos cinco accesos al sistema
select username, nombre, ultimoacceso
from usuario
where ultimoacceso is not null
order by ultimoacceso desc
limit 5;

-- C10 · Horarios activos de los lunes
select idmedico, horainicio, horafin
from horario
where diasemana = 1 and estado = 'ACTIVO'
order by horainicio;

-- ---------------------------------------------------------------------
-- Consultas múltiples (varias tablas)
-- ---------------------------------------------------------------------

-- C11 · Agenda de la semana con paciente, médico y especialidad (RF11)
select c.codigo, c.fecha, c.horainicio,
       p.nombres || ' ' || p.apellidos as paciente,
       m.nombres || ' ' || m.apellidos as medico,
       e.nombre as especialidad, ec.nombre as estado
from cita c
join paciente p      on p.idpaciente = c.idpaciente
join medico m        on m.idmedico = c.idmedico
join especialidad e  on e.idespecialidad = c.idespecialidad
join estadocita ec   on ec.codigo = c.codigoestado
where c.fecha between current_date and current_date + 7
order by c.fecha, c.horainicio;

-- C12 · Médicos con todas sus especialidades
select m.codigo, m.nombres || ' ' || m.apellidos as medico,
       string_agg(e.nombre, ', ' order by e.nombre) as especialidades
from medico m
join medicoespecialidad me on me.idmedico = m.idmedico
join especialidad e        on e.idespecialidad = me.idespecialidad
group by m.idmedico, m.codigo, m.nombres, m.apellidos
order by medico;

-- C13 · Usuarios con su rol
select u.username, u.nombre, u.email, r.nombre as rol, u.estado
from usuario u
join rol r on r.idrol = u.idrol
order by r.nombre, u.username;

-- C14 · Cantidad de permisos de cada rol
select r.nombre as rol, count(rp.idpermiso) as permisos
from rol r
left join rolpermiso rp on rp.idrol = r.idrol
group by r.idrol, r.nombre
order by permisos desc;

-- C15 · Citas por médico y estado (RF13)
select m.nombres || ' ' || m.apellidos as medico,
       count(*) filter (where c.codigoestado = 'ATENDIDA')     as atendidas,
       count(*) filter (where c.codigoestado = 'CANCELADA')    as canceladas,
       count(*) filter (where c.codigoestado = 'INASISTENCIA') as inasistencias,
       count(c.idcita) as total
from medico m
left join cita c on c.idmedico = m.idmedico
group by m.idmedico, m.nombres, m.apellidos
order by total desc;

-- C16 · Consultas médicas con paciente y diagnóstico
select c.codigo as cita, co.fechahora, p.nombres || ' ' || p.apellidos as paciente, co.diagnostico
from consulta co
join cita c     on c.idcita = co.idcita
join paciente p on p.idpaciente = c.idpaciente
order by co.fechahora desc
limit 10;

-- C17 · Tratamientos indicados con el medicamento y el paciente
select p.nombres || ' ' || p.apellidos as paciente, me.nombre as medicamento,
       t.dosis, t.frecuencia, t.duracion
from tratamiento t
join medicamento me on me.idmedicamento = t.idmedicamento
join consulta co    on co.idconsulta = t.idconsulta
join cita c         on c.idcita = co.idcita
join paciente p     on p.idpaciente = c.idpaciente
order by paciente;

-- C18 · Estudios médicos con su estado y paciente (CU18)
select ce.nombre as estudio, ce.categoria, es.estado, es.fechasolicitud,
       p.nombres || ' ' || p.apellidos as paciente
from estudio es
join catalogoestudio ce on ce.idcatalogo = es.idcatalogo
join consulta co        on co.idconsulta = es.idconsulta
join cita c             on c.idcita = co.idcita
join paciente p         on p.idpaciente = c.idpaciente
order by es.fechasolicitud desc;

-- C19 · Historial de cambios de las citas con el usuario que los hizo
select c.codigo, h.accion, ec.nombre as estado, h.motivo, h.fecha, u.username
from historialcita h
join cita c        on c.idcita = h.idcita
join estadocita ec on ec.codigo = h.codigoestado
left join usuario u on u.idusuario = h.idusuario
order by h.fecha desc
limit 15;

-- C20 · Horario semanal de cada médico con el nombre del día
select m.nombres || ' ' || m.apellidos as medico,
       (array['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'])[h.diasemana] as dia,
       h.horainicio, h.horafin
from horario h
join medico m on m.idmedico = h.idmedico
where h.estado = 'ACTIVO'
order by medico, h.diasemana, h.horainicio;

-- ---------------------------------------------------------------------
-- Subconsultas
-- ---------------------------------------------------------------------

-- C21 · Pacientes que nunca sacaron una cita
select codigo, nombres, apellidos
from paciente p
where not exists (select 1 from cita c where c.idpaciente = p.idpaciente);

-- C22 · Médicos con más citas que el promedio de la clínica
select m.nombres || ' ' || m.apellidos as medico, count(*) as citas
from cita c
join medico m on m.idmedico = c.idmedico
group by m.idmedico, m.nombres, m.apellidos
having count(*) > (select avg(n) from (select count(*) as n from cita group by idmedico) x);

-- C23 · Pacientes con al menos tres citas atendidas
select codigo, nombres, apellidos
from paciente
where idpaciente in (
    select idpaciente from cita
    where codigoestado = 'ATENDIDA'
    group by idpaciente
    having count(*) >= 3
);

-- C24 · Especialidad más solicitada
select e.nombre, x.total
from (select idespecialidad, count(*) as total from cita group by idespecialidad) x
join especialidad e on e.idespecialidad = x.idespecialidad
where x.total = (select max(n) from (select count(*) as n from cita group by idespecialidad) y);

-- C25 · Última cita de cada paciente (subconsulta correlacionada)
select p.nombres || ' ' || p.apellidos as paciente, c.codigo, c.fecha, c.codigoestado
from cita c
join paciente p on p.idpaciente = c.idpaciente
where c.fecha + c.horainicio = (
    select max(c2.fecha + c2.horainicio) from cita c2 where c2.idpaciente = c.idpaciente
);

-- C26 · Médicos activos que no atienden los sábados
select codigo, nombres, apellidos
from medico m
where estado = 'ACTIVO'
  and m.idmedico not in (select idmedico from horario where diasemana = 6 and estado = 'ACTIVO');

-- C27 · Porcentaje de asistencia de cada médico (citas ya cerradas)
select m.nombres || ' ' || m.apellidos as medico,
       (select count(*) from cita c where c.idmedico = m.idmedico and c.codigoestado = 'ATENDIDA') as atendidas,
       (select count(*) from cita c where c.idmedico = m.idmedico and c.codigoestado in ('ATENDIDA', 'INASISTENCIA')) as cerradas,
       round(100.0 * (select count(*) from cita c where c.idmedico = m.idmedico and c.codigoestado = 'ATENDIDA')
             / nullif((select count(*) from cita c where c.idmedico = m.idmedico and c.codigoestado in ('ATENDIDA', 'INASISTENCIA')), 0), 1) as asistencia_pct
from medico m
order by asistencia_pct desc nulls last;

-- C28 · Medicamentos que nunca se recetaron
select nombre, presentacion
from medicamento
where idmedicamento not in (select idmedicamento from tratamiento);

-- C29 · Pacientes con estudios todavía pendientes de resultado
select codigo, nombres, apellidos
from paciente p
where exists (
    select 1 from estudio es
    join consulta co on co.idconsulta = es.idconsulta
    join cita c      on c.idcita = co.idcita
    where c.idpaciente = p.idpaciente and es.estado in ('SOLICITADO', 'EN_PROCESO')
);

-- C30 · Citas de la especialidad con más inasistencias
select c.codigo, c.fecha, c.codigoestado
from cita c
where c.idespecialidad = (
    select idespecialidad from cita
    where codigoestado = 'INASISTENCIA'
    group by idespecialidad
    order by count(*) desc
    limit 1
)
order by c.fecha desc
limit 10;
