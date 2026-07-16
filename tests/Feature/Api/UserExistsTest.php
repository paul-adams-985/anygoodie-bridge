<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config(['services.tenant_lookup.secret' => 'test-secret']);
});

test('missing tenant secret header is rejected', function (): void {
    $this->postJson('/api/v1/recipient/user-exists', [
        'email' => 'someone@example.com',
    ])->assertUnauthorized();
});

test('invalid tenant secret header is rejected', function (): void {
    $this->withHeaders(['X-Tenant-Secret' => 'wrong-secret'])
        ->postJson('/api/v1/recipient/user-exists', [
            'email' => 'someone@example.com',
        ])->assertUnauthorized();
});

test('returns exists true with the tenant that has the account', function (): void {
    // Only 'uk' hosts users without being isolated by default.
    Http::preventStrayRequests();
    Http::fake([
        'https://uk-service.anygoodie.com/api/v1/recipient/user-exists' => Http::response([
            'data' => ['exists' => true, 'tenant' => 'uk'],
        ]),
    ]);

    $response = $this->withHeaders(['X-Tenant-Secret' => 'test-secret'])
        ->postJson('/api/v1/recipient/user-exists', [
            'email' => 'existing@example.com',
        ]);

    $response->assertOK()
        ->assertJson(['data' => ['exists' => true, 'tenant' => 'uk']]);

    Http::assertSent(function (ClientRequest $request): bool {
        return $request->url() === 'https://uk-service.anygoodie.com/api/v1/recipient/user-exists'
            && $request['email'] === 'existing@example.com'
            && $request->hasHeader('X-Tenant-Secret', 'test-secret');
    });
});

test('returns exists false when no joinable tenant has the account', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://uk-service.anygoodie.com/api/v1/recipient/user-exists' => Http::response([
            'data' => ['exists' => false, 'tenant' => null],
        ]),
    ]);

    $response = $this->withHeaders(['X-Tenant-Secret' => 'test-secret'])
        ->postJson('/api/v1/recipient/user-exists', [
            'email' => 'missing@example.com',
        ]);

    $response->assertOK()
        ->assertJson(['data' => ['exists' => false, 'tenant' => null]]);
});

test('skips isolated tenants and stops at the first match', function (): void {
    // Make 'staging' joinable too, alongside the default 'uk'.
    config(['tenants.staging.isolated' => false]);

    Http::preventStrayRequests();
    Http::fake([
        'https://uk-service.anygoodie.com/api/v1/recipient/user-exists' => Http::response([
            'data' => ['exists' => true, 'tenant' => 'uk'],
        ]),
        'https://staging.anygoodie.com/*' => Http::response([
            'data' => ['exists' => true, 'tenant' => 'staging'],
        ]),
    ]);

    $response = $this->withHeaders(['X-Tenant-Secret' => 'test-secret'])
        ->postJson('/api/v1/recipient/user-exists', [
            'email' => 'existing@example.com',
        ]);

    $response->assertOK()
        ->assertJson(['data' => ['exists' => true, 'tenant' => 'uk']]);

    Http::assertNotSent(function (ClientRequest $request): bool {
        return str_starts_with($request->url(), 'https://staging.anygoodie.com');
    });
});

test('validation fails without email', function (): void {
    $this->withHeaders(['X-Tenant-Secret' => 'test-secret'])
        ->postJson('/api/v1/recipient/user-exists', [])
        ->assertUnprocessable();
});

test('an explicit tenant checks only that tenant, bypassing isolation', function (): void {
    // 'staging' is isolated by default, so it would never be reached by the
    // fan-out — but an explicit tenant request should still check it directly.
    Http::preventStrayRequests();
    Http::fake([
        'https://staging.anygoodie.com/api/v1/recipient/user-exists' => Http::response([
            'data' => ['exists' => true, 'tenant' => 'staging'],
        ]),
    ]);

    $response = $this->withHeaders(['X-Tenant-Secret' => 'test-secret'])
        ->postJson('/api/v1/recipient/user-exists', [
            'email' => 'existing@example.com',
            'tenant' => 'staging',
        ]);

    $response->assertOK()
        ->assertJson(['data' => ['exists' => true, 'tenant' => 'staging']]);

    Http::assertSent(function (ClientRequest $request): bool {
        return $request->url() === 'https://staging.anygoodie.com/api/v1/recipient/user-exists'
            && $request['email'] === 'existing@example.com';
    });

    // The default 'uk' tenant is never contacted when a tenant is explicit.
    Http::assertNotSent(function (ClientRequest $request): bool {
        return str_starts_with($request->url(), 'https://uk-service.anygoodie.com');
    });
});

test('an explicit tenant returns exists false when that tenant does not have the account', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://staging.anygoodie.com/api/v1/recipient/user-exists' => Http::response([
            'data' => ['exists' => false, 'tenant' => null],
        ]),
    ]);

    $response = $this->withHeaders(['X-Tenant-Secret' => 'test-secret'])
        ->postJson('/api/v1/recipient/user-exists', [
            'email' => 'missing@example.com',
            'tenant' => 'staging',
        ]);

    $response->assertOK()
        ->assertJson(['data' => ['exists' => false, 'tenant' => null]]);
});

test('validation fails for an unknown tenant', function (): void {
    $this->withHeaders(['X-Tenant-Secret' => 'test-secret'])
        ->postJson('/api/v1/recipient/user-exists', [
            'email' => 'someone@example.com',
            'tenant' => 'not-a-real-tenant',
        ])->assertUnprocessable();
});
