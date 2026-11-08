<?php
/**
 * Nova Salud · Controlador frontal
 * ---------------------------------------------------------------------
 * Todas las peticiones llegan aquí (Vercel: vercel.json · Apache: .htaccess ·
 * servidor de PHP: router.php) y se envían al controlador correspondiente.
 */

declare(strict_types=1);

require __DIR__ . '/../Bakend/app/bootstrap.php';

$r = new Router();

// --- P1 · Seguridad y acceso ------------------------------------------
// --- Página pública: especialidades, médicos y cómo reservar ------------
$r->get('/', [PublicoController::class, 'inicio']);

$r->get('/login', [AuthController::class, 'formulario'])
  ->post('/login', [AuthController::class, 'ingresar'])
  ->post('/salir', [AuthController::class, 'salir'])
  ->get('/recuperar', [RecuperacionController::class, 'formulario'])
  ->post('/recuperar', [RecuperacionController::class, 'enviar'])
  ->get('/recuperar/codigo', [RecuperacionController::class, 'codigo'])
  ->post('/recuperar/codigo', [RecuperacionController::class, 'restablecer'])
  ->get('/panel', [PanelController::class, 'index'])
  ->get('/usuarios', [UsuarioController::class, 'index'])
  ->get('/usuarios/nuevo', [UsuarioController::class, 'crear'])
  ->get('/usuarios/editar', [UsuarioController::class, 'editar'])
  ->post('/usuarios/guardar', [UsuarioController::class, 'guardar'])
  ->post('/usuarios/estado', [UsuarioController::class, 'estado'])
  ->get('/roles', [RolController::class, 'index'])
  ->post('/roles/guardar', [RolController::class, 'guardar'])
  ->post('/roles/permisos', [RolController::class, 'permisos'])
  ->post('/roles/eliminar', [RolController::class, 'eliminar'])
  ->get('/configuracion', [ConfiguracionController::class, 'index'])
  ->post('/configuracion', [ConfiguracionController::class, 'guardar'])
  ->get('/bitacora', [BitacoraController::class, 'index'])
  ->get('/cuenta', [CuentaController::class, 'index'])
  ->post('/cuenta/password', [CuentaController::class, 'password']);

// --- P2 · Gestión médica ----------------------------------------------
$r->get('/especialidades', [EspecialidadController::class, 'index'])
  ->post('/especialidades/guardar', [EspecialidadController::class, 'guardar'])
  ->post('/especialidades/estado', [EspecialidadController::class, 'estado'])
  ->get('/medicos', [MedicoController::class, 'index'])
  ->get('/medicos/ver', [MedicoController::class, 'ver'])
  ->get('/medicos/nuevo', [MedicoController::class, 'crear'])
  ->get('/medicos/editar', [MedicoController::class, 'editar'])
  ->post('/medicos/guardar', [MedicoController::class, 'guardar'])
  ->post('/medicos/estado', [MedicoController::class, 'estado'])
  ->get('/horarios', [HorarioController::class, 'index'])
  ->post('/horarios/guardar', [HorarioController::class, 'guardar'])
  ->post('/horarios/estado', [HorarioController::class, 'estado'])
  ->post('/horarios/eliminar', [HorarioController::class, 'eliminar'])
  ->post('/bloqueos/guardar', [HorarioController::class, 'bloquear'])
  ->post('/bloqueos/eliminar', [HorarioController::class, 'desbloquear']);

// --- P3 · Gestión de pacientes ----------------------------------------
$r->get('/registro', [AuthController::class, 'registro'])
  ->post('/registro', [AuthController::class, 'registrar'])
  ->get('/pacientes', [PacienteController::class, 'index'])
  ->get('/pacientes/ver', [PacienteController::class, 'ver'])
  ->get('/pacientes/nuevo', [PacienteController::class, 'crear'])
  ->get('/pacientes/editar', [PacienteController::class, 'editar'])
  ->post('/pacientes/guardar', [PacienteController::class, 'guardar'])
  ->post('/pacientes/estado', [PacienteController::class, 'estado'])
  ->post('/pacientes/historia', [PacienteController::class, 'historia'])
  ->post('/pacientes/contacto', [PacienteController::class, 'contacto'])
  ->post('/pacientes/contacto/eliminar', [PacienteController::class, 'quitarContacto']);

// --- P4 · Gestión de citas --------------------------------------------
$r->get('/citas', [CitaController::class, 'index'])
  ->get('/citas/nueva', [CitaController::class, 'nueva'])
  ->post('/citas/guardar', [CitaController::class, 'guardar'])
  ->get('/citas/ver', [CitaController::class, 'ver'])
  ->get('/citas/reprogramar', [CitaController::class, 'reprogramar'])
  ->post('/citas/reprogramar', [CitaController::class, 'guardarReprogramacion'])
  ->post('/citas/cancelar', [CitaController::class, 'cancelar'])
  ->post('/citas/estado', [CitaController::class, 'estado'])
  ->get('/agenda', [AgendaController::class, 'index'])
  ->get('/api/medicos', [ApiController::class, 'medicos'])
  ->get('/api/disponibilidad', [ApiController::class, 'disponibilidad'])
  ->get('/api/pacientes', [ApiController::class, 'pacientes']);

// --- P5 · Atención médica ---------------------------------------------
$r->get('/atencion', [AtencionController::class, 'formulario'])
  ->post('/atencion/guardar', [AtencionController::class, 'guardar'])
  ->get('/estudios', [EstudioController::class, 'index'])
  ->get('/estudios/resultado', [EstudioController::class, 'resultado'])
  ->post('/estudios/resultado', [EstudioController::class, 'guardarResultado'])
  ->get('/estudios/catalogo', [EstudioController::class, 'catalogo'])
  ->post('/estudios/catalogo', [EstudioController::class, 'guardarCatalogo'])
  ->post('/estudios/catalogo/estado', [EstudioController::class, 'estadoCatalogo']);

// --- P6 · Reportes ----------------------------------------------------
$r->get('/reportes', [ReporteController::class, 'index']);

// --- Portal del paciente ----------------------------------------------
$r->get('/portal', [PortalController::class, 'inicio'])
  ->get('/portal/reservar', [PortalController::class, 'reservar'])
  ->post('/portal/reservar', [PortalController::class, 'guardarReserva'])
  ->get('/portal/citas', [PortalController::class, 'citas'])
  ->post('/portal/citas/cancelar', [PortalController::class, 'cancelar'])
  ->get('/portal/perfil', [PortalController::class, 'perfil'])
  ->post('/portal/perfil', [PortalController::class, 'guardarPerfil'])
  ->get('/portal/historial', [PortalController::class, 'historial'])
  ->post('/portal/notificaciones/leer', [PortalController::class, 'leerNotificaciones']);

// Cabeceras de seguridad básicas
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$r->despachar($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
