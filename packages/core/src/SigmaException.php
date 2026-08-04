<?php

declare(strict_types=1);

namespace Sigma\Core;

/**
 * Exceção base de todo o SIGMA. Toda exceção de domínio lançada por um
 * Engine, Plugin ou Service estende esta classe — permite capturar
 * "qualquer falha do SIGMA" distintamente de uma exceção de biblioteca
 * de terceiros, e carrega um `code` estável para o campo `error.code`
 * do Envelope (ver SIGMA_PROTOCOL.md §1).
 */
class SigmaException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
