<?php
/**
 * CU5 · Gestionar médicos (RF03).
 */

declare(strict_types=1);

final class MedicoController extends Controlador
{
    public function index(): void
    {
        Auth::requerir('medicos.ver', 'medicos.gestionar');
        $f = ['q' => entrada('q'), 'especialidad' => entrada('especialidad'), 'estado' => entrada('estado')];
        $pag = new Paginador(Medico::contar($f), 12);
        $this->vista('P2_GestionMedica/medicos/index', [
            'medicos'        => Medico::listar($f, $pag->porPagina, $pag->offset),
            'especialidades' => Especialidad::todas(),
            'f'              => $f,
            'pag'            => $pag,
        ]);
    }

    public function ver(): void
    {
        Auth::requerir('medicos.ver', 'medicos.gestionar');
        $m = Medico::buscar($this->id()) ?? $this->noEncontrado();
        $fecha = entrada('fecha') ?: hoy();
        $this->vista('P2_GestionMedica/medicos/ver', [
            'm'        => $m,
            'horarios' => Horario::delMedico((int) $m['idmedico']),
            'bloqueos' => Horario::bloqueos((int) $m['idmedico']),
            'fecha'    => $fecha,
            'agenda'   => Cita::agenda($fecha, (int) $m['idmedico']),
            'conteo'   => Cita::conteoPorEstado(['medico' => $m['idmedico']]),
        ]);
    }

    public function crear(): void
    {
        Auth::requerir('medicos.gestionar');
        $this->vista('P2_GestionMedica/medicos/form', [
            'm' => null, 'especialidades' => Especialidad::todas(true), 'codigo' => Medico::siguienteCodigo(),
        ]);
    }

    public function editar(): void
    {
        Auth::requerir('medicos.gestionar');
        $m = Medico::buscar($this->id()) ?? $this->noEncontrado();
        $this->vista('P2_GestionMedica/medicos/form', ['m' => $m, 'especialidades' => Especialidad::todas(), 'codigo' => $m['codigo']]);
    }

    public function guardar(): void
    {
        Auth::requerir('medicos.gestionar');
        $id = entrada_int('idmedico');
        $actual = $id ? (Medico::buscar($id) ?? $this->noEncontrado()) : null;
        $d = array_map(fn($x) => is_string($x) ? trim($x) : $x, $_POST);
        $esp = array_values(array_unique(array_map('intval', (array) ($_POST['especialidades'] ?? []))));
        $validas = array_map('intval', array_column(Especialidad::todas(), 'idespecialidad'));

        $v = new Validator($d);
        $v->requerido('codigo', 'El código')->regex('codigo', '/^[A-Za-z0-9-]{3,20}$/', 'Solo letras, números y guiones (3 a 20).')
          ->requerido('registroprofesional', 'El registro profesional')->regex('registroprofesional', '/^[A-Za-z0-9-]{3,50}$/', 'Solo letras, números y guiones.')
          ->requerido('nombres', 'Los nombres')->longitud('nombres', 2, 100)->regex('nombres', "/^[\\p{L} .'-]+$/u", 'Solo letras.')
          ->requerido('apellidos', 'Los apellidos')->longitud('apellidos', 2, 100)->regex('apellidos', "/^[\\p{L} .'-]+$/u", 'Solo letras.')
          ->telefono('telefono')
          ->requerido('email', 'El correo')->email('email')->max('email', 150)
          ->si(!$esp, 'especialidades', 'Seleccione al menos una especialidad.')
          ->si((bool) array_diff($esp, $validas), 'especialidades', 'Especialidad no válida.')
          ->si(Medico::existe('codigo', $d['codigo'] ?? '', $id), 'codigo', 'Ya existe un médico con ese código.')
          ->si(Medico::existe('registro', $d['registroprofesional'] ?? '', $id), 'registroprofesional', 'Ese registro profesional ya está registrado.')
          ->si(Usuario::existeEmail($d['email'] ?? '', $actual['idusuario'] ?? null), 'email', 'Ese correo ya está registrado en otra cuenta.');
        if (!$id) {
            // La cuenta de acceso del médico se crea junto con su ficha
            $v->requerido('username', 'El usuario')->regex('username', '/^[A-Za-z0-9_.-]{3,50}$/', 'Solo letras, números, guiones y puntos.')
              ->si(Usuario::existeUsername($d['username'] ?? ''), 'username', 'Ese nombre de usuario ya está en uso.')
              ->password('password')->coincide('password_confirmacion', 'password', 'Las contraseñas no coinciden.');
        }
        $volver = $id ? url('/medicos/editar', ['id' => $id]) : '/medicos/nuevo';
        if ($v->falla()) {
            con_errores($v->errores(), $d + ['especialidades' => $esp]);
            flash('error', $v->primerError());
            redirect($volver);
        }

        $nuevoId = $id;
        if (!$this->intentar(function () use ($id, $d, $esp, &$nuevoId) {
            if ($id) {
                Medico::actualizar($id, $d, $esp);
            } else {
                $nuevoId = Medico::crear($d, $esp);
            }
            Bitacora::registrar($id ? 'EDITAR' : 'CREAR', 'GESTION_MEDICA', 'medico', $nuevoId, "Médico {$d['codigo']} · {$d['nombres']} {$d['apellidos']}");
        }, $d + ['especialidades' => $esp])) {
            redirect($volver);
        }
        flash('ok', $id ? 'Datos del médico actualizados.' : 'Médico registrado. Ahora defina sus horarios de atención.');
        redirect($id ? url('/medicos/ver', ['id' => $id]) : url('/horarios', ['medico' => $nuevoId]));
    }

    /** RF03 / RN04: un médico inactivo no recibe nuevas citas; su historial se conserva. */
    public function estado(): void
    {
        Auth::requerir('medicos.gestionar');
        $m = Medico::buscar($this->id()) ?? $this->noEncontrado();
        $nuevo = $this->estadoContrario($m['estado']);
        Medico::cambiarEstado((int) $m['idmedico'], $nuevo);
        $pendientes = (int) Database::valor("select count(*) from cita where idmedico = :m and codigoestado in ('PROGRAMADA','CONFIRMADA') and fecha >= current_date", ['m' => $m['idmedico']]);
        Bitacora::registrar($nuevo === 'ACTIVO' ? 'ACTIVAR' : 'DESACTIVAR', 'GESTION_MEDICA', 'medico', (int) $m['idmedico'], "Médico {$m['codigo']}");
        flash($nuevo === 'INACTIVO' && $pendientes ? 'info' : 'ok', $nuevo === 'ACTIVO'
            ? "Médico {$m['nombre_completo']} activado."
            : "Médico {$m['nombre_completo']} desactivado." . ($pendientes ? " Tiene $pendientes cita(s) pendiente(s): reprográmelas o cancélelas." : ''));
        volver('/medicos');
    }
}
