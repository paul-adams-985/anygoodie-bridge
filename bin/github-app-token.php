<?php

/**
 * Mint a short-lived GitHub App installation access token for build-time
 * Composer auth. Runs standalone (no framework bootstrap) so it works
 * before `composer install` has produced a vendor/autoload.php.
 *
 * Laravel Cloud's build command does not export environment variables into
 * the shell — it textually substitutes `$VARNAME` references written
 * literally in the build command before running it. So the app ID,
 * installation ID, and base64-encoded PEM private key must be passed as
 * explicit CLI arguments (each referenced literally in the build command)
 * rather than read via getenv().
 *
 * Outputs the token to stdout only — used via command substitution in the
 * Cloud build command, e.g.:
 *   composer config http-basic.github.com x-access-token $(php bin/github-app-token.php "$GITHUB_APP_ID" "$GITHUB_APP_INSTALLATION_ID" "$GITHUB_APP_PRIVATE_KEY")
 */

function fail(string $message): never
{
    fwrite(STDERR, $message.PHP_EOL);
    exit(1);
}

[, $appId, $installationId, $encodedKey] = [...$argv, null, null, null];

$appId ?: fail('Usage: github-app-token.php <app-id> <installation-id> <base64-private-key>');
$installationId ?: fail('Usage: github-app-token.php <app-id> <installation-id> <base64-private-key>');
$encodedKey ?: fail('Usage: github-app-token.php <app-id> <installation-id> <base64-private-key>');

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
