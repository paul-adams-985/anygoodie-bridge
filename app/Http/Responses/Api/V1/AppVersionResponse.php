<?php

declare(strict_types=1);

namespace App\Http\Responses\Api\V1;

use App\DTO\AppVersionData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiRequest;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Support\Collection;

class AppVersionResponse extends JsonApiResource
{
    public function __construct(
        private AppVersionData $versionData,
    ) {
        parent::__construct($versionData);
    }

    public function toId(Request $request): string
    {
        return $this->versionData->platform ?? 'unknown';
    }

    public function toType(Request $request): string
    {
        return 'app-version';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'platform' => $this->versionData->platform,
            'is_current' => $this->versionData->isCurrent,
            'update_required' => $this->versionData->updateRequired,
            'latest_version' => $this->versionData->latestVersion,
            'minimum_version' => $this->versionData->minimumVersion,
            'store_url' => $this->versionData->storeUrl,
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
        ])->response()->setStatusCode(200);
    }
}
