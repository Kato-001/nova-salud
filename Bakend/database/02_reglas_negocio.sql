-- =====================================================================
-- Nova Salud · 02 · Reglas de negocio en la base de datos
-- ---------------------------------------------------------------------
-- La aplicación PHP valida todo antes de guardar; estos triggers son la
-- última línea de defensa (RN04–RN08, RF08, RF10) por si algún dato llega
-- sin pasar por la aplicación. Los mensajes de RAISE EXCEPTION se muestran
-- al usuario tal cual, por eso están redactados para él.
-- =====================================================================

set search_path = clinica, extensions, public;

-- Fecha y hora actuales de la clínica (Bolivia, UTC-4)
create or replace function ahora_local() returns timestamp
language sql stable set search_path = '' as $$
    select (now() at time zone 'America/La_Paz')
$$;

-- ---------------------------------------------------------------------
-- Validación de una cita al registrarla o reprogramarla
-- ---------------------------------------------------------------------
create or replace function validar_cita() returns trigger
language plpgsql set search_path = '' as $$
declare
    v_duracion integer := coalesce((select valor::int from clinica.configuracion where clave = 'DURACION_CITA_MINUTOS'), 30);
    v_cambia_horario boolean := tg_op = 'INSERT'
        or new.fecha is distinct from old.fecha
        or new.horainicio is distinct from old.horainicio
        or new.horafin is distinct from old.horafin
        or new.idmedico is distinct from old.idmedico;
begin
    -- RF08: solo se reprograman citas vigentes
    if tg_op = 'UPDATE' and v_cambia_horario and old.codigoestado not in ('PROGRAMADA', 'CONFIRMADA') then
        raise exception 'Solo se pueden reprogramar citas programadas o confirmadas';
    end if;

    if v_cambia_horario then
        -- RN04: el médico debe estar activo
        if not exists (select 1 from clinica.medico where idmedico = new.idmedico and estado = 'ACTIVO') then
            raise exception 'El médico no está activo y no puede recibir nuevas citas';
        end if;
        if tg_op = 'INSERT' and not exists (select 1 from clinica.paciente where idpaciente = new.idpaciente and estado = 'ACTIVO') then
            raise exception 'El paciente está inactivo';
        end if;
        -- El médico debe atender la especialidad elegida
        if not exists (select 1 from clinica.medicoespecialidad
                       where idmedico = new.idmedico and idespecialidad = new.idespecialidad) then
            raise exception 'El médico no atiende la especialidad seleccionada';
        end if;
        -- RN06: nada en el pasado
        if new.fecha + new.horainicio <= clinica.ahora_local() then
            raise exception 'No se pueden registrar citas en fechas u horas pasadas';
        end if;
        -- RN08: duración configurada
        if new.horafin - new.horainicio <> make_interval(mins => v_duracion) then
            raise exception 'La cita debe durar % minutos', v_duracion;
        end if;
        -- RN05: dentro de un horario de atención y alineada a sus intervalos
        if not exists (
            select 1 from clinica.horario h
            where h.idmedico = new.idmedico and h.estado = 'ACTIVO'
              and h.diasemana = extract(isodow from new.fecha)
              and new.horainicio >= h.horainicio and new.horafin <= h.horafin
              and (extract(epoch from new.horainicio - h.horainicio)::int / 60) % v_duracion = 0
        ) then
            raise exception 'El horario elegido está fuera de la atención del médico';
        end if;
        -- Bloqueos de agenda (feriados, vacaciones, permisos)
        if exists (
            select 1 from clinica.bloqueoagenda b
            where b.idmedico = new.idmedico and b.fecha = new.fecha
              and (b.horainicio is null or (new.horainicio < b.horafin and new.horafin > b.horainicio))
        ) then
            raise exception 'El médico tiene la agenda bloqueada en ese horario';
        end if;
    end if;

    -- RF10: transiciones de estado permitidas
    if tg_op = 'UPDATE' and new.codigoestado is distinct from old.codigoestado then
        if not (
            (old.codigoestado = 'PROGRAMADA' and new.codigoestado in ('CONFIRMADA', 'CANCELADA', 'ATENDIDA', 'INASISTENCIA')) or
            (old.codigoestado = 'CONFIRMADA' and new.codigoestado in ('PROGRAMADA', 'CANCELADA', 'ATENDIDA', 'INASISTENCIA'))
        ) then
            raise exception 'No se puede pasar una cita de % a %', old.codigoestado, new.codigoestado;
        end if;
        if new.codigoestado in ('ATENDIDA', 'INASISTENCIA') and new.fecha > clinica.ahora_local()::date then
            raise exception 'No se puede registrar la asistencia de una cita futura';
        end if;
    end if;
    return new;
end $$;

create trigger cita_validar before insert or update on cita
    for each row execute function validar_cita();

-- ---------------------------------------------------------------------
-- Los roles del sistema no se renombran ni se eliminan
-- ---------------------------------------------------------------------
create or replace function proteger_rol() returns trigger
language plpgsql set search_path = '' as $$
begin
    if old.nombre in ('ADMINISTRADOR', 'RECEPCIONISTA', 'MEDICO', 'PACIENTE') then
        if tg_op = 'DELETE' then
            raise exception 'El rol % es del sistema y no se puede eliminar', old.nombre;
        elsif new.nombre <> old.nombre then
            raise exception 'El rol % es del sistema y no se puede renombrar', old.nombre;
        end if;
    end if;
    return coalesce(new, old);
end $$;

create trigger rol_proteger before update or delete on rol
    for each row execute function proteger_rol();

-- ---------------------------------------------------------------------
-- Acceso: la aplicación PHP se conecta con su propio usuario (headsalud_app);
-- la API pública de Supabase (anon/authenticated) no tiene acceso al esquema.
-- El usuario se crea aparte (ver 04_usuario_app.sql) para no publicar su clave.
-- ---------------------------------------------------------------------
revoke all on schema clinica from public;
-- Roles de la API de Supabase (solo existen allí)
do $$
begin
    if exists (select 1 from pg_roles where rolname = 'anon') then
        execute 'revoke all on schema clinica from anon, authenticated';
    end if;
end $$;
revoke execute on all functions in schema clinica from public;
alter default privileges in schema clinica revoke execute on functions from public;

do $$
declare t text;
begin
    for t in select tablename from pg_tables where schemaname = 'clinica' loop
        execute format('alter table clinica.%I enable row level security', t);
    end loop;
end $$;
