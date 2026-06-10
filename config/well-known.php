<?php

return [

    /*
    |--------------------------------------------------------------------------
    | iOS Universal Links (Apple App Site Association)
    |--------------------------------------------------------------------------
    |
    | Configuration for the /.well-known/apple-app-site-association file,
    | which tells iOS which paths should be intercepted by the app.
    |
    */

    'ios' => [
        'team_id' => env('IOS_TEAM_ID'),
        'bundle_id' => env('IOS_BUNDLE_ID', 'com.anygoodie.app'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Android App Links (Asset Links)
    |--------------------------------------------------------------------------
    |
    | Configuration for the /.well-known/assetlinks.json file, which proves
    | domain ownership to Android. Uses Google's Play App Signing key
    | fingerprint, which signs the app as distributed to users.
    |
    */

    'android' => [
        'package_name' => env('ANDROID_PACKAGE_NAME', 'com.anygoodie.app'),
        'play_signing_cert_fingerprint' => env('ANDROID_PLAY_SIGNING_CERT_FINGERPRINT'),
    ],

];
