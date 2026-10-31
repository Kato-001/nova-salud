<?php
/**
 * Acceso a PostgreSQL mediante PDO (controlador pdo_pgsql).
 * Una sola conexión por petición; todas las consultas son parametrizadas.
 */

declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $url = getenv('DATABASE_URL');
        if (!$url) {
            throw new RuntimeException('Falta la variable de entorno DATABASE_URL');
        }
        $p = parse_url($url);
        parse_str($p['query'] ?? '', $opciones);
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
            $p['host'],
            $p['port'] ?? 5432,
            ltrim($p['path'] ?? '/postgres', '/'),
            $opciones['sslmode'] ?? 'prefer'
        );

        self::$pdo = new PDO($dsn, urldecode($p['user'] ?? ''), urldecode($p['pass'] ?? ''), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // El pooler de Supabase (modo transacción) no admite sentencias preparadas
            // del lado del servidor: PDO las emula escapando los parámetros de forma segura.
            PDO::ATTR_EMULATE_PREPARES   => true,
            PDO::ATTR_TIMEOUT            => 10,
        ]);
        return self::$pdo;
    }

    /** Ejecuta una consulta parametrizada y devuelve la sentencia. */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        foreach ($params as $clave => $valor) {
            $tipo = match (true) {
                is_int($valor)  => PDO::PARAM_INT,
                is_bool($valor) => PDO::PARAM_BOOL,
                $valor === null => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $st->bindValue(is_int($clave) ? $clave + 1 : ':' . ltrim($clave, ':'), $valor, $tipo);
        }
        $st->execute();
        return $st;
    }

    public static function fila(string $sql, array $params = []): ?array
    {
        return self::query($sql, $params)->fetch() ?: null;
    }

    public static function filas(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function valor(string $sql, array $params = []): mixed
    {
        $v = self::query($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** Ejecuta $fn dentro de una transacción; revierte si algo falla. */
    public static function transaccion(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $r = $fn($pdo);
            $pdo->commit();
            return $r;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
