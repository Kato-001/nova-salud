# Sugerencias para las presentaciones · Ciclo 1 y Ciclo 2

> Documento interno del grupo. **No forma parte de la documentación del sistema.**
> Son posibilidades: el docente decide qué pide en cada ciclo.

---

## Cómo podrían repartirse los casos de uso

La división más habitual con el Proceso Unificado es construir primero la base (seguridad y datos maestros) y después el núcleo del negocio (citas y atención).

| Ciclo | Paquetes | Casos de uso |
|---|---|---|
| **Ciclo 1** | P1 Seguridad y acceso · P2 Gestión médica · P3 Gestión de pacientes | CU1 Iniciar sesión · CU2 Gestionar roles y permisos · CU3 Gestionar usuarios (incluye configuración y bitácora) · CU4 Especialidades · CU5 Médicos · CU6 Horarios · CU7 Pacientes · CU8 Registrarse como paciente |
| **Ciclo 2** | P4 Gestión de citas · P5 Atención médica · P6 Reportes | CU9 Consultar disponibilidad · CU10 Reservar ficha · CU11 Reprogramar · CU12 Cancelar · CU13 Registrar asistencia · CU14 Agenda médica · CU15 Consulta médica · CU16 Historial clínico · CU17 Tratamiento · CU18 Estudios médicos · CU19 Historial de resultados · CU20 Reportes |

---

## Ciclo 1 · Lo que probablemente pidan

### Documentación
- **Perfil:** introducción, objetivos, descripción del problema y alcance (salen del enunciado).
- **Marco teórico:** PHP, PostgreSQL, PDO, MVC, sesiones, `password_hash`, Proceso Unificado y UML.
- **Modelo de negocio:** diagrama de actividades del proceso manual actual (cuadernos y llamadas) y el propuesto.
- **FT Captura de requisitos:**
  - Actores: Administrador, Recepcionista, Médico y Paciente.
  - Diagrama general de casos de uso y diagramas por paquete.
  - Descripción expandida de CU1 a CU8 (actor, precondición, flujo principal, flujos alternos, postcondición).
  - Requerimientos funcionales RF01–RF05 y no funcionales (seguridad, usabilidad, rendimiento).
- **FT Análisis:** diagramas de colaboración o comunicación, y el diagrama de paquetes (P1…P6).
- **FT Diseño:**
  - Diagrama de clases (está en `Docs/informe/base_de_datos/Diagrama de Clinica.svg`).
  - Modelo lógico y físico de la base de datos.
  - Diagramas de secuencia de *Iniciar sesión* y *Registrar paciente*.
  - Arquitectura lógica (MVC) y física (navegador → Vercel → Supabase).
- **FT Implementación:** herramientas y capturas de las pantallas de los CU del ciclo.
- **FT Pruebas:** casos de prueba 1, 2, 9 y 10 del enunciado (paciente nuevo, CI duplicado, acceso sin sesión, datos inválidos).

### Demostración
- Iniciar sesión con cada rol y mostrar que el menú cambia según los permisos.
- Intentar abrir `/usuarios` como recepcionista y obtener **403**.
- Crear un usuario, desactivarlo e intentar entrar con él: queda bloqueado.
- Registrar un paciente y repetir el CI: lo rechaza.
- Crear una especialidad repetida ("pediatria" contra "Pediatría"): la rechaza.
- Registrar un médico con dos especialidades y definirle horarios. Un horario cruzado o con la hora final menor se rechaza.
- Mostrar la bitácora con los registros de lo que acaban de hacer.

### Preguntas probables y respuesta corta
| Pregunta | Respuesta |
|---|---|
| ¿Cómo guardan las contraseñas? | Con `password_hash()` (bcrypt, con sal) y se verifican con `password_verify()`. Nunca en texto plano. |
| ¿Cómo evitan la inyección SQL? | Usamos PDO con consultas parametrizadas (`:ci`) en todos los modelos y nunca concatenamos datos del usuario. |
| ¿Qué pasa si entro a una página sin sesión? | `Auth::requerir()` redirige a `/login`. Sin el permiso correspondiente, responde 403. |
| ¿Por qué no se eliminan pacientes ni médicos? | Por RN11: se desactivan y su historial se conserva. Las FK sin `CASCADE` impiden borrarlos. |
| ¿Dónde se valida? | En las dos capas: JavaScript para la experiencia de uso y PHP (`Validator`) como validación real. La base tiene además `CHECK`, `UNIQUE` y triggers. |
| ¿Qué es el token CSRF? | Un valor secreto de la sesión que viaja en cada formulario. Si no coincide, la petición se rechaza (419). |

