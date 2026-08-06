<?php

describe('preview', function (): void {
    it('redirects to the tenant endpoint, preserving the request path and query string', function (): void {
        config(['tenants.uk.endpoint' => 'https://uk.anygoodie.test']);

        $response = $this->get('/uk/abc123/preview?ref=email-campaign');

        $response->assertRedirect('https://uk.anygoodie.test/recipient-inbound/uk/abc123/preview?ref=email-campaign');
    });

    it('redirects to each tenant\'s own configured endpoint', function (string $tenant): void {
        config(["tenants.{$tenant}.endpoint" => "https://{$tenant}.anygoodie.test"]);

        $response = $this->get("/{$tenant}/abc123/preview");

        $response->assertRedirect("https://{$tenant}.anygoodie.test/recipient-inbound/{$tenant}/abc123/preview");
    })->with([
        'uk' => 'uk',
    ]);

    it('redirects even when no matching voucher share exists, proving no model binding occurs', function (): void {
        config(['tenants.uk.endpoint' => 'https://uk.anygoodie.test']);

        $response = $this->get('/uk/does-not-exist/preview');

        $response->assertRedirect('https://uk.anygoodie.test/recipient-inbound/uk/does-not-exist/preview');
    });
});

describe('gift', function (): void {
    it('redirects to the tenant endpoint, preserving the request path and query string', function (): void {
        config(['tenants.uk.endpoint' => 'https://uk.anygoodie.test']);

        $response = $this->get('/uk/abc123/gift?ref=email-campaign');

        $response->assertRedirect('https://uk.anygoodie.test/recipient-inbound/uk/abc123/gift?ref=email-campaign');
    });

    it('redirects to each tenant\'s own configured endpoint', function (string $tenant): void {
        config(["tenants.{$tenant}.endpoint" => "https://{$tenant}.anygoodie.test"]);

        $response = $this->get("/{$tenant}/abc123/gift");

        $response->assertRedirect("https://{$tenant}.anygoodie.test/recipient-inbound/{$tenant}/abc123/gift");
    })->with([
        'uk' => 'uk',
    ]);

    it('redirects even when no matching recipient voucher exists, proving no model binding occurs', function (): void {
        config(['tenants.uk.endpoint' => 'https://uk.anygoodie.test']);

        $response = $this->get('/uk/does-not-exist/gift');

        $response->assertRedirect('https://uk.anygoodie.test/recipient-inbound/uk/does-not-exist/gift');
    });
});
