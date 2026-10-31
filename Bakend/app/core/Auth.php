<?php
/**
 * RF01 · Inicio de sesión y control de acceso.
 * Valida credenciales con password_verify(), mantiene la sesión y
 * comprueba los permisos del rol en cada petición (RN12).
 */

declare(strict_types=1);

final class Auth
{
    private const MAX_INTENTOS = 5;      // intentos fallidos permitidos…
    private const VENTANA_MINUTOS = 10;  // …por IP en esta ventana

    private static ?array $usuario = null;
    private static bool $cargado = false;

    /**
     * Intenta iniciar sesión con usuario, correo o CI (pacientes).
     * @throws AppException con un mensaje genérico si las credenciales no son válidas
     */
    public static function intentar(string $login, string $password): array
    {
        $ip = ip_cliente();
        $fallidos = (int) Database::valor(
            "select count(*) from bitacora where accion = 'LOGIN_FALLIDO' and ip = :ip
             and fecha > now() - make_interval(mins => :min)",
            ['ip' => $ip, 'min' => self::VENTANA_MINUTOS]
        );
        if ($fallidos >= self::MAX_INTENTOS) {
            throw new AppException('Demasiados intentos fallidos. Espere unos minutos e intente de nuevo.');
        }

        $u = Database::fila(
            'select u.idusuario, u.nombre, u.password, u.estado
             from usuario u left join paciente p on p.idusuario = u.idusuario
             where lower(u.username) = lower(:l) or lower(u.email) = lower(:l) or p.ci = :l
             limit 1',
            ['l' => $login]
        );

        if (!$u || !password_verify($password, $u['password'])) {
            Bitacora::registrar('LOGIN_FALLIDO', 'SEGURIDAD', 'usuario', $u['idusuario'] ?? null, 'Intento fallido para "' . mb_substr($login, 0, 60) . '"', null);
            throw new AppException('Usuario o contraseña incorrectos.');
        }
        if ($u['estado'] !== 'ACTIVO') {
            throw new AppException('Su cuenta está desactivada. Contacte al administrador.');
        }

        // Si el algoritmo de hash cambió, se actualiza el hash guardado
        if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
            Database::query('update usuario set password = :p where idusuario = :id',
                ['p' => password_hash($password, PASSWORD_DEFAULT), 'id' => $u['idusuario']]);
        }

        session_regenerate_id(true);   // evita la fijación de sesión
        $_SESSION['idusuario'] = (int) $u['idusuario'];
        Database::query('update usuario set ultimoacceso = now() where idusuario = :id', ['id' => $u['idusuario']]);
        self::$cargado = false;
        Bitacora::registrar('INICIAR_SESION', 'SEGURIDAD', 'usuario', (int) $u['idusuario'], 'Inicio de sesión');
        return self::usuario();
    }

    /** Usuario de la sesión con su rol, permisos y vínculos (médico / paciente). */
    public static function usuario(): ?array
    {
        if (self::$cargado) {
            return self::$usuario;
        }
        self::$cargado = true;
        $id = $_SESSION['idusuario'] ?? null;
        if (!$id) {
            return self::$usuario = null;
        }

        $u = Database::fila(
            "select u.idusuario, u.nombre, u.username, u.email, u.estado, u.ultimoacceso, u.idrol,
                    r.nombre as rol, m.idmedico, p.idpaciente,
                    coalesce((select string_agg(pe.codigo, ',') from rolpermiso rp
                              join permiso pe on pe.idpermiso = rp.idpermiso where rp.idrol = u.idrol), '') as permisos
             from usuario u
             join rol r on r.idrol = u.idrol
             left join medico m on m.idusuario = u.idusuario
             left join paciente p on p.idusuario = u.idusuario
             where u.idusuario = :id",
            ['id' => $id]
        );

        // Una cuenta desactivada pierde la sesión en su siguiente petición
        if (!$u || $u['estado'] !== 'ACTIVO') {
            self::cerrarSesion();
            return self::$usuario = null;
        }
        $u['permisos'] = array_filter(explode(',', $u['permisos']));
        return self::$usuario = $u;
    }

    public static function id(): ?int
    {
        return self::usuario()['idusuario'] ?? null;
    }

    public static function puede(string ...$permisos): bool
    {
        $u = self::usuario();
        if (!$u) {
            return false;
        }
        return (bool) array_intersect($permisos, $u['permisos']);
    }

    /** Detiene la petición si no hay sesión (RF01) o falta el permiso (RN12). */
    public static function requerir(string ...$permisos): array
    {
        $u = self::usuario();
        if (!$u) {
            if (es_ajax()) {
                json_response(['ok' => false, 'error' => 'Sesión expirada'], 401);
            }
            // Recuerda la página pedida para volver a ella después de ingresar
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                $_SESSION['_destino'] = (string) ($_SERVER['REQUEST_URI'] ?? '/');
            }
            flash('error', str_starts_with((string) ($_SERVER['REQUEST_URI'] ?? ''), '/portal')
                ? 'Inicie sesión como paciente para continuar. ¿No tiene cuenta? Regístrese gratis.'
                : 'Inicie sesión para continuar.');
            redirect('/login');
        }
        if ($permisos && !self::puede(...$permisos)) {
            http_response_code(403);
            if (es_ajax()) {
                json_response(['ok' => false, 'error' => 'Acceso denegado'], 403);
            }
            View::render('errors/403');
            exit;
        }
        return $u;
    }

    /**
     * Página a la que se va después de ingresar: la que se pidió antes del login
     * (solo rutas internas y del portal si es paciente) o el inicio según el rol.
     */
    public static function destino(): string
    {
        $d = (string) ($_SESSION['_destino'] ?? '');
        unset($_SESSION['_destino']);
        $interna = $d !== '' && $d[0] === '/' && !str_starts_with($d, '//') && !str_contains($d, '\\');
        $esPortal = str_starts_with($d, '/portal');
        $paciente = self::inicio() === '/portal';
        return $interna && $esPortal === $paciente ? $d : self::inicio();
    }

    /** Página de inicio según el rol. */
    public static function inicio(): string
    {
        return self::puede('portal.paciente') && !self::puede('panel.ver') ? '/portal' : '/panel';
    }

    public static function cerrarSesion(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        self::$usuario = null;
    }
}