### Posibles requisitos extra que podrían agregar
- Recuperación de contraseña por correo.
- Foto de perfil del médico o del paciente.
- Exigir el cambio de contraseña en el primer ingreso.
- Bitácora exportable a CSV.

---

## Ciclo 2 · Lo que probablemente pidan

### Documentación
- **FT Captura de requisitos:** descripción expandida de CU9 a CU20 y los RF06–RF13.
- **FT Análisis:**
  - Diagrama de estados de la **Cita**: Programada → Confirmada → Atendida / Inasistencia / Cancelada.
  - Diagramas de colaboración de *Reservar ficha* y *Cancelar cita*.
- **FT Diseño:**
  - Diagramas de secuencia de *Reservar ficha*. Es el más importante: debe mostrar la verificación de disponibilidad, la transacción y el `FOR UPDATE`.
  - Diagramas de secuencia de *Reprogramar*, *Registrar asistencia* y *Generar reporte*.
  - Diseño de los reportes.
- **FT Implementación:** arquitectura de subsistemas (paquetes P4–P6) y capturas.
- **FT Pruebas:** casos 3 a 8 del enunciado y pruebas de concurrencia (dos personas reservando el mismo horario).
- **Cierre:** conclusiones, recomendaciones, bibliografía y anexos (scripts SQL, QR del repositorio, manual de usuario).

### Demostración
- Programar una cita y mostrar que **solo aparecen horarios libres**.
- Intentar la misma hora con otro paciente: lo rechaza. Si el docente insiste, mostrar la restricción `EXCLUDE` de PostgreSQL.
- Intentar una hora fuera del horario o una fecha pasada: lo rechaza.
- Reprogramar (queda en el historial) y cancelar con motivo (el horario se libera).
- Intentar reprogramar una cita cancelada: no se permite y se ofrece crear una nueva.
- Como médico, ver la agenda del día, atender, registrar el diagnóstico y el tratamiento, y que la cita quede *Atendida*.
- Mostrar el historial del paciente con citas pasadas y canceladas.
- Generar reportes por fecha, médico, especialidad y estado, exportar a CSV e imprimir a PDF.
- Como paciente, reservar desde el portal, ver el recordatorio y cancelar.

### Preguntas probables y respuesta corta
| Pregunta | Respuesta |
|---|---|
| ¿Cómo evitan dos citas al mismo tiempo para el mismo médico? | Con tres capas: (1) solo se muestran bloques libres; (2) al guardar, se bloquea el médico con `SELECT … FOR UPDATE` dentro de una transacción y se vuelve a verificar; (3) PostgreSQL tiene una restricción `EXCLUDE USING gist` que impide rangos superpuestos aunque alguien inserte por SQL. |
| ¿Cómo se calculan los horarios disponibles? | Se toma el horario del día, se divide en bloques de `DURACION_CITA_MINUTOS` y se quitan los pasados, los ocupados (excepto las canceladas) y los bloqueos de agenda. |
| ¿Qué estados tiene una cita y qué transiciones permiten? | Programada → Confirmada / Atendida / Inasistencia / Cancelada, y Confirmada → Atendida / Inasistencia / Cancelada. Atendida, Cancelada e Inasistencia son finales. Se controla en PHP (`CitaService::TRANSICIONES`) y en el trigger `validar_cita`. |
| ¿Por qué una cancelada no ocupa horario? | `ESTADOCITA.ocupahorario = false` y la restricción `EXCLUDE` tiene `WHERE codigoestado <> 'CANCELADA'`. |
| ¿Qué política aplicaron a canceladas y atendidas? | No se reprograman: se registra una cita nueva, y el sistema ofrece el botón con los datos ya cargados. |
| ¿Cómo funcionan los recordatorios? | Al programar se crea una `NOTIFICACION` con `programadapara` = cita − 24 h, que el paciente ve en su portal desde ese momento. |

### Posibles requisitos extra que podrían agregar
- **Subir el archivo del resultado** de un estudio (PDF o imagen) al servidor, en lugar de solo un enlace; hoy CU18–CU19 guardan el resultado en texto y un enlace.
- Envío real de recordatorios por correo o WhatsApp con una tarea programada.
- Reporte en PDF generado en el servidor (con una librería como Dompdf).
- Lista de espera cuando no hay horarios disponibles.
- Calendario semanal o mensual de la agenda.
- Límite de citas por paciente por día o por especialidad.

---

## Lista rápida antes de cada presentación
- [ ] Índice de la documentación actualizado.
- [ ] Demo abierta en https://nova-salud-clinica.vercel.app y probada con las cuatro cuentas.
- [ ] Repositorio de GitHub actualizado y QR impreso en la carátula o en los anexos.
- [ ] Diagramas exportados como imagen dentro del documento.
- [ ] Capturas de las pruebas con el resultado esperado y el obtenido.
