<?php

declare(strict_types=1);

namespace AIEngine\Exceptions;

class ApiException extends AIEngineException
{
    protected string $provider;
    protected ?string $apiErrorMessage;

    public function __construct(string $message, string $provider, ?string $apiErrorMessage = null, int $code = 0, ?\Throwable $previous = null)
    {
        $this->provider = $provider;
        $this->apiErrorMessage = $apiErrorMessage;
        parent::__construct($message, $code, $previous);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getApiErrorMessage(): ?string
    {
        return $this->apiErrorMessage;
    }
}
