<?php
/**
 * CU7 · Gestionar pacientes (RF02) e historial de citas (RF12).
 */

declare(strict_types=1);

final class PacienteController extends Controlador
{
    /** Reglas comunes de los datos del paciente (también las usa el autoregistro). */
    public static function validador(array $d, ?int $id): Validator
    {
        $v = new Validator($d);
        $v->requerido('ci', 'El CI')->regex('ci', '/^[0-9]{5,12}([-][0-9A-Z]{1,3})?$/', 'CI no válido: 5 a 12 dígitos (complemento opcional, ej. 1234567-1B).')
          ->requerido('nombres', 'Los nombres')->longitud('nombres', 2, 100)->regex('nombres', "/^[\\p{L} .'-]+$/u", 'Solo letras.')
          ->requerido('apellidos', 'Los apellidos')->longitud('apellidos', 2, 100)->regex('apellidos', "/^[\\p{L} .'-]+$/u", 'Solo letras.')
          ->requerido('fechanacimiento', 'La fecha de nacimiento')->fecha('fechanacimiento', '1900-01-01', hoy())
          ->requerido('sexo', 'El sexo')->en('sexo', array_keys(Paciente::SEXOS))
          ->requerido('telefono', 'El teléfono')->telefono('telefono')
          ->email('email')->max('email', 150)->max('direccion', 255)
          ->en('tiposangre', Paciente::TIPOS_SANGRE, 'Tipo de sangre no válido.')
          ->si(trim((string) ($d['ci'] ?? '')) !== '' && Paciente::existeCi(trim((string) $d['ci']), $id), 'ci', 'Ya existe un paciente registrado con ese CI.');   // RF02
        return $v;
    }

    public function index(): void
    {
        Auth::requerir('pacientes.ver', 'pacientes.gestionar');
        $f = ['q' => entrada('q'), 'estado' => entrada('estado')];
        $pag = new Paginador(Paciente::contar($f), 12);
        $this->vista('P3_GestionPacientes/pacientes/index', [
            'pacientes' => Paciente::listar($f, $pag->porPagina, $pag->offset),
            'f'         => $f,
            'pag'       => $pag,
        ]);
    }

    public function ver(): void
    {
        Auth::requerir('pacientes.ver', 'pacientes.gestionar');
        $p = Paciente::buscar($this->id()) ?? $this->noEncontrado();
        $citas = Cita::delPaciente((int) $p['idpaciente']);
        $this->vista('P3_GestionPacientes/pacientes/ver', [
            'p'         => $p,
            'citas'     => $citas,
            'conteo'    => array_count_values(array_column($citas, 'codigoestado')),
            'contactos' => Paciente::contactos((int) $p['idpaciente']),
            'consultas' => Paciente::consultas((int) $p['idpaciente']),
        ]);
    }

    public function crear(): void
    {
        Auth::requerir('pacientes.gestionar');
        $this->vista('P3_GestionPacientes/pacientes/form', ['p' => null]);
    }

    public function editar(): void
    {
        Auth::requerir('pacientes.gestionar');
        $p = Paciente::buscar($this->id()) ?? $this->noEncontrado();
        $this->vista('P3_GestionPacientes/pacientes/form', ['p' => $p]);
    }

    public function guardar(): void
    {
        Auth::requerir('pacientes.gestionar');
        $id = entrada_int('idpaciente');
        if ($id) {
            Paciente::buscar($id) ?? $this->noEncontrado();
        }
        $d = array_map(fn($x) => is_string($x) ? trim($x) : $x, $_POST);
        $d['ci'] = mb_strtoupper($d['ci'] ?? '');
        $conCuenta = !$id && !empty($d['crear_cuenta']);

        $v = self::validador($d, $id);
        if ($conCuenta) {
            $v->requerido('email', 'El correo (para la cuenta)')
              ->requerido('username', 'El usuario')->regex('username', '/^[A-Za-z0-9_.-]{3,50}$/', 'Solo letras, números, guiones y puntos.')
              ->si(Usuario::existeUsername($d['username'] ?? ''), 'username', 'Ese nombre de usuario ya está en uso.')
              ->si(Usuario::existeEmail($d['email'] ?? ''), 'email', 'Ese correo ya tiene una cuenta.')
              ->password('password')->coincide('password_confirmacion', 'password', 'Las contraseñas no coinciden.');
        }
        $volver = $id ? url('/pacientes/editar', ['id' => $id]) : '/pacientes/nuevo';
        if ($v->falla()) {
            con_errores($v->errores(), $d);
            flash('error', $v->primerError());
            redirect($volver);
        }

        $nuevoId = $id;
        if (!$this->intentar(function () use ($id, $d, $conCuenta, &$nuevoId) {
            if ($id) {
                Paciente::actualizar($id, $d);
            } else {
                $nuevoId = Paciente::crear($d, $conCuenta);
            }
            Bitacora::registrar($id ? 'EDITAR' : 'CREAR', 'PACIENTES', 'paciente', $nuevoId, "Paciente CI {$d['ci']} · {$d['nombres']} {$d['apellidos']}");
        }, $d)) {
            redirect($volver);
        }
        flash('ok', $id ? 'Datos del paciente actualizados.' : 'Paciente registrado correctamente.');
        redirect(url('/pacientes/ver', ['id' => $nuevoId]));
    }

