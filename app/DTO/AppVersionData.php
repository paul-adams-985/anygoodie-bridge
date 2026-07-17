<?php

declare(strict_types=1);

namespace App\DTO;

readonly class AppVersionData
{
    public function __construct(
        public bool $isCurrent,
        public bool $updateRequired,
        public string $latestVersion,
        public string $minimumVersion,
        public ?string $storeUrl,
        public ?string $platform = null,
    ) {}
}
