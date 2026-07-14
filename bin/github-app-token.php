<?php

/**
 * Mint a short-lived GitHub App installation access token for build-time
 * Composer auth. Runs standalone (no framework bootstrap) so it works
 * before `composer install` has produced a vendor/autoload.php.
 *
 * Requires GITHUB_APP_ID, GITHUB_APP_INSTALLATION_ID, GITHUB_APP_PRIVATE_KEY
 * (PEM, base64-encoded to survive env var storage) in the environment.
 *
 * Outputs the token to stdout only — used via command substitution in the
 * Cloud build command, e.g.:
 *   composer config http-basic.github.com x-access-token $(php bin/github-app-token.php)
 */

function fail(string $message): never
{
    fwrite(STDERR, $message.PHP_EOL);
    exit(1);
}

$appId = getenv('GITHUB_APP_ID') ?: fail('GITHUB_APP_ID not set');
$installationId = getenv('GITHUB_APP_INSTALLATION_ID') ?: fail('GITHUB_APP_INSTALLATION_ID not set');
$encodedKey = getenv('GITHUB_APP_PRIVATE_KEY') ?: fail('GITHUB_APP_PRIVATE_KEY not set');

$privateKey = base64_decode($encodedKey, strict: true) ?: fail('GITHUB_APP_PRIVATE_KEY is not valid base64');

$header = ['alg' => 'RS256', 'typ' => 'JWT'];
$payload = [
    'iat' => time() - 60,
    'exp' => time() + 300,
    'iss' => $appId,
];

$segments = [
    rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '='),
    rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '='),
];

$signature = '';
$key = openssl_pkey_get_private($privateKey) ?: fail('Failed to load GITHUB_APP_PRIVATE_KEY');
openssl_sign(implode('.', $segments), $signature, $key, OPENSSL_ALGO_SHA256);
$segments[] = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

$jwt = implode('.', $segments);

$ch = curl_init("https://api.github.com/app/installations/{$installationId}/access_tokens");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$jwt}",
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
        'User-Agent: laravel-cloud-build',
    ],
]);

$response = curl_exec($ch) ?: fail('curl request to GitHub failed: '.curl_error($ch));
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status !== 201) {
    fail("GitHub API returned {$status}: {$response}");
}

$token = json_decode($response, true)['token'] ?? fail('No token in GitHub response');

echo $token;
