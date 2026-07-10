<?php
namespace App\Services;

class IdEncryption
{
    private const CIPHER = 'aes-256-cbc';
    private const HASH_ALGO = 'sha256';
    private const IV_LEN = 16;

    public static function encryptFile(string $sourcePath, string $destPath, string $key): void
    {
        $plaintext = file_get_contents($sourcePath);
        if ($plaintext === false) {
            throw new \RuntimeException('Cannot read source file for encryption: ' . $sourcePath);
        }

        $iv = random_bytes(self::IV_LEN);
        $derivedKey = hash_hkdf(self::HASH_ALGO, $key, 32, 'id-encryption-v1');

        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $derivedKey, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            throw new \RuntimeException('OpenSSL encryption failed: ' . openssl_error_string());
        }

        $payload = $iv . $ciphertext;
        if (file_put_contents($destPath, $payload, LOCK_EX) === false) {
            throw new \RuntimeException('Cannot write encrypted file: ' . $destPath);
        }
    }

    public static function decryptFile(string $path, string $key): string
    {
        $payload = file_get_contents($path);
        if ($payload === false || strlen($payload) < self::IV_LEN) {
            throw new \RuntimeException('Cannot read or invalid encrypted file: ' . $path);
        }

        $iv = substr($payload, 0, self::IV_LEN);
        $ciphertext = substr($payload, self::IV_LEN);
        $derivedKey = hash_hkdf(self::HASH_ALGO, $key, 32, 'id-encryption-v1');

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $derivedKey, OPENSSL_RAW_DATA, $iv);
        if ($plaintext === false) {
            throw new \RuntimeException('OpenSSL decryption failed: ' . openssl_error_string());
        }

        return $plaintext;
    }

    public static function isEncryptedFile(string $path): bool
    {
        if (!file_exists($path) || filesize($path) < self::IV_LEN + 1) {
            return false;
        }
        return true;
    }
}
