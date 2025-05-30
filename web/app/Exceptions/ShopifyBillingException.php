<?php

namespace App\Exceptions;

use Exception;

class ShopifyBillingException extends Exception
{
    private array $errors;

    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        $firstError = reset($this->errors);

        // Handle different error formats from Shopify
        if (is_array($firstError)) {
            return $firstError['message'] ?? $firstError['field'] ?? 'Unknown error';
        }

        return (string) $firstError;
    }
}
