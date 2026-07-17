<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTO\AppVersionData;
use App\DTO\MaintenanceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\AppVersionRequest;
use App\Http\Responses\Api\V1\AppVersionResponse;
use App\Http\Responses\Api\V1\MaintenanceResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use PaulAdams985\Core\Types\MobilePlatform;
use PaulAdams985\Core\Types\Tenant;

class AppVersionController extends Controller
{
    /**
     * Check if the client app version is current or if the app is in maintenance mode.
     */
    public function __invoke(AppVersionRequest $request): JsonResponse
    {
        if (App::isDownForMaintenance()) {
            return $this->maintenanceResponse();
        }

        $clientVersion = $request->query('version', '0.0.0');
        $platform = $request->validated('platform');
        $tenant = Tenant::tryFrom((string) $request->validated('tenant'));

        return (new AppVersionResponse($this->buildVersionData($clientVersion, $platform, $tenant)))
            ->toJsonResponse();
    }

    private function maintenanceResponse(): JsonResponse
    {
        $maintenanceData = $this->getMaintenanceData();

        $data = new MaintenanceData(
            message: config('mobile.maintenance-message'),
            retryAfter: $maintenanceData['retry'] ?? null,
        );

        return (new MaintenanceResponse($data))
            ->toJsonResponse();
    }

    /**
     * @return array{except: array, redirect: ?string, retry: ?int, refresh: ?int, secret: ?string, status: int, template: ?string}
     */
    private function getMaintenanceData(): array
    {
        $path = storage_path('framework/down');

        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }

    private function buildVersionData(string $clientVersion, string $platform, ?Tenant $tenant): AppVersionData
    {
        $mobilePlatform = MobilePlatform::from($platform);

        $minimumVersion = $mobilePlatform->minimumVersion($tenant);
        $currentVersion = $mobilePlatform->currentVersion($tenant);

        return new AppVersionData(
            isCurrent: version_compare($clientVersion, $currentVersion, '>='),
            updateRequired: version_compare($clientVersion, $minimumVersion, '<'),
            latestVersion: $currentVersion,
            minimumVersion: $minimumVersion,
            storeUrl: config("mobile.{$platform}.store-url"),
            platform: $platform,
        );
    }
}
