<?php

describe('assetlinks.json', function (): void {
    it('returns valid structure', function (): void {
        $this->getJson('/.well-known/assetlinks.json')
            ->assertOk()
            ->assertJsonStructure([[
                'relation',
                'target' => ['namespace', 'package_name', 'sha256_cert_fingerprints'],
            ]]);
    });

    it('returns relation as array', function (): void {
        $response = $this->getJson('/.well-known/assetlinks.json');

        expect($response->json('0.relation'))->toBeArray();
    });

    it('returns sha256_cert_fingerprints as array', function (): void {
        $response = $this->getJson('/.well-known/assetlinks.json');

        expect($response->json('0.target.sha256_cert_fingerprints'))->toBeArray();
    });

    it('uses configured package name', function (): void {
        config(['well-known.android.package_name' => 'com.example.test']);

        $this->getJson('/.well-known/assetlinks.json')
            ->assertJsonPath('0.target.package_name', 'com.example.test');
    });

    it('filters null fingerprints and reindexes', function (): void {
        config([
            'well-known.android.cert_fingerprint' => null,
            'well-known.android.play_signing_cert_fingerprint' => 'AA:BB:CC',
        ]);

        $fingerprints = $this->getJson('/.well-known/assetlinks.json')
            ->json('0.target.sha256_cert_fingerprints');

        expect($fingerprints)->toBe(['AA:BB:CC']);
    });

    it('includes both fingerprints when both set', function (): void {
        config([
            'well-known.android.cert_fingerprint' => 'AA:BB:CC',
            'well-known.android.play_signing_cert_fingerprint' => 'DD:EE:FF',
        ]);

        $fingerprints = $this->getJson('/.well-known/assetlinks.json')
            ->json('0.target.sha256_cert_fingerprints');

        expect($fingerprints)->toBe(['AA:BB:CC', 'DD:EE:FF']);
    });
});

describe('apple-app-site-association', function (): void {
    it('returns valid structure', function (): void {
        $this->getJson('/.well-known/apple-app-site-association')
            ->assertOk()
            ->assertJsonStructure([
                'applinks' => ['apps', 'details'],
            ]);
    });

    it('returns apps as empty array', function (): void {
        $response = $this->getJson('/.well-known/apple-app-site-association');

        expect($response->json('applinks.apps'))->toBe([]);
    });

    it('uses configured team and bundle id', function (): void {
        config([
            'well-known.ios.team_id' => 'TEAM123',
            'well-known.ios.bundle_id' => 'com.example.app',
        ]);

        $response = $this->getJson('/.well-known/apple-app-site-association');
        $appIDs = $response->json('applinks.details.0.appIDs');

        expect($appIDs)->toBe(['TEAM123.com.example.app']);
    });

    it('contains wildcard path component allowing all routes', function (): void {
        $response = $this->getJson('/.well-known/apple-app-site-association');
        $components = $response->json('applinks.details.0.components');

        $paths = array_column($components, '/');

        expect($paths)->toContain('/*');
    });
});
