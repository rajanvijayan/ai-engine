<?php

declare(strict_types=1);

namespace AIEngine;

class Response
{
    protected string $text;
    protected string $provider;
    protected string $model;
    protected ?array $rawResponse;

    public function __construct(string $text, string $provider, string $model, ?array $rawResponse = null)
    {
        $this->text = $text;
        $this->provider = $provider;
        $this->model = $model;
        $this->rawResponse = $rawResponse;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getRawResponse(): ?array
    {
        return $this->rawResponse;
    }

    public function __toString(): string
    {
        return $this->text;
    }
}
