<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PaulAdams985\Core\Types\Tenant;

class TenantUserLookupService
{
    /**
     * Search for the email, either on a single explicitly requested tenant
     * (bypassing isolation — the caller already knows which tenant to ask),
     * or by fanning out across every joinable tenant, stopping at the first
     * one that has an account for the email.
     */
    public function find(string $email, ?string $tenant = null): ?Tenant
    {
        if ($tenant !== null) {
            $candidate = Tenant::from($tenant);

            return $this->existsOnTenant($candidate, $email) ? $candidate : null;
        }

        foreach (Tenant::joinable() as $candidate) {
            if ($this->existsOnTenant($candidate, $email)) {
                return $candidate;
            }
        }

        return null;
    }

    private function existsOnTenant(Tenant $candidate, string $email): bool
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['X-Tenant-Secret' => config('services.tenant_lookup.secret')])
                ->post("{$candidate->endpoint()}/api/v1/recipient/user-exists", [
                    'email' => $email,
                ]);

            return $response->successful() && $response->json('data.exists') === true;
        } catch (\Throwable $e) {
            Log::warning('Tenant user-exists lookup failed', [
                'tenant' => $candidate->value,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
