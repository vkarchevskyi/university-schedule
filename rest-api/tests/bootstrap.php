<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

$jwtDir = dirname(__DIR__) . '/var/jwt-test';
$privateKey = $jwtDir . '/private.pem';
$publicKey = $jwtDir . '/public.pem';

if (!is_file($privateKey) || !is_file($publicKey)) {
    if (!is_dir($jwtDir)) {
        mkdir($jwtDir, 0777, true);
    }

    $key = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    if ($key === false) {
        throw new RuntimeException('Unable to generate test JWT key pair.');
    }

    openssl_pkey_export($key, $privatePem, 'test-passphrase');
    $details = openssl_pkey_get_details($key);

    if (!is_string($privatePem) || $details === false || !isset($details['key'])) {
        throw new RuntimeException('Unable to export test JWT key pair.');
    }

    file_put_contents($privateKey, $privatePem);
    file_put_contents($publicKey, $details['key']);
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