    /** RN11: se desactiva sin eliminar su historial. */
    public function estado(): void
    {
        Auth::requerir('pacientes.gestionar');
        $p = Paciente::buscar($this->id()) ?? $this->noEncontrado();
        $nuevo = $this->estadoContrario($p['estado']);
        $pendientes = $nuevo === 'INACTIVO' ? Paciente::citasPendientes((int) $p['idpaciente']) : 0;
        Paciente::cambiarEstado((int) $p['idpaciente'], $nuevo);
        Bitacora::registrar($nuevo === 'ACTIVO' ? 'ACTIVAR' : 'DESACTIVAR', 'PACIENTES', 'paciente', (int) $p['idpaciente'], "Paciente CI {$p['ci']}");
        flash($pendientes ? 'info' : 'ok', "Paciente {$p['nombre_completo']} " . ($nuevo === 'ACTIVO' ? 'activado.' : 'desactivado. Su historial se conserva.')
            . ($pendientes ? " Tiene $pendientes cita(s) pendiente(s)." : ''));
        volver('/pacientes');
    }

    public function historia(): void
    {
        Auth::requerir('pacientes.gestionar', 'consultas.registrar');
        $id = $this->id('idpaciente');
        Paciente::buscar($id) ?? $this->noEncontrado();
        $d = array_map(fn($x) => is_string($x) ? trim($x) : '', $_POST);
        $v = new Validator($d);
        foreach (['antecedentespersonales', 'antecedentesfamiliares', 'alergias', 'enfermedadescronicas', 'medicacionhabitual'] as $c) {
            $v->max($c, 2000);
        }
        if ($v->falla()) {
            flash('error', $v->primerError());
            volver('/pacientes');
        }
        Paciente::actualizarHistoria($id, $d);
        Bitacora::registrar('EDITAR', 'PACIENTES', 'historiaclinica', $id, 'Antecedentes de la historia clínica actualizados');
        flash('ok', 'Historia clínica actualizada.');
        volver('/pacientes');
    }

    public function contacto(): void
    {
        Auth::requerir('pacientes.gestionar');
        $id = $this->id('idpaciente');
        Paciente::buscar($id) ?? $this->noEncontrado();
        $d = ['nombre' => entrada('nombre'), 'parentesco' => entrada('parentesco'), 'telefono' => entrada('telefono')];
        $v = self::validadorContacto($d);
        if ($v->falla()) {
            flash('error', $v->primerError());
        } else {
            Paciente::agregarContacto($id, $d);
            flash('ok', 'Contacto de emergencia agregado.');
        }
        redirect(url('/pacientes/ver', ['id' => $id]) . '#contactos');
    }

    public function quitarContacto(): void
    {
        Auth::requerir('pacientes.gestionar');
        $id = $this->id('idpaciente');
        Paciente::eliminarContacto($id, $this->id('idcontacto'));
        flash('ok', 'Contacto eliminado.');
        redirect(url('/pacientes/ver', ['id' => $id]) . '#contactos');
    }

    public static function validadorContacto(array $d): Validator
    {
        return (new Validator($d))
            ->requerido('nombre', 'El nombre del contacto')->longitud('nombre', 3, 150)
            ->requerido('parentesco', 'El parentesco')->longitud('parentesco', 3, 50)
            ->requerido('telefono', 'El teléfono del contacto')->telefono('telefono');
    }
}
