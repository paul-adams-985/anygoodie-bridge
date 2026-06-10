<?php

use Illuminate\Support\Facades\Route;

Route::get('/.well-known/apple-app-site-association', function () {
    return response()->json([
        'applinks' => [
            'apps' => [],
            'details' => [
                [
                    'appIDs' => [config('well-known.ios.team_id').'.'.config('well-known.ios.bundle_id')],
                    'components' => [
                        [
                            '/' => '/*',
                        ],
                    ],
                ],
            ],
        ],
    ]);
});

Route::get('/.well-known/assetlinks.json', function () {
    return response()->json([[
        'relation' => ['delegate_permission/common.handle_all_urls'],
        'target' => [
            'namespace' => 'android_app',
            'package_name' => config('well-known.android.package_name'),
            'sha256_cert_fingerprints' => array_filter([
                config('well-known.android.play_signing_cert_fingerprint'),
            ]),
        ],
    ]]);
});
