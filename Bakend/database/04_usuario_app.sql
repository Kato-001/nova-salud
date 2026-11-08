-- =====================================================================
-- Nova Salud · 04 · Usuario de base de datos de la aplicación PHP
-- ---------------------------------------------------------------------
-- Reemplace CAMBIAR_CLAVE por una contraseña segura antes de ejecutarlo y
-- use la misma en la variable DATABASE_URL (.env local o Vercel).
-- Principio de mínimo privilegio: solo puede leer/escribir datos, no
-- cambiar la estructura.
-- =====================================================================

create role headsalud_app login password 'CAMBIAR_CLAVE';
alter role headsalud_app set timezone = 'America/La_Paz';
alter role headsalud_app set search_path = clinica, extensions, public;

grant usage on schema clinica, extensions to headsalud_app;
grant select, insert, update, delete on all tables in schema clinica to headsalud_app;
grant usage, select on all sequences in schema clinica to headsalud_app;
grant execute on all functions in schema clinica to headsalud_app;

-- RLS está activo en todas las tablas: la aplicación tiene acceso completo,
-- y el control de permisos por rol lo hace PHP (RN12)
do $$
declare t text;
begin
    for t in select tablename from pg_tables where schemaname = 'clinica' loop
        execute format('create policy app_acceso on clinica.%I for all to headsalud_app using (true) with check (true)', t);
    end loop;
end $$;
