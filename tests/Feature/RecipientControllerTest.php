<?php

describe('preview', function (): void {
    it('redirects to the tenant endpoint, preserving the request path and query string', function (): void {
        config(['tenants.uk.endpoint' => 'https://uk.anygoodie.test']);

        $response = $this->get('/recipient/uk/voucher/abc123/preview?ref=email-campaign');

        $response->assertRedirect('https://uk.anygoodie.test/recipient/uk/voucher/abc123/preview?ref=email-campaign');
    });

    it('redirects to each tenant\'s own configured endpoint', function (string $tenant): void {
        config(["tenants.{$tenant}.endpoint" => "https://{$tenant}.anygoodie.test"]);

        $response = $this->get("/recipient/{$tenant}/voucher/abc123/preview");

        $response->assertRedirect("https://{$tenant}.anygoodie.test/recipient/{$tenant}/voucher/abc123/preview");
    })->with([
        'uk' => 'uk',
        'us' => 'us',
    ]);

    it('redirects even when no matching voucher share exists, proving no model binding occurs', function (): void {
        config(['tenants.uk.endpoint' => 'https://uk.anygoodie.test']);

        $response = $this->get('/recipient/uk/voucher/does-not-exist/preview');

        $response->assertRedirect('https://uk.anygoodie.test/recipient/uk/voucher/does-not-exist/preview');
    });
});
