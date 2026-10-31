<?php
/**
 * Validación de datos en el servidor.
 * Repite las mismas reglas que valida JavaScript en el navegador, porque
 * los datos del cliente nunca son confiables (requerimiento técnico).
 *
 * Uso:
 *   $v = new Validator($_POST);
 *   $v->requerido('ci', 'CI')->regex('ci', '/^\d{5,12}$/', 'El CI solo admite números');
 *   if ($v->falla()) { ... $v->errores() ... }
 */

declare(strict_types=1);

final class Validator
{
    private array $errores = [];

    public function __construct(private array $datos)
    {
    }

    private function valor(string $campo): string
    {
        $v = $this->datos[$campo] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    private function error(string $campo, string $mensaje): self
    {
        $this->errores[$campo] ??= $mensaje;   // se guarda solo el primer error del campo
        return $this;
    }

    public function requerido(string $campo, string $etiqueta): self
    {
        return $this->valor($campo) === '' ? $this->error($campo, "$etiqueta es obligatorio.") : $this;
    }

    public function longitud(string $campo, int $min, int $max): self
    {
        $n = mb_strlen($this->valor($campo));
        if ($n > 0 && ($n < $min || $n > $max)) {
            $this->error($campo, $min === $max ? "Debe tener $min caracteres." : "Debe tener entre $min y $max caracteres.");
        }
        return $this;
    }

    public function max(string $campo, int $max): self
    {
        return mb_strlen($this->valor($campo)) > $max ? $this->error($campo, "Máximo $max caracteres.") : $this;
    }

    public function regex(string $campo, string $patron, string $mensaje): self
    {
        $v = $this->valor($campo);
        return $v !== '' && !preg_match($patron, $v) ? $this->error($campo, $mensaje) : $this;
    }

    public function email(string $campo): self
    {
        $v = $this->valor($campo);
        return $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL) ? $this->error($campo, 'Correo electrónico no válido.') : $this;
    }

    public function telefono(string $campo): self
    {
        return $this->regex($campo, '/^[0-9+\-\s]{7,20}$/', 'Teléfono no válido (7 a 20 dígitos).');
    }

    public function fecha(string $campo, ?string $min = null, ?string $max = null): self
    {
        $v = $this->valor($campo);
        if ($v === '') {
            return $this;
        }
        $d = DateTime::createFromFormat('!Y-m-d', $v);
        if (!$d || $d->format('Y-m-d') !== $v) {
            return $this->error($campo, 'Fecha no válida.');
        }
        if ($min !== null && $v < $min) {
            return $this->error($campo, 'La fecha no puede ser anterior a ' . fecha($min) . '.');
        }
        if ($max !== null && $v > $max) {
            return $this->error($campo, 'La fecha no puede ser posterior a ' . fecha($max) . '.');
        }
        return $this;
    }

    public function hora(string $campo): self
    {
        return $this->regex($campo, '/^([01]\d|2[0-3]):[0-5]\d(:00)?$/', 'Hora no válida.');
    }

    public function en(string $campo, array $permitidos, string $mensaje = 'Valor no permitido.'): self
    {
        $v = $this->valor($campo);
        return $v !== '' && !in_array($v, $permitidos, true) ? $this->error($campo, $mensaje) : $this;
    }

    public function entero(string $campo, ?int $min = null, ?int $max = null): self
    {
        $v = $this->valor($campo);
        if ($v === '') {
            return $this;
        }
        if (filter_var($v, FILTER_VALIDATE_INT) === false) {
            return $this->error($campo, 'Debe ser un número entero.');
        }
        if (($min !== null && (int) $v < $min) || ($max !== null && (int) $v > $max)) {
            return $this->error($campo, "Debe estar entre $min y $max.");
        }
        return $this;
    }

    public function decimal(string $campo, float $min, float $max): self
    {
        $v = str_replace(',', '.', $this->valor($campo));
        if ($v === '') {
            return $this;
        }
        if (!is_numeric($v) || (float) $v < $min || (float) $v > $max) {
            return $this->error($campo, "Debe ser un número entre $min y $max.");
        }
        return $this;
    }

    /** Mínimo 8 caracteres con letras y números. */
    public function password(string $campo, bool $obligatorio = true): self
    {
        $v = $this->datos[$campo] ?? '';
        if ($v === '' && !$obligatorio) {
            return $this;
        }
        if (!is_string($v) || !preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,100}$/', $v)) {
            return $this->error($campo, 'Mínimo 8 caracteres con letras y números.');
        }
        return $this;
    }

    public function coincide(string $campo, string $otro, string $mensaje): self
    {
        return ($this->datos[$campo] ?? '') !== ($this->datos[$otro] ?? '') ? $this->error($campo, $mensaje) : $this;
    }

    /** Regla personalizada: si $condicion es verdadera, se registra el error. */
    public function si(bool $condicion, string $campo, string $mensaje): self
    {
        return $condicion ? $this->error($campo, $mensaje) : $this;
    }

    public function falla(): bool
    {
        return $this->errores !== [];
    }

    public function errores(): array
    {
        return $this->errores;
    }

    public function primerError(): string
    {
        return (string) (array_values($this->errores)[0] ?? '');
    }
}
