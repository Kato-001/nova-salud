-- =====================================================================
-- Nova Salud · 06 · Procedimientos almacenados y disparadores
-- ---------------------------------------------------------------------
-- Complementa a 02_reglas_negocio.sql (trigger validar_cita y proteger_rol).
-- Se ejecuta después de los scripts 01 a 04.
-- =====================================================================

set search_path = clinica, extensions, public;

-- ---------------------------------------------------------------------
-- PA1 · Horarios libres de un médico en una fecha (CU9, RN05, RN07, RN09)
-- Divide cada horario del día en bloques de DURACION_CITA_MINUTOS y quita
-- los ocupados por citas vigentes, los bloqueos de agenda y las horas pasadas.
-- ---------------------------------------------------------------------
create or replace function fn_horarios_libres(p_idmedico integer, p_fecha date)
returns table (horainicio time, horafin time)
language sql stable set search_path = '' as $$
    with cfg as (
        select coalesce((select valor::int from clinica.configuracion where clave = 'DURACION_CITA_MINUTOS'), 30) as minutos
    ), bloques as (
        select (g)::time as ini, (g + make_interval(mins => cfg.minutos))::time as fin
        from clinica.horario h, cfg,
             generate_series(p_fecha + h.horainicio, p_fecha + h.horafin - make_interval(mins => cfg.minutos),
                             make_interval(mins => cfg.minutos)) g
        where h.idmedico = p_idmedico and h.estado = 'ACTIVO'
          and h.diasemana = extract(isodow from p_fecha)
    )
    select b.ini, b.fin
    from bloques b
    where p_fecha + b.ini > clinica.ahora_local()
      and not exists (select 1 from clinica.cita c
                      join clinica.estadocita e on e.codigo = c.codigoestado and e.ocupahorario
                      where c.idmedico = p_idmedico and c.fecha = p_fecha
                        and b.ini < c.horafin and b.fin > c.horainicio)
      and not exists (select 1 from clinica.bloqueoagenda k
                      where k.idmedico = p_idmedico and k.fecha = p_fecha
                        and (k.horainicio is null or (b.ini < k.horafin and b.fin > k.horainicio)))
    order by b.ini
$$;

-- ---------------------------------------------------------------------
-- PA2 · Cancelar una cita con su motivo (CU12, RF09)
-- El trigger validar_cita controla que la transición de estado sea válida.
-- ---------------------------------------------------------------------
create or replace procedure sp_cancelar_cita(p_idcita integer, p_motivo varchar, p_idusuario integer)
language plpgsql set search_path = '' as $$
begin
    if coalesce(trim(p_motivo), '') = '' then
        raise exception 'Debe indicar el motivo de la cancelación';
    end if;
    update clinica.cita set codigoestado = 'CANCELADA' where idcita = p_idcita;
    if not found then
        raise exception 'La cita % no existe', p_idcita;
    end if;
    insert into clinica.historialcita (accion, motivo, idcita, codigoestado, idusuario)
    values ('CANCELAR', p_motivo, p_idcita, 'CANCELADA', p_idusuario);
end $$;

-- ---------------------------------------------------------------------
-- PA3 · Desactivar un paciente (RN11: no se elimina, se desactiva)
-- No se permite si tiene citas futuras vigentes.
-- ---------------------------------------------------------------------
create or replace procedure sp_desactivar_paciente(p_idpaciente integer)
language plpgsql set search_path = '' as $$
begin
    if exists (select 1 from clinica.cita
               where idpaciente = p_idpaciente and codigoestado in ('PROGRAMADA', 'CONFIRMADA')
                 and fecha >= clinica.ahora_local()::date) then
        raise exception 'El paciente tiene citas pendientes; cancélelas antes de desactivarlo';
    end if;
    update clinica.paciente set estado = 'INACTIVO' where idpaciente = p_idpaciente;
end $$;

-- ---------------------------------------------------------------------
-- PA4 · Resumen de citas por estado en un período (CU20, RF13)
-- ---------------------------------------------------------------------
create or replace function fn_resumen_citas(p_desde date, p_hasta date)
returns table (estado varchar, total bigint, porcentaje numeric)
language sql stable set search_path = '' as $$
    select e.nombre, count(c.idcita),
           round(100.0 * count(c.idcita) / nullif(sum(count(c.idcita)) over (), 0), 1)
    from clinica.estadocita e
    left join clinica.cita c on c.codigoestado = e.codigo and c.fecha between p_desde and p_hasta
    group by e.codigo, e.nombre
    order by count(c.idcita) desc
$$;

-- ---------------------------------------------------------------------
-- PA5 · Edad actual de un paciente
-- ---------------------------------------------------------------------
create or replace function fn_edad_paciente(p_idpaciente integer)
returns integer
language sql stable set search_path = '' as $$
    select extract(year from age(clinica.ahora_local()::date, fechanacimiento))::int
    from clinica.paciente where idpaciente = p_idpaciente
$$;

-- ---------------------------------------------------------------------
-- TG1 · Normalizar los datos del paciente antes de guardarlos
-- Quita espacios sobrantes, pasa el CI a mayúsculas y el correo a minúsculas.
-- ---------------------------------------------------------------------
create or replace function tg_normalizar_paciente() returns trigger
language plpgsql set search_path = '' as $$
begin
    new.nombres   := regexp_replace(trim(new.nombres), '\s+', ' ', 'g');
    new.apellidos := regexp_replace(trim(new.apellidos), '\s+', ' ', 'g');
    new.ci        := upper(trim(new.ci));
    new.email     := nullif(lower(trim(new.email)), '');
    return new;
end $$;

create trigger paciente_normalizar before insert or update on paciente
    for each row execute function tg_normalizar_paciente();

-- ---------------------------------------------------------------------
-- TG2 · Auditar la activación o desactivación de un médico en la bitácora
-- ---------------------------------------------------------------------
create or replace function tg_auditar_estado_medico() returns trigger
language plpgsql set search_path = '' as $$
begin
    insert into clinica.bitacora (accion, modulo, tabla, idregistro, descripcion)
    values (case when new.estado = 'ACTIVO' then 'ACTIVAR' else 'DESACTIVAR' end,
            'GESTION_MEDICA', 'medico', new.idmedico,
            format('El médico %s %s pasó de %s a %s', new.nombres, new.apellidos, old.estado, new.estado));
    return new;
end $$;

create trigger medico_auditar_estado after update of estado on medico
    for each row when (old.estado is distinct from new.estado)
    execute function tg_auditar_estado_medico();

-- ---------------------------------------------------------------------
-- TG3 · Fecha del resultado de un estudio (CU18)
-- Al completar un estudio se registra la fecha del resultado si falta.
-- ---------------------------------------------------------------------
create or replace function tg_fecha_resultado_estudio() returns trigger
language plpgsql set search_path = '' as $$
begin
    if new.estado = 'COMPLETADO' and new.fecharesultado is null then
        new.fecharesultado := now();
    end if;
    return new;
end $$;

create trigger estudio_fecha_resultado before insert or update on estudio
    for each row execute function tg_fecha_resultado_estudio();

revoke execute on all functions in schema clinica from public;
revoke execute on all procedures in schema clinica from public;
-- La aplicación (headsalud_app, ver 04_usuario_app.sql) puede usar las funciones nuevas
do $$
begin
    if exists (select 1 from pg_roles where rolname = 'headsalud_app') then
        execute 'grant execute on all functions in schema clinica to headsalud_app';
        execute 'grant execute on all procedures in schema clinica to headsalud_app';
    end if;
end $$;
