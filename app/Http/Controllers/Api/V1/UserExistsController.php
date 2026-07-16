<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserExistsRequest;
use App\Services\TenantUserLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserExistsController extends Controller
{
    public function __construct(
        private TenantUserLookupService $lookup
    ) {}

    public function __invoke(UserExistsRequest $request): JsonResponse
    {
        try {
            $email = $request->validated('email');
            $tenant = $request->validated('tenant');

            $tenant = $this->lookup->find($email, $tenant);

            return response()->json([
                'data' => ['exists' => $tenant !== null, 'tenant' => $tenant?->value],
                'meta' => ['message' => $tenant !== null ? 'User found' : 'User not found'],
            ]);
        } catch (\Exception $e) {
            Log::error('User exists lookup failed', ['error' => $e->getMessage()]);

            return response()->json([
                'errors' => [
                    [
                        'status' => '500',
                        'title' => 'Failed to check user existence',
                    ],
                ],
            ], 500);
        }
    }
}
