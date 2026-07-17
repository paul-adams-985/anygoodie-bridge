<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    config([
        'services.tenant_lookup.secret' => 'test-secret',
        'tenants.default.mobile.ios.minimum_version' => '1.0.0',
        'tenants.default.mobile.ios.current_version' => '1.2.0',
        'mobile.ios.store-url' => 'https://apps.apple.com/app/example',
        'tenants.default.mobile.android.minimum_version' => '1.0.0',
        'tenants.default.mobile.android.current_version' => '1.3.0',
        'mobile.android.store-url' => 'https://play.google.com/store/apps/example',
        'mobile.maintenance-message' => 'We are currently performing maintenance.',
    ]);

    $this->withHeaders(['X-Tenant-Secret' => 'test-secret']);
});

afterEach(function (): void {
    $path = storage_path('framework/down');
    if (File::exists($path)) {
        File::delete($path);
    }
});

test('returns iOS version information when platform=ios', function (): void {
    $response = $this->getJson('/api/v1/app/version?version=1.0.0&platform=ios');

    $response->assertOK()
        ->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'platform',
                    'is_current',
                    'update_required',
                    'latest_version',
                    'minimum_version',
                    'store_url',
                ],
            ],
            'meta' => ['message'],
            'jsonapi' => ['version'],
        ])
        ->assertJson([
            'data' => [
                'type' => 'app-version',
                'id' => 'ios',
                'attributes' => [
                    'platform' => 'ios',
                    'latest_version' => '1.2.0',
                    'store_url' => 'https://apps.apple.com/app/example',
                ],
            ],
        ]);
});

test('returns Android version information when platform=android', function (): void {
    $response = $this->getJson('/api/v1/app/version?version=1.0.0&platform=android');

    $response->assertOK()
        ->assertJson([
            'data' => [
                'type' => 'app-version',
                'id' => 'android',
                'attributes' => [
                    'platform' => 'android',
                    'latest_version' => '1.3.0',
                    'store_url' => 'https://play.google.com/store/apps/example',
                ],
            ],
        ]);
});

test('returns validation error when platform is not specified', function (): void {
    $response = $this->getJson('/api/v1/app/version?version=1.0.0');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['platform']);
});

test('returns validation error for invalid platform', function (): void {
    $response = $this->getJson('/api/v1/app/version?version=1.0.0&platform=windows');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['platform']);
});

test('returns validation error for invalid tenant', function (): void {
    $response = $this->getJson('/api/v1/app/version?version=1.0.0&platform=ios&tenant=nonexistent');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tenant']);
});

test('falls back to default version requirements when no tenant is given', function (): void {
    $response = $this->getJson('/api/v1/app/version?version=1.1.0&platform=ios');

    $response->assertOK()
        ->assertJson([
            'data' => [
                'attributes' => [
                    'latest_version' => '1.2.0',
                    'minimum_version' => '1.0.0',
                ],
            ],
        ]);
});

test('uses tenant-specific version requirements when tenant is given', function (): void {
    config([
        'tenants.uk.mobile.ios.minimum_version' => '3.0.0',
        'tenants.uk.mobile.ios.current_version' => '3.1.0',
    ]);

    $response = $this->getJson('/api/v1/app/version?version=3.0.0&platform=ios&tenant=uk');

    $response->assertOK()
        ->assertJson([
            'data' => [
                'attributes' => [
                    'latest_version' => '3.1.0',
                    'minimum_version' => '3.0.0',
                    'is_current' => false,
                    'update_required' => false,
                ],
            ],
        ]);
});

test('returns is_current true when client version equals current version', function (): void {
    $response = $this->getJson('/api/v1/app/version?version=1.2.0&platform=ios');

    $response->assertOK()
        ->assertJson([
            'data' => [
                'attributes' => [
                    'is_current' => true,
                    'update_required' => false,
                    'latest_version' => '1.2.0',
                    'minimum_version' => '1.0.0',
                ],
            ],
        ]);
});

test('returns update_required true when client version is below minimum', function (): void {
    $response = $this->getJson('/api/v1/app/version?version=0.9.0&platform=ios');

    $response->assertOK()
        ->assertJson([
            'data' => [
                'attributes' => [
                    'is_current' => false,
                    'update_required' => true,
                ],
            ],
        ]);
});

test('handles missing version parameter gracefully', function (): void {
    $response = $this->getJson('/api/v1/app/version?platform=ios');

    $response->assertOK()
        ->assertJson([
            'data' => [
                'attributes' => [
                    'is_current' => false,
                    'update_required' => true,
                ],
            ],
        ]);
});

test('missing tenant secret header is rejected', function (): void {
    $this->withoutHeader('X-Tenant-Secret')
        ->getJson('/api/v1/app/version?version=1.0.0&platform=ios')
        ->assertUnauthorized();
});

test('invalid tenant secret header is rejected', function (): void {
    $this->withHeaders(['X-Tenant-Secret' => 'wrong-secret'])
        ->getJson('/api/v1/app/version?version=1.0.0&platform=ios')
        ->assertUnauthorized();
});

test('returns maintenance response when app is in maintenance mode', function (): void {
    File::put(storage_path('framework/down'), json_encode([
        'except' => [],
        'redirect' => null,
        'retry' => 60,
        'refresh' => null,
        'secret' => null,
        'status' => 503,
        'template' => null,
    ]));

    $response = $this->getJson('/api/v1/app/version?version=1.0.0&platform=ios');

    $response->assertStatus(503)
        ->assertJsonStructure([
            'data' => [
                'type',
                'id',
                'attributes' => [
                    'maintenance',
                    'message',
                    'retry_after',
                ],
            ],
            'meta' => ['message'],
        ])
        ->assertJson([
            'data' => [
                'type' => 'maintenance',
                'id' => 'current',
                'attributes' => [
                    'maintenance' => true,
                    'message' => 'We are currently performing maintenance.',
                    'retry_after' => 60,
                ],
            ],
        ]);
});

test('maintenance mode takes priority over version check', function (): void {
    File::put(storage_path('framework/down'), json_encode([
        'except' => [],
        'redirect' => null,
        'retry' => null,
        'refresh' => null,
        'secret' => null,
        'status' => 503,
        'template' => null,
    ]));

    $response = $this->getJson('/api/v1/app/version?version=1.2.0&platform=ios');

    $response->assertStatus(503)
        ->assertJson([
            'data' => [
                'type' => 'maintenance',
            ],
        ]);
});
