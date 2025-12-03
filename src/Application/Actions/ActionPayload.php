<?php

declare(strict_types=1);

namespace App\Application\Actions;

use JsonSerializable;

class ActionPayload implements JsonSerializable
{
    private int $statusCode;

    /**
     * @var array|object|null
     */
    private $data;

    private ?ActionError $error;

    private string $message;

    private bool $success;

    public function __construct(
        int $statusCode = 200,
        $data = null,
        ?ActionError $error = null,
        ?string $message = null
    ) {
        $this->statusCode = $statusCode;
        $this->data = $data;
        $this->error = $error;
        // Default message based on presence of error
        $this->message = $message ?? ($error === null ? 'Data ditemukan' : 'Data tidak ditemukan');
        $this->success = $error === null;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array|null|object
     */
    public function getData()
    {
        return $this->data;
    }

    public function getError(): ?ActionError
    {
        return $this->error;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        $payload = [
            'message'    => $this->message,
            'success'    => $this->success,
            'statusCode' => $this->statusCode,
        ];

        if ($this->data !== null) {
            $payload['data'] = $this->data;
        }

        if ($this->error !== null) {
            $payload['data'] = null;
        }

        return $payload;
    }
}
