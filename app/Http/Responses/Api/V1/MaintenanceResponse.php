<?php

declare(strict_types=1);

namespace App\Http\Responses\Api\V1;

use App\DTO\MaintenanceData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiRequest;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Support\Collection;

class MaintenanceResponse extends JsonApiResource
{
    public function __construct(
        private MaintenanceData $maintenanceData,
    ) {
        parent::__construct($maintenanceData);
    }

    public function toId(Request $request): string
    {
        return 'current';
    }

    public function toType(Request $request): string
    {
        return 'maintenance';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'maintenance' => true,
            'message' => $this->maintenanceData->message,
            'retry_after' => $this->maintenanceData->retryAfter,
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [];
    }

    public function resolveIncludedResourceObjects(JsonApiRequest $request): Collection
    {
        return collect();
    }

    public function toJsonResponse(): JsonResponse
    {
        return $this->additional([
            'meta' => [
                'message' => 'Success',
            ],
        ])->response()->setStatusCode(503);
    }
}
