<?php

class TwoFactorAuth {

    public static function generateSecret(): string {
        $bytes = random_bytes(20);
        return self::base32Encode($bytes);
    }

    public static function getQRUrl(string $email, string $secret): string {
        $issuer = 'EstrateGIA';
        return 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl='
            . urlencode("otpauth://totp/{$issuer}:{$email}?secret={$secret}&issuer={$issuer}");
    }

    public static function verify(string $secret, string $code): bool {
        if (strlen($code) !== 6) return false;
        $timeSlice = floor(time() / 30);
        // Check current and adjacent windows
        for ($i = -1; $i <= 1; $i++) {
            if (self::generateCode($secret, $timeSlice + $i) === $code) return true;
        }
        return false;
    }

    private static function generateCode(string $secret, int $timeSlice): string {
        $secretKey = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = (ord($hash[$offset]) & 0x7F) << 24
                | (ord($hash[$offset + 1]) & 0xFF) << 16
                | (ord($hash[$offset + 2]) & 0xFF) << 8
                | (ord($hash[$offset + 3]) & 0xFF);
        $otp = $binary % 1000000;
        return str_pad((string)$otp, 6, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $result = '';
        $binary = '';
        foreach (str_split($data) as $char) $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        foreach (str_split($binary, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0');
            $result .= $alphabet[bindec($chunk)];
        }
        return $result;
    }

    private static function base32Decode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper(rtrim($data, '='));
        $binary = '';
        foreach (str_split($data) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) continue;
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $result = '';
        foreach (str_split($binary, 8) as $chunk) {
            if (strlen($chunk) < 8) break;
            $result .= chr(bindec($chunk));
        }
        return $result;
    }
}
