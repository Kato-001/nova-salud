<?php
/**
 * Envío de correos por SMTP (Gmail u otro servidor), sin librerías externas.
 *
 * Variables de entorno:
 *   SMTP_HOST (smtp.gmail.com) · SMTP_PORT (465 SSL o 587 STARTTLS)
 *   SMTP_USER (correo remitente) · SMTP_PASS (contraseña de aplicación de Gmail)
 *   SMTP_FROM_NAME (nombre visible del remitente)
 *   SMTP_SECURE (ssl · tls · none; por defecto ssl en 465 y tls en otros puertos)
 */

declare(strict_types=1);

final class Mailer
{
    public static function configurado(): bool
    {
        return (bool) (getenv('SMTP_USER') && getenv('SMTP_PASS'));
    }

    /** @throws RuntimeException si no se pudo enviar */
    public static function enviar(string $para, string $asunto, string $html, string $texto): void
    {
        if (!self::configurado()) {
            throw new RuntimeException('SMTP no configurado');
        }
        $host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $port = (int) (getenv('SMTP_PORT') ?: 465);
        $user = (string) getenv('SMTP_USER');
        $pass = str_replace(' ', '', (string) getenv('SMTP_PASS'));   // la clave de Gmail se copia con espacios
        $nombre = getenv('SMTP_FROM_NAME') ?: 'NovaSol Salud';

        $seguro = getenv('SMTP_SECURE') ?: ($port === 465 ? 'ssl' : 'tls');
        $destino = ($seguro === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $s = @stream_socket_client($destino, $errno, $error, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$s) {
            throw new RuntimeException("No se pudo conectar a $host:$port ($error)");
        }
        stream_set_timeout($s, 15);

        try {
            self::esperar($s, 220);
            self::comando($s, 'EHLO novasol-salud', 250);
            if ($seguro === 'tls') {
                self::comando($s, 'STARTTLS', 220);
                if (!stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new RuntimeException('No se pudo iniciar TLS');
                }
                self::comando($s, 'EHLO novasol-salud', 250);
            }
            self::comando($s, 'AUTH LOGIN', 334);
            self::comando($s, base64_encode($user), 334);
            self::comando($s, base64_encode($pass), 235);
            self::comando($s, "MAIL FROM:<$user>", 250);
            self::comando($s, 'RCPT TO:<' . self::limpiar($para) . '>', 250);
            self::comando($s, 'DATA', 354);

            $limite = 'ns_' . bin2hex(random_bytes(8));
            $cabeceras = [
                'From: ' . self::codificar($nombre) . " <$user>",
                'To: <' . self::limpiar($para) . '>',
                'Subject: ' . self::codificar($asunto),
                'Date: ' . date('r'),
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@novasol-salud>',
                'MIME-Version: 1.0',
                "Content-Type: multipart/alternative; boundary=\"$limite\"",
            ];
            $cuerpo = implode("\r\n", $cabeceras) . "\r\n\r\n"
                . "--$limite\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($texto)) . "\r\n"
                . "--$limite\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($html)) . "\r\n"
                . "--$limite--\r\n";
            // Un punto al inicio de línea terminaría el mensaje: se duplica (RFC 5321)
            fwrite($s, preg_replace('/^\./m', '..', $cuerpo) . "\r\n.\r\n");
            self::esperar($s, 250);
            self::comando($s, 'QUIT', 221);
        } finally {
            fclose($s);
        }
    }

    private static function comando($s, string $linea, int $esperado): void
    {
        fwrite($s, $linea . "\r\n");
        self::esperar($s, $esperado);
    }

    /** Lee la respuesta (puede tener varias líneas "250-…") y verifica el código. */
    private static function esperar($s, int $esperado): void
    {
        $respuesta = '';
        while (($linea = fgets($s, 515)) !== false) {
            $respuesta .= $linea;
            if (strlen($linea) < 4 || $linea[3] === ' ') {
                break;
            }
        }
        if ((int) substr($respuesta, 0, 3) !== $esperado) {
            throw new RuntimeException('SMTP respondió: ' . trim(substr($respuesta, 0, 200)));
        }
    }

    private static function codificar(string $t): string
    {
        return '=?UTF-8?B?' . base64_encode($t) . '?=';
    }

    /** Evita inyección de cabeceras en la dirección. */
    private static function limpiar(string $correo): string
    {
        return str_replace(["\r", "\n", '<', '>'], '', $correo);
    }
}
