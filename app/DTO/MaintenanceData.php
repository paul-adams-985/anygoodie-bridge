<?php

declare(strict_types=1);

namespace App\DTO;

readonly class MaintenanceData
{
    public function __construct(
        public string $message,
        public ?int $retryAfter = null,
    ) {}
}
