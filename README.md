<div align="center">

# Nova Salud · Sistema Web de Gestión de Citas Médicas

**Proyecto de Examen Parcial II** — Desarrollo web con PHP y PostgreSQL
Caso de estudio: *Clínica Vida Salud*

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](#tecnologías)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-4169E1?logo=postgresql&logoColor=white)](#base-de-datos)
[![PDO](https://img.shields.io/badge/PDO-pdo__pgsql-0d9488)](#seguridad)
[![Vercel](https://img.shields.io/badge/Demo-Vercel-000?logo=vercel)](https://nova-salud-clinica.vercel.app)

**Demo en línea:** https://nova-salud-clinica.vercel.app · **Repositorio:** https://github.com/Kato-001/nova-salud

| Repositorio | Demo |
|:-:|:-:|
| <img src="Docs/qr-repositorio.png" width="150" alt="QR del repositorio"> | <img src="Docs/qr-demo.png" width="150" alt="QR de la demo"> |

</div>

---

## Integrantes

| Integrante | Registro |
|---|---|
| Sergio Estaca Paco | 221089421 |
| Frank Miguel Yerbales Molina | 218164629 |

**Universidad Autónoma Gabriel René Moreno** · Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones

**Grupo:** # 20 · **Área:** Ingeniería en Requisitos y Modelado · **Docente:** M.Sc. Ing. Angelica Garzon Cuellar

## Documentación

| Documento | Contenido |
|---|---|
| [📘 Informe del Examen Parcial II (PDF)](Docs/informe/Informe_NovaSalud_Grupo20.pdf) · [Word](Docs/informe/Informe_NovaSalud_Grupo20.docx) | **Informe vigente**, con la tabla de contenido del Examen Parcial II: perfil (con requisitos funcionales y no funcionales), marco teórico, modelo de negocio, flujos de trabajo de captura de requisitos, análisis, diseño (arquitectura, diagramas de secuencia y diseño de datos), implementación (herramientas y diagramas de componentes del sistema y de cada subsistema), pruebas de caja negra de los casos de uso (33 de 33 correctas), conclusión, recomendación, bibliografía, publicación del código (URL + QR) y anexos. 282 páginas en formato APA 7. |
| [🧪 Pruebas de caja negra](Docs/pruebas) | `pruebas_caja_negra.py` ejecuta las 33 pruebas de los casos de uso (interfaz, entrada, condición, salida y procedimiento) y guarda el resultado en `resultados.json`. |
| [🎤 Guion de presentación (HTML)](Docs/Guion_Presentacion.html) | Guion paso a paso para la defensa, las 10 pruebas del enunciado, preguntas probables con sus respuestas y qué hacer si algo falla. Descargar y abrir en el navegador. |
| [📐 Diagramas UML](Docs/diagramas) | 153 figuras con la notación y el estilo de Enterprise Architect, ordenadas por etapa: `1 Modelo de negocio`, `2 Casos de Uso` (`Casos de Uso 1 (Ciclo I)`, `Casos de Uso 2 (Ciclo II)` y prototipos), `3 Análisis`, `4 Diseño` y `5 Implementación`. Incluyen: negocio, Ishikawa por etapas, árbol de problemas, modelo de dominio, casos de uso (general, por ciclo y por paquete), prototipos, diagrama de casos de uso, comunicación, clases de análisis y secuencia de cada uno de los 20 CU, paquetes de análisis (relación con sus CU y vista con actores), clases de análisis por ciclo, estados, despliegue, paquetes, componentes (principal y por paquete), implementación de la arquitectura del sistema y de cada subsistema (estereotipos «main», «file», «library», «engine», «script», «table», «form», «report», «class», «PDF» y «Excel»), clases persistentes, modelo relacional y trazabilidad. Las fuentes `.puml` y los scripts que las dibujan están en `fuentes/` (`python renderizar_ea.py`). |
| [🖼️ Capturas de pantalla](Docs/capturas) | 57 pantallas del sistema funcionando, de cada rol. Se toman con `tomar_capturas.js`. |
| [📝 Diagrama de clases original](Docs/informe/base_de_datos/Diagrama%20de%20Clinica.svg) | Diagrama de clases del que parte la base de datos. |

---

## Tabla de contenido

1. [Descripción](#descripción)
2. [¿Para quién es el sistema?](#para-quién-es-el-sistema)
3. [Ciclo 1 y Ciclo 2](#ciclo-1-y-ciclo-2)
4. [Objetivos](#objetivos)
5. [Funcionalidades](#funcionalidades)
6. [Roles y permisos](#roles-y-permisos)
7. [Proceso Unificado · Flujos de trabajo (PUDS-UML)](#proceso-unificado--flujos-de-trabajo-puds-uml)
   - [1. Captura de requisitos](#1-captura-de-requisitos)
   - [2. Análisis](#2-análisis)
   - [3. Diseño](#3-diseño)
   - [Trazabilidad](#trazabilidad-trace)
8. [Tecnologías](#tecnologías)
9. [Arquitectura](#arquitectura)
10. [Base de datos](#base-de-datos)
11. [Reglas de negocio](#reglas-de-negocio-cómo-se-cumplen)
12. [Seguridad](#seguridad)
13. [Instalación local](#instalación-local)
14. [Despliegue en Vercel](#despliegue-en-vercel)
15. [Cuentas de prueba](#cuentas-de-prueba)
16. [Pruebas](#pruebas)
17. [Estructura del proyecto](#estructura-del-proyecto)

---

## Descripción

La Clínica Vida Salud administraba sus citas con cuadernos, llamadas y hojas de cálculo, lo que provocaba citas duplicadas, datos de pacientes perdidos, desconocimiento de los horarios disponibles y falta de reportes.

**Nova Salud** centraliza esa información en una aplicación web: registra pacientes, médicos, especialidades y horarios, **programa citas mostrando solo los horarios realmente libres** y controla sus estados (programada, confirmada, atendida, cancelada, inasistencia). Incluye agenda médica, historial por paciente, atención médica con tratamientos, reportes administrativos y un portal donde el paciente reserva y sigue sus propias citas.

## ¿Para quién es el sistema?

El sistema tiene **dos tipos de usuarios**, que entran por la **misma página de ingreso** y ven un menú distinto según su rol:

| | Personal de la clínica (uso interno) | Pacientes (uso público) |
|---|---|---|
| **Quiénes** | Administrador, recepcionistas y médicos | Cualquier persona que se atienda en la clínica |
| **Cómo obtienen la cuenta** | La crea el administrador (o se crea al registrar un médico) | Se registra sola en **/registro**, o la recepción le crea la cuenta al registrarla |
| **Qué hacen** | Gestionan pacientes, médicos, horarios y citas; atienden; generan reportes | Reservan y cancelan sus citas, actualizan su perfil y ven sus recordatorios y su historial |
| **Requisitos que cubre** | Secciones 2, 3.1–3.3 y RF01–RF13 del enunciado | Sección 3.4 *Perfil de pacientes para una app de citas médicas* |

El **corazón del sistema es el personal**: el enunciado indica que está "destinado principalmente al personal administrativo", y la recepción es quien programa las citas, incluidas las de los pacientes que llaman o llegan sin cuenta. El **portal del paciente** es el complemento que pide el punto 3.4. Un paciente solo ve sus propios datos y sus propias citas: nunca accede a las pantallas internas (si lo intenta, recibe un 403).

## Ciclo 1 y Ciclo 2

El Proceso Unificado es iterativo: el sistema se construyó en dos ciclos. El primero arma la **base** (seguridad y datos maestros) y el segundo el **núcleo del negocio** (citas, atención médica y reportes). Los 20 casos de uso están implementados.

| Ciclo | Paquetes | Casos de uso | Estado |
|---|---|---|:-:|
| **Ciclo 1** | P1 Seguridad y acceso · P2 Gestión médica · P3 Gestión de pacientes | CU1 Iniciar sesión · CU2 Gestionar roles y permisos · CU3 Gestionar usuarios · CU4 Gestionar especialidades · CU5 Gestionar médicos · CU6 Gestionar horarios · CU7 Gestionar pacientes | ✅ |
| **Ciclo 2** | P4 Gestión de citas · P5 Atención médica · P6 Reportes · Portal del paciente | CU8 Registrarse como paciente · CU9 Consultar disponibilidad · CU10 Reservar ficha · CU11 Reprogramar cita · CU12 Cancelar cita · CU13 Registrar asistencia · CU14 Consultar agenda médica · CU15 Registrar consulta médica · CU16 Gestionar historial clínico · CU17 Registrar tratamiento · CU18 Gestionar estudios médicos · CU19 Consultar historial y resultados · CU20 Generar reportes | ✅ |

## Objetivos

**General.** Desarrollar un sistema web de gestión de citas médicas con PHP y PostgreSQL que mejore el registro, la programación, el seguimiento y el control de las citas de la clínica.

**Específicos**
- Analizar los procesos de registro y programación de citas de la clínica.
- Diseñar una base de datos relacional normalizada con claves primarias y foráneas.
- Implementar el registro, la consulta, la modificación y la desactivación de pacientes y médicos.
- Gestionar especialidades y horarios de atención.
- Programar citas verificando la disponibilidad del médico en el servidor.
- Controlar los estados de las citas y sus transiciones.
- Generar reportes por fecha, médico, especialidad y estado.
- Aplicar validaciones y mecanismos de seguridad.
- Verificar el funcionamiento con pruebas.

## Funcionalidades

| Requerimiento | Implementación |
|---|---|
| **RF01** Inicio de sesión y control de acceso | Login con usuario, correo o CI (pacientes). `password_hash()` / `password_verify()`, sesión regenerada al ingresar, menú según permisos, cierre de sesión, bloqueo tras 5 intentos fallidos por IP y **recuperación de contraseña con un código de 6 dígitos enviado al correo (Gmail)**. |
| **RF02** Gestión de pacientes | Registro con código autogenerado (`PAC-0001`), búsqueda por CI, nombre o apellido, edición, desactivación sin perder el historial, historia clínica y contactos de emergencia. CI único. |
| **RF03** Gestión de médicos | Registro con su cuenta de acceso, una o varias especialidades, búsqueda por nombre o especialidad, ficha con agenda y activación/desactivación. |
| **RF04** Especialidades | CRUD con estado. No se repiten nombres normalizados ("Pediatría" = "pediatria "). |
| **RF05** Horarios de atención | Horarios por día con validación de rangos y cruces, duración de cita configurable y bloqueos de agenda (feriados, vacaciones, permisos). |
| **RF06** Registro de citas | Asistente paso a paso: paciente → especialidad → médico → fecha → **solo horarios libres**. Genera el código `CM-0001` y la fecha de registro. |
| **RF07** Prevención de duplicados | Verificación en PHP dentro de una transacción con bloqueo (`SELECT … FOR UPDATE`) y restricción `EXCLUDE USING gist` en PostgreSQL. |
| **RF08** Reprogramación | Muestra los nuevos horarios libres del mismo médico, guarda el motivo y deja el historial anterior → nuevo. |
| **RF09** Cancelación | Exige un motivo, no borra la cita y libera el horario. |
| **RF10** Asistencia y estados | Programada → Confirmada → Atendida / Inasistencia / Cancelada. Las transiciones inválidas se rechazan en PHP y en un trigger. |
| **RF11** Agenda médica | Por fecha, médico y estado, con los bloques libres intercalados. El médico ve solo su agenda. |
| **RF12** Historial por paciente | Todas sus citas (incluidas canceladas) con médico, especialidad, estado y fecha de registro, además de sus consultas y tratamientos. |
| **RF13** Reportes | Resumen del período, citas por fecha, médico, especialidad y estado, pacientes registrados y agenda diaria. Filtros por rango, totales, **exportación a CSV** e impresión a PDF. |
| Portal del paciente (3.4) | Autoregistro, perfil (datos personales, información médica, contactos de emergencia, cuenta), reserva y cancelación de citas, historial médico y recordatorios automáticos. |
| Atención médica | El médico registra signos vitales, diagnóstico, tratamientos y estudios a solicitar; la cita pasa a *Atendida* en la misma transacción. |
| Estudios médicos (CU18–CU19) | Catálogo de estudios, bandeja de estudios solicitados, registro del resultado (texto y enlace al archivo) y aviso automático al paciente, que ve el resultado en su portal. |
| Auditoría | Bitácora de inicios de sesión, intentos fallidos y cada operación, con usuario, fecha e IP. |

## Roles y permisos

Los permisos se asignan por rol desde **Administración → Roles y permisos** (tabla `ROLPERMISO`). Cada ruta del sistema exige un permiso concreto (RN12).

| Permiso | Admin | Recepción | Médico | Paciente |
|---|:-:|:-:|:-:|:-:|
| Usuarios, roles, configuración, bitácora | ✔ | | | |
| Especialidades, médicos, horarios | ✔ | consulta | | |
| Pacientes | ✔ | ✔ | | |
| Programar, reprogramar y cancelar citas | ✔ | ✔ | | |
| Confirmar e inasistencia | ✔ | ✔ | | |
| Agenda de todos los médicos | ✔ | ✔ | | |
| Agenda propia y atender citas | | | ✔ | |
| Estudios médicos (ver y registrar resultados) | ✔ | ✔ | ✔ (sus pacientes) | |
| Catálogo de estudios | ✔ | | | |
| Reportes | ✔ | ✔ | | |
| Portal del paciente | | | | ✔ |

## Proceso Unificado · Flujos de trabajo (PUDS-UML)

Los artefactos siguen los flujos de trabajo del Proceso Unificado y describen **el sistema tal como está construido**. Cada diagrama se corresponde con archivos reales del repositorio (ver [Trazabilidad](#trazabilidad-trace)). Los diagramas de esta sección están escritos en Mermaid, que GitHub dibuja automáticamente.

> **Versión UML completa:** los 153 diagramas en notación UML están en [`Docs/diagramas`](Docs/diagramas), ordenados por etapa y por ciclo, y en el [informe](Docs/informe/Informe_NovaSalud_Grupo20.pdf). Incluyen la descripción, el diagrama de comunicación y el diagrama de secuencia de **los 20 casos de uso**.

### 1. Captura de requisitos

#### 1.1 Encontrar actores y casos de uso

| Actor | Descripción |
|---|---|
| **Administrador** | Configura y supervisa el sistema. Puede hacer todo lo que hace la recepción, además de gestionar usuarios, roles, médicos, especialidades y horarios. |
| **Recepcionista** | Registra pacientes y programa, reprograma y cancela citas. Registra la asistencia. |
| **Médico** | Consulta su agenda, atiende a sus pacientes y registra la consulta y el tratamiento. |
| **Paciente** | Se registra, reserva y cancela sus citas, mantiene su perfil y consulta su historial. |

| Paquete | Casos de uso |
|---|---|
| **P1 Seguridad y acceso** | CU1 Iniciar sesión · CU2 Gestionar roles y permisos · CU3 Gestionar usuarios (incluye configuración y bitácora) |
| **P2 Gestión médica** | CU4 Gestionar especialidades · CU5 Gestionar médicos · CU6 Gestionar horarios |
| **P3 Gestión de pacientes** | CU7 Gestionar pacientes · CU8 Registrarse como paciente |
| **P4 Gestión de citas** | CU9 Consultar disponibilidad · CU10 Reservar ficha · CU11 Reprogramar cita · CU12 Cancelar cita · CU13 Registrar asistencia · CU14 Consultar agenda médica |
| **P5 Atención médica** | CU15 Registrar consulta médica · CU16 Gestionar historial clínico · CU17 Registrar tratamiento · CU18 Gestionar estudios médicos · CU19 Consultar historial y resultados |
| **P6 Reportes** | CU20 Generar reportes |

**Diagrama general de casos de uso**

```mermaid
flowchart LR
    USR(["👤 Usuario"])
    ADM(["👤 Administrador"])
    REC(["👤 Recepcionista"])
    MED(["👤 Médico"])
    PAC(["👤 Paciente"])

    ADM -. "es un" .-> REC
    REC -. "es un" .-> USR
    MED -. "es un" .-> USR
    PAC -. "es un" .-> USR

    subgraph P1["P1 Seguridad y acceso"]
        CU1(["CU1 Iniciar sesión"])
        CU2(["CU2 Gestionar roles y permisos"])
        CU3(["CU3 Gestionar usuarios"])
    end
    subgraph P2["P2 Gestión médica"]
        CU4(["CU4 Gestionar especialidades"])
        CU5(["CU5 Gestionar médicos"])
        CU6(["CU6 Gestionar horarios"])
    end
    subgraph P3["P3 Gestión de pacientes"]
        CU7(["CU7 Gestionar pacientes"])
        CU8(["CU8 Registrarse como paciente"])
    end
    subgraph P4["P4 Gestión de citas"]
        CU9(["CU9 Consultar disponibilidad"])
        CU10(["CU10 Reservar ficha"])
        CU11(["CU11 Reprogramar cita"])
        CU12(["CU12 Cancelar cita"])
        CU13(["CU13 Registrar asistencia"])
        CU14(["CU14 Consultar agenda médica"])
    end
    subgraph P5["P5 Atención médica"]
        CU15(["CU15 Registrar consulta"])
        CU16(["CU16 Gestionar historial clínico"])
        CU17(["CU17 Registrar tratamiento"])
        CU18(["CU18 Gestionar estudios"])
        CU19(["CU19 Consultar historial y resultados"])
    end
    subgraph P6["P6 Reportes"]
        CU20(["CU20 Generar reportes"])
    end

    USR --- CU1
    ADM --- CU2 & CU3 & CU4 & CU5 & CU6
    REC --- CU7 & CU9 & CU10 & CU11 & CU12 & CU13 & CU14 & CU20
    MED --- CU14 & CU15 & CU16 & CU17 & CU18
    PAC --- CU8 & CU9 & CU10 & CU12 & CU19
```

#### 1.2 Priorizar casos de uso

Primero se construyen los casos de uso **arquitectónicamente significativos**, porque obligan a definir la base de datos, la seguridad y la regla central del negocio: que no haya citas cruzadas.

| Prioridad | Caso de uso | Por qué | Ciclo | Estado |
|:-:|---|---|:-:|:-:|
| 1 | CU1 Iniciar sesión | Toda operación exige sesión y permisos (RF01, RN12) | 1 | ✅ |
| 2 | CU10 Reservar ficha | Es el módulo principal y concentra RN03–RN09 y RN13 | 2 | ✅ |
| 3 | CU9 Consultar disponibilidad | CU10 y CU11 dependen de él | 2 | ✅ |
| 4 | CU6 Gestionar horarios | Sin horarios no hay disponibilidad | 1 | ✅ |
| 5 | CU7 Gestionar pacientes | Una cita necesita un paciente válido | 1 | ✅ |
| 6 | CU5 Gestionar médicos | Una cita necesita un médico activo | 1 | ✅ |
| 7 | CU12 Cancelar · CU11 Reprogramar · CU13 Asistencia | Control de estados de la cita (RF08–RF10) | 2 | ✅ |
| 8 | CU14 Agenda · CU20 Reportes | Consultas para el personal (RF11, RF13) | 2 | ✅ |
| 9 | CU3 Usuarios · CU2 Roles · CU4 Especialidades | Administración | 1 | ✅ |
| 10 | CU15 Consulta · CU17 Tratamiento · CU16 Historial | Atención médica | 2 | ✅ |
| 11 | CU8 Registrarse · CU19 Historial (portal) | Portal del paciente (3.4) | 2 | ✅ |
| 12 | CU18 Gestionar estudios médicos | Solicitud en la consulta, resultados y aviso al paciente | 2 | ✅ |

#### 1.3 Detallar un caso de uso · CU10 Reservar ficha

| | |
|---|---|
| **Caso de uso** | CU10 Reservar ficha (programar cita) |
| **Actores** | Recepcionista (principal), Administrador, Paciente (desde el portal) |
| **Propósito** | Registrar una cita para un paciente con un médico en un horario realmente disponible |
| **Precondiciones** | El actor inició sesión (CU1) y tiene el permiso `citas.gestionar` (o `portal.paciente`). Existen especialidades, médicos activos y horarios. |
| **Incluye** | CU9 Consultar disponibilidad |
| **Postcondiciones** | La cita queda en estado PROGRAMADA con un código `CM-####` y su fecha de registro, más una entrada en el historial, un recordatorio para el paciente y un registro en la bitácora |

**Flujo principal**
1. El actor elige **Programar cita**.
2. El sistema muestra el asistente: paciente → especialidad → médico → fecha → hora.
3. El actor escribe el CI o el nombre del paciente. El sistema muestra solo los pacientes **activos** que coinciden.
4. El actor elige la especialidad. El sistema carga los médicos **activos** que la atienden.
5. El actor elige el médico. El sistema marca los días en que ese médico atiende.
6. El actor elige la fecha. El sistema calcula los bloques de la duración configurada (CU9) y muestra **solo los libres**.
7. El actor elige la hora, opcionalmente escribe la modalidad y el motivo, y confirma.
8. El sistema **vuelve a verificar en el servidor**, dentro de una transacción que bloquea al médico, que el bloque sigue libre (RN13).
9. El sistema registra la cita, el historial, el recordatorio y la bitácora, y muestra el código generado.

**Flujos alternativos**
- **3a. El paciente no existe:** el sistema ofrece registrarlo (CU7) y luego volver al paso 3.
- **6a. El médico no atiende ese día,** el día está bloqueado o ya no quedan bloques: el sistema informa el motivo y pide otra fecha.
- **8a. Otro usuario tomó el mismo bloque segundos antes:** el sistema rechaza la cita con el mensaje "El horario ya no está disponible: el médico tiene otra cita" y vuelve al paso 6 (RF07).
- **8b. Datos manipulados** (fecha pasada, hora fuera de horario, médico que no atiende la especialidad, médico inactivo): el sistema rechaza la operación con un mensaje explicativo (RN04–RN06, RN08).

#### 1.4 Prototipar la interfaz de usuario

Los prototipos se construyeron como pantallas funcionales. Prototipo de **CU10 Reservar ficha**:

```
┌────────────────────────────────────────────────────────────────────────────┐
│ ◀ Citas                                                                    │
│ Programar cita                                                             │
│ (1 Paciente) ── (2 Especialidad y médico) ── (3 Fecha) ── (4 Hora)         │
├───────────────────────────────────────────────────┬────────────────────────┤
│ PACIENTE                                          │ ▓▓ NUEVA CITA ▓▓       │
│ [🔍 CI, nombre o apellido…                    ]   │   CM-····              │
│ ESPECIALIDAD Y MÉDICO                             │ Paciente  Juan Pérez   │
│ [Medicina General ▾]   [Dr. Carlos Mendoza ▾]     │ Especial. Med. General │
│ FECHA                                             │ Médico    Dr. Mendoza  │
│ [Hoy 24] [Lun 26] [Mar 27] [Mié 28] [Jue 29] …    │ Fecha     Mar 27 oct   │
│ HORA DISPONIBLE                                   │ Horario   08:30–09:00  │
│ [08:00] [08:30] [0̶9̶:̶0̶0̶] [09:30] [1̶0̶:̶0̶0̶] …        │                        │
│ DETALLES  [Presencial ▾]  [Motivo…            ]   │ [ Confirmar cita ]     │
└───────────────────────────────────────────────────┴────────────────────────┘
```

| CU | Interfaz (ruta) |
|---|---|
| CU1 | `/login`, `/recuperar`, `/recuperar/codigo` |
| CU2 | `/roles` (matriz de permisos) |
| CU3 | `/usuarios`, `/usuarios/nuevo`, `/configuracion`, `/bitacora` |
| CU4 | `/especialidades` |
| CU5 | `/medicos`, `/medicos/nuevo`, `/medicos/ver` |
| CU6 | `/horarios` (horarios y bloqueos) |
| CU7 | `/pacientes`, `/pacientes/nuevo`, `/pacientes/ver` |
| CU8 | `/registro` |
| CU9 · CU10 | `/citas/nueva`, `/portal/reservar` |
| CU11 | `/citas/reprogramar` |
| CU12 · CU13 | `/citas/ver` (acciones según el estado), `/portal/citas` |
| CU14 | `/agenda` |
| CU15 · CU17 | `/atencion` |
| CU16 | `/pacientes/ver?tab=clinica`, `/portal/perfil?seccion=medica` |
| CU18 | `/estudios`, `/estudios/resultado`, `/estudios/catalogo` (y en `/atencion`) |
| CU19 | `/portal/historial` |
| CU20 | `/reportes` |

#### 1.5 Estructurar el modelo de casos de uso

```mermaid
flowchart LR
    CU10(["CU10 Reservar ficha"]) -. "«include»" .-> CU9(["CU9 Consultar disponibilidad"])
    CU11(["CU11 Reprogramar cita"]) -. "«include»" .-> CU9
    CU8(["CU8 Registrarse como paciente"]) -. "«include»" .-> CU7(["CU7 Gestionar pacientes"])
    CU15(["CU15 Registrar consulta"]) -. "«include»" .-> CU13(["CU13 Registrar asistencia"])
    CU17(["CU17 Registrar tratamiento"]) -. "«extend»" .-> CU15
    CU18(["CU18 Gestionar estudios"]) -. "«extend»" .-> CU15
    CU15 -. "«include»" .-> CU16(["CU16 Gestionar historial clínico"])
    CU5(["CU5 Gestionar médicos"]) -. "«include»" .-> CU3(["CU3 Gestionar usuarios"])
    CU3 -. "«include»" .-> CU2(["CU2 Gestionar roles y permisos"])
```

- **«include»:** el caso base siempre ejecuta al incluido. Por ejemplo, reservar siempre consulta la disponibilidad, y registrar un médico siempre crea su cuenta de usuario.
- **«extend»:** comportamiento opcional. El tratamiento y los estudios se agregan solo si el médico los indica.
- **Generalización de actores:** el Administrador hereda los casos de uso del Recepcionista, y todos los actores heredan CU1 de *Usuario*.

### 2. Análisis

#### 2.1 Análisis de la arquitectura

Los paquetes de análisis agrupan casos de uso relacionados. Las flechas indican dependencia.

```mermaid
flowchart TB
    PORTAL["📦 Portal del paciente"]
    P6["📦 P6 Reportes"]
    P5["📦 P5 Atención médica"]
    P4["📦 P4 Gestión de citas"]
    P3["📦 P3 Gestión de pacientes"]
    P2["📦 P2 Gestión médica"]
    P1["📦 P1 Seguridad y acceso"]

    PORTAL --> P4 & P3 & P5
    P6 --> P4 & P3
    P5 --> P4 & P3
    P4 --> P3 & P2
    P2 --> P1
    P3 --> P1
    P4 --> P1
```

#### 2.2 Analizar casos de uso · Diagrama de comunicación de CU10

```mermaid
flowchart LR
    R(["👤 Recepcionista"])
    IU["«boundary»<br/>IU_ProgramarCita"]
    C["«control»<br/>CtrlCita"]
    D["«control»<br/>CtrlDisponibilidad"]
    PA["«entity»<br/>Paciente"]
    ME["«entity»<br/>Medico"]
    HO["«entity»<br/>Horario"]
    BL["«entity»<br/>BloqueoAgenda"]
    CI["«entity»<br/>Cita"]
    HI["«entity»<br/>HistorialCita"]
    NO["«entity»<br/>Notificacion"]

    R -- "1: buscarPaciente(q)<br/>4: elegirFecha(f)<br/>7: confirmar()" --> IU
    IU -- "2: buscarPaciente(q)<br/>8: programar(datos)" --> C
    C -- "3: buscar(q)" --> PA
    IU -- "5: consultarDisponibilidad(medico, f)" --> D
    D -- "5.1: horariosDelDia()" --> HO
    D -- "5.2: bloqueos()" --> BL
    D -- "5.3: citasQueOcupan()" --> CI
    C -- "8.1: bloquear() y estaActivo()" --> ME
    C -- "8.2: verificar disponibilidad" --> D
    C -- "8.3: crear()" --> CI
    C -- "8.4: registrarCambio()" --> HI
    C -- "8.5: programar recordatorio" --> NO
```

#### 2.3 Análisis de clases

Clases de análisis (interfaz, control y entidad) de los casos de uso principales:

```mermaid
classDiagram
    class IU_Login
    <<boundary>> IU_Login
    class IU_ProgramarCita
    <<boundary>> IU_ProgramarCita
    class IU_Agenda
    <<boundary>> IU_Agenda
    class IU_FichaPaciente
    <<boundary>> IU_FichaPaciente

    class CtrlSesion {
        +iniciarSesion(login, clave)
        +cerrarSesion()
        +tienePermiso(codigo)
    }
    <<control>> CtrlSesion
    class CtrlCita {
        +programar(datos)
        +reprogramar(cita, fecha, hora, motivo)
        +cancelar(cita, motivo)
        +cambiarEstado(cita, estado)
    }
    <<control>> CtrlCita
    class CtrlDisponibilidad {
        +bloques(medico, fecha)
        +estaLibre(medico, fecha, hora)
    }
    <<control>> CtrlDisponibilidad
    class CtrlPaciente {
        +registrar(datos)
        +buscar(q)
        +desactivar(id)
    }
    <<control>> CtrlPaciente

    class Usuario
    <<entity>> Usuario
    class Rol
    <<entity>> Rol
    class Paciente
    <<entity>> Paciente
    class Medico
    <<entity>> Medico
    class Horario
    <<entity>> Horario
    class Cita
    <<entity>> Cita
    class EstadoCita
    <<entity>> EstadoCita
    class HistorialCita
    <<entity>> HistorialCita

    IU_Login --> CtrlSesion
    IU_ProgramarCita --> CtrlCita
    IU_ProgramarCita --> CtrlDisponibilidad
    IU_Agenda --> CtrlDisponibilidad
    IU_FichaPaciente --> CtrlPaciente
    CtrlSesion --> Usuario
    Usuario --> Rol
    CtrlCita --> Cita
    CtrlCita --> HistorialCita
    CtrlDisponibilidad --> Horario
    CtrlDisponibilidad --> Cita
    CtrlPaciente --> Paciente
    Cita --> Paciente
    Cita --> Medico
    Cita --> EstadoCita
    Medico --> Horario
```

#### 2.4 Analizar paquetes

| Paquete | Interfaz «boundary» | Control «control» | Entidad «entity» | CU |
|---|---|---|---|---|
| P1 Seguridad y acceso | IU_Login, IU_Usuarios, IU_Roles, IU_Configuración, IU_Bitácora | CtrlSesion, CtrlUsuario, CtrlRol | Usuario, Rol, Permiso, Configuracion, Bitacora | CU1–CU3 |
| P2 Gestión médica | IU_Especialidades, IU_Médicos, IU_Horarios | CtrlMedico, CtrlHorario | Especialidad, Medico, MedicoEspecialidad, Horario, BloqueoAgenda | CU4–CU6 |
| P3 Gestión de pacientes | IU_Pacientes, IU_FichaPaciente, IU_Registro | CtrlPaciente | Paciente, HistoriaClinica, ContactoEmergencia | CU7–CU8 |
| P4 Gestión de citas | IU_ProgramarCita, IU_DetalleCita, IU_Reprogramar, IU_Agenda | CtrlCita, CtrlDisponibilidad | Cita, EstadoCita, HistorialCita, Notificacion | CU9–CU14 |
| P5 Atención médica | IU_Consulta, IU_Estudios, IU_Historial | CtrlAtencion, CtrlEstudio | Consulta, Tratamiento, Medicamento, Estudio, CatalogoEstudio | CU15–CU19 |
| P6 Reportes | IU_Reportes | CtrlReporte | (consulta a Cita, Paciente, Medico) | CU20 |

### 3. Diseño

#### 3.1 Diseño de la arquitectura

##### 3.1.1 Diseño físico · Diagrama de despliegue

```mermaid
flowchart LR
    subgraph CLI["«dispositivo» PC o celular"]
        NAV["Navegador web<br/>HTML5 · CSS3 · JavaScript"]
    end
    subgraph VER["«nodo» Vercel · región gru1 (São Paulo)"]
        CDN["CDN<br/>Frontend/public/assets (CSS, JS, íconos)"]
        FN["Función serverless<br/>PHP 8.3 · vercel-php<br/>api/index.php"]
    end
    subgraph SUP["«nodo» Supabase · sa-east-1 (São Paulo)"]
        POOL["Supavisor<br/>pooler :6543"]
        DB[("PostgreSQL 17<br/>esquema clinica")]
    end
    NAV -- "HTTPS" --> CDN
    NAV -- "HTTPS (cookie de sesión)" --> FN
    FN -- "TCP/TLS · PDO pdo_pgsql" --> POOL
    POOL --> DB
```

**Despliegue alternativo en local (XAMPP / Laragon):** el navegador se conecta por HTTP a Apache con PHP 8 (que procesa el `.htaccess` y `api/index.php`), y este a PostgreSQL local por PDO en el puerto 5432.

##### 3.1.2 Diseño lógico · Diagrama de paquetes

```mermaid
flowchart TB
    subgraph PRES["📦 Presentación"]
        VIEWS["Frontend/views<br/>P1…P6 · Portal · layouts · partials"]
        ASSETS["Frontend/public/assets<br/>app.css · componentes.css · app.js · citas.js"]
    end
    subgraph CTRL["📦 Control"]
        FRONT["api/index.php<br/>controlador frontal y rutas"]
        CONTROLLERS["Bakend/app/controllers<br/>Auth, Usuario, Rol, Medico, Horario,<br/>Paciente, Cita, Agenda, Atencion, Reporte, Portal, Api"]
    end
    subgraph NEG["📦 Lógica de negocio"]
        SERV["Bakend/app/services<br/>CitaService · Disponibilidad"]
    end
    subgraph DAT["📦 Acceso a datos"]
        MODELS["Bakend/app/models<br/>Usuario, Rol, Paciente, Medico, Horario,<br/>Cita, Consulta, Notificacion, Reporte, Bitacora"]
    end
    subgraph CORE["📦 Núcleo"]
        CORECLS["Bakend/app/core<br/>Router · Auth · Session · Csrf · Validator · View · Database (PDO)"]
    end
    BD[("PostgreSQL")]

    FRONT --> CONTROLLERS
    CONTROLLERS --> VIEWS
    CONTROLLERS --> SERV
    CONTROLLERS --> MODELS
    SERV --> MODELS
    MODELS --> CORECLS
    CONTROLLERS --> CORECLS
    VIEWS --> ASSETS
    CORECLS --> BD
```

#### 3.2 Diseñar casos de uso · Diagramas de secuencia

**CU1 Iniciar sesión**

```mermaid
sequenceDiagram
    actor U as Usuario
    participant N as Navegador
    participant R as Router
    participant AC as AuthController
    participant A as Auth
    participant DB as PostgreSQL

    U->>N: escribe usuario/correo y contraseña
    N->>R: POST /login (token CSRF)
    R->>R: Csrf::verificar()
    R->>AC: ingresar()
    AC->>AC: Validator (campos obligatorios)
    AC->>A: intentar(login, clave)
    A->>DB: contar intentos fallidos de la IP (10 min)
    alt 5 o más intentos
        A-->>AC: AppException "Demasiados intentos"
    else
        A->>DB: SELECT usuario por username, correo o CI
        A->>A: password_verify(clave, hash)
        alt credenciales inválidas
            A->>DB: INSERT bitacora LOGIN_FALLIDO
            A-->>AC: AppException "Usuario o contraseña incorrectos"
        else cuenta INACTIVA
            A-->>AC: AppException "Cuenta desactivada"
        else correcto
            A->>A: session_regenerate_id(true)
            A->>DB: UPDATE ultimoacceso y INSERT bitacora INICIAR_SESION
            A-->>AC: usuario con rol y permisos
        end
    end
    AC-->>N: 302 a /panel o /portal (o a /login con mensaje)
```

**CU10 Reservar ficha** (verificación de disponibilidad y prevención de cruces)

```mermaid
sequenceDiagram
    actor RE as Recepcionista
    participant N as Navegador (citas.js)
    participant API as ApiController
    participant CC as CitaController
    participant CS as CitaService
    participant D as Disponibilidad
    participant DB as PostgreSQL

    RE->>N: elige paciente, especialidad y médico
    N->>API: GET /api/medicos?especialidad=…
    API-->>N: médicos activos y días de atención
    RE->>N: elige la fecha
    N->>API: GET /api/disponibilidad?medico=…&fecha=…
    API->>D: bloques(medico, fecha)
    D->>DB: horarios, bloqueos y citas que ocupan
    D-->>API: bloques con libre true o false
    API-->>N: JSON (solo se habilitan los libres)
    RE->>N: elige la hora y confirma
    N->>CC: POST /citas/guardar
    CC->>CS: programar(datos)
    CS->>CS: Validator (fecha mayor o igual a hoy, hora, ids)
    CS->>DB: BEGIN
    CS->>DB: SELECT medico FOR UPDATE (bloquea al médico)
    CS->>DB: paciente activo y médico atiende la especialidad
    CS->>D: estaLibre(medico, fecha, hora) se vuelve a verificar
    alt ocupado o fuera de horario
        CS->>DB: ROLLBACK
        CS-->>CC: AppException con el motivo
        CC-->>N: 302 al formulario con el mensaje de error
    else libre
        CS->>DB: INSERT cita (trigger validar_cita y restricción EXCLUDE)
        CS->>DB: INSERT historialcita PROGRAMAR
        CS->>DB: INSERT notificacion (aviso y recordatorio)
        CS->>DB: COMMIT
        CS->>DB: INSERT bitacora CREAR
        CC-->>N: 302 a /citas/ver con el código de la cita
    end
```

**CU12 Cancelar cita**

```mermaid
sequenceDiagram
    actor RE as Recepcionista
    participant N as Navegador
    participant CC as CitaController
    participant CS as CitaService
    participant DB as PostgreSQL

    RE->>N: Cancelar cita (modal pide el motivo)
    N->>CC: POST /citas/cancelar (idcita, motivo, CSRF)
    CC->>CS: cancelar(idcita, motivo)
    CS->>DB: BEGIN y SELECT cita FOR UPDATE
    alt estado distinto de PROGRAMADA o CONFIRMADA
        CS-->>CC: AppException "no se puede cancelar"
    else
        CS->>DB: UPDATE cita SET codigoestado = CANCELADA
        CS->>DB: INSERT historialcita CANCELAR con motivo
        CS->>DB: DELETE recordatorios pendientes e INSERT aviso
        CS->>DB: COMMIT
        Note over DB: la cita no se borra y deja de ocupar el horario (RN09)
    end
    CC-->>N: 302 a /citas/ver con el resultado
```

#### 3.3 Diseño de datos

- **Modelo lógico:** el diagrama de clases [`Docs/informe/base_de_datos/Diagrama de Clinica.svg`](Docs/informe/base_de_datos/Diagrama%20de%20Clinica.svg).
- **Modelo físico:** [`Bakend/database/01_esquema.sql`](Bakend/database/01_esquema.sql).
- **Relaciones principales del núcleo de citas:**

```mermaid
erDiagram
    ROL ||--o{ USUARIO : tiene
    USUARIO ||--o| MEDICO : pertenece
    USUARIO ||--o| PACIENTE : pertenece
    MEDICO ||--o{ MEDICOESPECIALIDAD : tiene
    ESPECIALIDAD ||--o{ MEDICOESPECIALIDAD : clasifica
    MEDICO ||--o{ HORARIO : tiene
    MEDICO ||--o{ BLOQUEOAGENDA : tiene
    PACIENTE ||--o{ CITA : reserva
    MEDICO ||--o{ CITA : atiende
    ESPECIALIDAD ||--o{ CITA : tiene
    ESTADOCITA ||--o{ CITA : clasifica
    CITA ||--o{ HISTORIALCITA : registra
    CITA ||--o| CONSULTA : genera
    CONSULTA ||--o{ TRATAMIENTO : indica
    MEDICAMENTO ||--o{ TRATAMIENTO : "se usa en"
    PACIENTE ||--o| HISTORIACLINICA : tiene
    PACIENTE ||--o{ CONTACTOEMERGENCIA : tiene
    CITA ||--o{ NOTIFICACION : genera

    CITA {
        int idcita PK
        varchar codigo UK
        date fecha
        time horainicio
        time horafin
        varchar modalidad
        varchar motivo
        timestamptz fecharegistro
        int idpaciente FK
        int idmedico FK
        int idespecialidad FK
        varchar codigoestado FK
    }
```

### Trazabilidad (trace)

Cada modelo se deriva del anterior: **modelo de casos de uso → modelo de análisis → modelo de diseño → modelo de despliegue → implementación**.

```mermaid
flowchart LR
    MCU["📁 Modelo de<br/>casos de uso"] -. "«trace»" .-> MAN["📁 Modelo de<br/>análisis"]
    MAN -. "«trace»" .-> MDI["📁 Modelo de<br/>diseño"]
    MDI -. "«trace»" .-> MDE["📁 Modelo de<br/>despliegue"]
    MDI -. "«trace»" .-> IMP["📁 Implementación<br/>(código PHP y SQL)"]
```

| Caso de uso | Clases de análisis | Clases de diseño (archivos) | Nodo |
|---|---|---|---|
| CU1 Iniciar sesión | IU_Login, CtrlSesion, Usuario, Rol | `Frontend/views/P1_SeguridadAcceso/login.php`, `AuthController`, `core/Auth`, `core/Session`, `models/Bitacora` | Vercel · Supabase |
| CU7 Gestionar pacientes | IU_Pacientes, CtrlPaciente, Paciente | `Frontend/views/P3_GestionPacientes/pacientes/*`, `PacienteController`, `models/Paciente` | Vercel · Supabase |
| CU9 Consultar disponibilidad | IU_ProgramarCita, CtrlDisponibilidad, Horario, Cita | `services/Disponibilidad`, `ApiController::disponibilidad`, `Frontend/public/assets/js/citas.js` | Vercel · Supabase |
| CU10 Reservar ficha | IU_ProgramarCita, CtrlCita, Cita, HistorialCita | `Frontend/views/P4_GestionCitas/citas/nueva.php`, `CitaController`, `services/CitaService::programar`, trigger `validar_cita`, `EXCLUDE cita_sin_cruce_medico` | Vercel · Supabase |
| CU11 Reprogramar | IU_Reprogramar, CtrlCita | `citas/reprogramar.php`, `CitaService::reprogramar` | Vercel · Supabase |
| CU12 Cancelar | IU_DetalleCita, CtrlCita | `citas/ver.php`, `CitaService::cancelar` | Vercel · Supabase |
| CU14 Agenda | IU_Agenda, CtrlDisponibilidad | `views/P4_GestionCitas/agenda/index.php`, `AgendaController` | Vercel · Supabase |
| CU15 · CU17 Consulta y tratamiento | IU_Consulta, CtrlAtencion, Consulta, Tratamiento | `views/P5_AtencionMedica/consulta.php`, `AtencionController`, `CitaService::atender`, `models/Consulta` | Vercel · Supabase |
| CU20 Reportes | IU_Reportes, CtrlReporte | `views/P6_Reportes/index.php`, `ReporteController`, `models/Reporte` | Vercel · Supabase |

## Tecnologías

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.3 sin framework, con arquitectura MVC propia |
| Acceso a datos | PDO con el controlador `pdo_pgsql` y consultas parametrizadas |
| Base de datos | PostgreSQL 17 (Supabase) con las extensiones `btree_gist` y `pgcrypto` |
| Frontend | HTML5, CSS3 (variables, modo claro/oscuro, animaciones) y JavaScript sin librerías |
| Servidor local | Apache (XAMPP/Laragon) o el servidor integrado de PHP |
| Producción | Vercel (`vercel-php`, región São Paulo) y Supabase (región São Paulo) |

## Arquitectura

```
Navegador ──HTTP──▶ api/index.php (controlador frontal + rutas)
                         │
                         ▼
          Bakend/app/core  ── Router · Auth · Csrf · Session · Validator · View · Database (PDO)
                         │
        ┌────────────────┼──────────────────┐
        ▼                ▼                  ▼
 Bakend/app/       Bakend/app/        Frontend/views
 controllers       services
 (reciben la       (reglas de          (plantillas PHP
  petición,         negocio: citas,     por paquete P1…P6
  validan)          disponibilidad)     y portal)
        │                │
        └──────▶ Bakend/app/models (acceso a datos con SQL parametrizado)
                         │
                         ▼
                PostgreSQL (esquema "clinica": tablas, restricciones y triggers)
```

- **Separación de responsabilidades:**
  - Los controladores no contienen SQL.
  - Los modelos no generan HTML.
  - Las reglas de citas están en `services/CitaService.php` y `services/Disponibilidad.php`.
- **Patrón Post/Redirect/Get** con mensajes *flash*: al recargar la página no se reenvía el formulario.
- **Organización por paquetes** del análisis: P1 Seguridad, P2 Gestión médica, P3 Pacientes, P4 Citas, P5 Atención médica, P6 Reportes.

## Base de datos

- **Diagrama de clases:** [`Docs/informe/base_de_datos/Diagrama de Clinica.svg`](Docs/informe/base_de_datos/Diagrama%20de%20Clinica.svg)
- **Scripts:** en [`Bakend/database/`](Bakend/database). Se ejecutan en orden:

| Script | Contenido |
|---|---|
| `01_esquema.sql` | 21 tablas del diagrama más `PERMISO`, `ROLPERMISO` y `SESION`. PK, FK, `UNIQUE`, `CHECK` y restricciones `EXCLUDE`. |
| `02_reglas_negocio.sql` | Trigger `validar_cita` (RN04–RN08, RF08, RF10), protección de roles del sistema y RLS. |
| `03_datos_iniciales.sql` | Roles, permisos, configuración, estados, especialidades, médicos con horarios, pacientes y unas 240 citas de ejemplo alrededor de la fecha actual. |
| `04_usuario_app.sql` | Usuario de base de datos con privilegios mínimos para la aplicación. |
| `05_consultas.sql` | 30 consultas de ejemplo: 10 simples, 10 de varias tablas y 10 subconsultas (solo lectura). |
| `06_procedimientos_triggers.sql` | 5 procedimientos almacenados (horarios libres, cancelar cita, desactivar paciente, resumen de citas, edad) y 3 disparadores (normalizar paciente, auditar estado del médico, fecha del resultado de un estudio). |
| `00_limpiar_version_anterior.sql` | (Opcional) borra una versión de prueba anterior. |

**Relaciones principales:**
- Un paciente tiene muchas citas, y un médico atiende muchas citas.
- Médico ↔ especialidad es una relación muchos a muchos (`MEDICOESPECIALIDAD`).
- Un médico tiene varios horarios y varios bloqueos de agenda.
- Una cita tiene un historial de cambios (`HISTORIALCITA`), una consulta y notificaciones.
- Una consulta tiene tratamientos y estudios.

## Reglas de negocio (cómo se cumplen)

| Regla | PHP | PostgreSQL |
|---|---|---|
| RN01 / RN02 Identificador único | Código autogenerado | `PRIMARY KEY`, `UNIQUE (codigo)`, `UNIQUE (ci)` |
| RN03 Paciente y médico válidos | `CitaService::programar` | `FOREIGN KEY` |
| RN04 Médico activo | `Disponibilidad::bloques` | trigger `validar_cita` |
| RN05 Dentro del horario | Solo se ofrecen bloques del horario | trigger `validar_cita` |
| RN06 Sin fechas pasadas | `Validator::fecha(min: hoy)` | trigger `validar_cita` |
| RN07 Sin superposición | Transacción + `FOR UPDATE` + verificación | `EXCLUDE USING gist (idmedico WITH =, tsrange WITH &&)` |
| RN08 Duración configurada | Bloques de `DURACION_CITA_MINUTOS` | trigger `validar_cita` |
| RN09 Canceladas no ocupan | `estadocita.ocupahorario` | `EXCLUDE … WHERE codigoestado <> 'CANCELADA'` |
| RN10 Historial de atendidas | No se borran citas | `HISTORIALCITA` |
| RN11 No eliminar con historial | Solo se activa o desactiva | `FOREIGN KEY` sin `CASCADE` |
| RN12 Solo usuarios autorizados | `Auth::requerir('permiso')` en cada acción | El usuario `headsalud_app` solo tiene permisos de datos |
| RN13 Disponibilidad en el servidor | Se vuelve a comprobar dentro de la transacción | Restricción `EXCLUDE` |
| RN14 Persistencia en PostgreSQL | PDO | — |

## Seguridad

- **Contraseñas:** se guardan con `password_hash()` (bcrypt) y se verifican con `password_verify()`. El hash se actualiza automáticamente con `password_needs_rehash()`.
- **Inyección SQL:** todas las consultas usan PDO con parámetros (`:nombre`). Nunca se concatenan datos del usuario.
- **XSS:** toda salida pasa por `e()` (`htmlspecialchars`).
- **CSRF:** cada formulario POST lleva un token de sesión que se verifica con `hash_equals`.
- **Sesiones:**
  - Cookies `HttpOnly`, `SameSite=Lax` y `Secure` en HTTPS.
  - El ID de sesión se regenera al iniciar sesión.
  - Las sesiones caducan tras 2 horas de inactividad.
  - Se guardan en la tabla `SESION`, porque Vercel es serverless.
- **Control de acceso:**
  - Cada petición recarga el usuario y sus permisos.
  - Una cuenta desactivada pierde la sesión de inmediato.
  - Ninguna página interna se puede abrir sin sesión.
- **Recuperación de cuenta:** "¿Olvidaste tu contraseña?" envía un **código de 6 dígitos** al correo por SMTP (Gmail).
  - El código se guarda con `password_hash()`, es de un solo uso, vence en 15 minutos y admite 5 intentos.
  - Se limitan las solicitudes por cuenta y por IP.
  - La respuesta es la misma exista o no el correo, así que no revela qué cuentas están registradas.
  - Al restablecer la contraseña se cierran las sesiones abiertas de esa cuenta.
- **Fuerza bruta:** se bloquea tras 5 intentos fallidos por IP en 10 minutos, y cada intento queda registrado en la bitácora.
- **Errores:**
  - En producción no se muestran detalles internos: se registran en el log y el usuario ve un mensaje claro.
  - Los errores de PostgreSQL se traducen (`AppException::desdePdo`).
- **Validación doble:** JavaScript en el navegador y `Validator` en PHP aplican las mismas reglas. Se rechaza cualquier texto que no sea UTF-8 válido.
- **Credenciales:** van en variables de entorno (`.env` local, panel de Vercel) y **nunca se suben al repositorio**.

## Instalación local

**Requisitos:** PHP 8.1 o superior con `pdo_pgsql` y `mbstring` (Laragon y XAMPP lo traen), y PostgreSQL 14 o superior (local o Supabase).

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/Kato-001/nova-salud.git && cd nova-salud
   ```
2. **Crear la base de datos.** En PostgreSQL local, por ejemplo con pgAdmin:
   ```sql
   CREATE DATABASE nova_salud;
   ```
   Luego ejecute en orden `01_esquema.sql`, `02_reglas_negocio.sql`, `03_datos_iniciales.sql` y `04_usuario_app.sql` (cambie `CAMBIAR_CLAVE` por su contraseña). Los scripts funcionan igual en PostgreSQL local y en Supabase.
3. **Configurar el entorno**
   ```bash
   cp .env.example .env
   # DATABASE_URL=postgresql://headsalud_app:SU_CLAVE@localhost:5432/nova_salud?sslmode=disable
   ```
4. **Ejecutar**, con una de estas dos opciones:
   - **Servidor integrado de PHP:**
     ```bash
     php -S localhost:8000 router.php
     ```
     Luego abra http://localhost:8000.
   - **Apache (XAMPP/Laragon):** copie la carpeta en `htdocs` / `www`. El `.htaccess` envía todo a `api/index.php` y bloquea el acceso a `Bakend/`, `Frontend/views`, `Docs/` y `.env`.

## Despliegue en Vercel

PHP corre en Vercel gracias al runtime comunitario [`vercel-php`](https://github.com/vercel-community/php) (configurado en `vercel.json`).

```bash
npx vercel link
npx vercel env add DATABASE_URL production   # cadena del pooler de Supabase (puerto 6543)
npx vercel env add APP_ENV production         # valor: production
# Envío del código de recuperación por Gmail (contraseña de aplicación de 16 letras)
npx vercel env add SMTP_HOST production       # smtp.gmail.com
npx vercel env add SMTP_PORT production       # 465
npx vercel env add SMTP_USER production       # correo Gmail remitente
npx vercel env add SMTP_PASS production       # contraseña de aplicación de Gmail
npx vercel deploy --prod
```

- **Archivos estáticos:** las rutas `/assets/*` se sirven desde `Frontend/public/`.
- **Rutas:** todo lo demás pasa por `api/index.php`.
- **Pooler de Supabase:** se usa en modo transacción, por eso `PDO::ATTR_EMULATE_PREPARES` está activo.

## Cuentas de prueba

Página pública: **https://nova-salud-clinica.vercel.app** (especialidades, médicos con sus horarios y botón para reservar; al reservar se pide iniciar sesión como paciente y luego se vuelve directo a la reserva con el médico ya elegido).

Página de ingreso: **https://nova-salud-clinica.vercel.app/login**. Se puede entrar con el **usuario** o con el **correo**; los pacientes también pueden usar su **CI**.

**Contraseña de todas las cuentas: `HeadSalud2026`**

| Rol | Persona | Usuario | Correo | Qué puede hacer |
|---|---|---|---|---|
| Administrador | Administrador General | `admin` | admin@vidasalud.com | Todo el sistema: usuarios, roles y permisos, configuración, bitácora, médicos, especialidades, horarios, pacientes, citas y reportes |
| Recepcionista | María Fernanda Rojas | `mrojas` | recepcion@vidasalud.com | Pacientes; programar, reprogramar y cancelar citas; confirmar y registrar asistencia; agenda de todos los médicos; reportes |
| Recepcionista | Jorge Cuéllar | `jcuellar` | jcuellar@vidasalud.com | Igual que la recepcionista anterior |
| Médico | Dr. Carlos Mendoza (Medicina General, Cardiología) | `cmendoza` | cmendoza@vidasalud.com | Su agenda y la atención de sus pacientes (lunes a viernes, 08:00–12:00) |
| Médico | Dra. Lucía Vargas (Pediatría) | `lvargas` | lvargas@vidasalud.com | Su agenda (lunes, miércoles y viernes 14:00–18:00; martes y jueves 08:00–12:00) |
| Médico | Dr. Roberto Suárez (Odontología) | `rsuarez` | rsuarez@vidasalud.com | Su agenda (lunes a sábado, 09:00–13:00) |
| Médico | Dra. Patricia Molina (Ginecología, Medicina General) | `pmolina` | pmolina@vidasalud.com | Su agenda (martes y jueves 14:00–18:00; sábado 08:00–12:00) |
| Paciente | Juan Pérez Quispe | `jperez` · CI `1234567` | jperez@correo.com | Portal del paciente |
| Paciente | María López Rivero | `mlopez` · CI `7654321` | mlopez@correo.com | Portal del paciente |

**Cuenta para probar la recuperación de contraseña** (tiene un correo real):

| Rol | Persona | Usuario | Correo | Contraseña |
|---|---|---|---|---|
| Recepcionista | Sergio Estaca Paco | `sestaca` | sergioestaca673@gmail.com | `Recuperar2026` |

Para probarla: en el login elige **"¿Olvidaste tu contraseña?"**, escribe `sergioestaca673@gmail.com`, revisa el correo y usa el código de 6 dígitos para poner una contraseña nueva. Si la cambias, esta tabla deja de estar al día.

> **Para probar la atención médica:** un médico solo puede atender las citas del día. Entre con el médico que atiende el día de hoy, según el horario de la tabla.
> Un paciente nuevo también puede crear su propia cuenta en **/registro**.
> Si alguien olvida su contraseña, en el login está **"¿Olvidaste tu contraseña?"**: el código llega al correo de la cuenta. Las cuentas de demostración tienen correos ficticios, así que para probarlo hay que poner un correo real en una cuenta desde **Usuarios**.
> Estas cuentas son de demostración. En un uso real, cambie las contraseñas desde **Mi cuenta**.

## Pruebas

Pruebas mínimas del enunciado, ejecutadas contra el sistema:

| N.º | Prueba | Resultado esperado | Resultado obtenido |
|---|---|---|---|
| 1 | Registrar un paciente nuevo | Queda guardado | ✅ «Paciente registrado correctamente» y se generó `PAC-0009` |
| 2 | Paciente con CI duplicado | Se rechaza | ✅ «Ya existe un paciente registrado con ese CI» |
| 3 | Cita en horario disponible | Queda registrada | ✅ «Cita CM-0245 programada…» |
| 4 | Cita superpuesta, mismo médico | Se impide | ✅ «El horario de las 08:00 ya no está disponible: el médico tiene otra cita» |
| 5 | Cita fuera del horario | Se rechaza | ✅ «La hora elegida está fuera del horario de atención del médico» |
| 6 | Cancelar una cita | Queda cancelada y conserva el historial | ✅ Estado *Cancelada*, el horario queda libre y el historial se conserva |
| 7 | Historial de un paciente | Aparecen sus citas | ✅ Lista con la cita cancelada incluida |
| 8 | Citas de un médico por fecha | Resultados correctos | ✅ Agenda filtrada por médico, fecha y estado |
| 9 | Página interna sin sesión | Acceso denegado | ✅ Redirige a `/login` (302) |
| 10 | Datos inválidos | El servidor rechaza | ✅ CI con letras, fecha futura y correo inválido se rechazan aunque se salte el JavaScript |

**Pruebas adicionales**
- **En la base de datos:** insertando por SQL directo se rechazan el cruce de citas, las fechas pasadas, las horas fuera del horario, la duración incorrecta, las transiciones de estado inválidas, el borrado de pacientes con citas, el CI duplicado, las especialidades repetidas y los horarios cruzados.
- **Permisos por rol:** la recepción, el médico y el paciente reciben **403** en las páginas que no les corresponden.
- **Portal:** un paciente no puede cancelar citas de otro (404).
- **Recuperación de contraseña** (probada con un servidor SMTP de prueba):
  - Un correo no registrado recibe la misma respuesta que uno registrado.
  - El código llega por correo.
  - Un código incorrecto avisa cuántos intentos quedan.
  - El código correcto restablece la contraseña y permite ingresar.
  - Un código ya usado se rechaza.

## Estructura del proyecto

```
nova-salud/
├── Bakend/                    # Lo que el usuario NO ve
│   ├── app/
│   │   ├── bootstrap.php      # Arranque: entorno, errores, carga de clases, sesión
│   │   ├── core/              # Router, Auth, Csrf, Session, Validator, View, Database, Mailer…
│   │   ├── controllers/       # Un controlador por caso de uso o módulo (incluye la página pública)
│   │   ├── models/            # Acceso a datos (SQL parametrizado)
│   │   └── services/          # CitaService (reglas de citas) y Disponibilidad
│   └── database/              # Scripts SQL 01 a 06 de PostgreSQL
├── Frontend/                  # Lo que el usuario SÍ ve
│   ├── views/                 # Pantallas: Publico, P1_SeguridadAcceso … P6_Reportes, Portal, layouts, partials
│   └── public/                # Lo que el navegador descarga tal cual: CSS, JS e ícono
├── Docs/                      # informe/ (Word, PDF y diagramas de la base), diagramas/ (por etapa y ciclo), capturas/, pruebas/, QR y guion
├── api/index.php              # Entrada de todas las peticiones y tabla de rutas (Vercel exige api/ en la raíz)
├── router.php                 # Servidor local `php -S` (solo sirve Frontend/public)
├── .htaccess                  # Apache (XAMPP/Laragon): bloquea Bakend, Docs y Frontend/views
├── vercel.json                # Despliegue en Vercel
└── .env.example               # Plantilla de configuración
```

- **Bakend** decide *qué* pasa: permisos, reglas de negocio y base de datos. **Frontend** decide *cómo se ve*.
- `Frontend/public` es la única carpeta que el navegador puede abrir directamente; el resto queda protegido.
- Los casos de uso no son una carpeta: cada uno se reparte entre su controlador (Bakend) y sus pantallas (Frontend), y se documenta en Docs.

---

<div align="center">
<sub>Clínica Vida Salud es un caso ficticio con fines académicos.</sub>
</div>
