<?php
/**
 * Errores de validación de un formulario (campo => mensaje).
 */

declare(strict_types=1);

final class ValidacionException extends RuntimeException
{
    public function __construct(public readonly array $errores)
    {
        parent::__construct((string) (array_values($errores)[0] ?? 'Datos inválidos.'));
    }
}
