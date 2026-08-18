<?php

namespace App\Support;

final class Gstin
{
    public static function normalize(?string $value): ?string
    {
        $value = strtoupper(preg_replace('/\s+/', '', (string) $value) ?? '');

        return $value === '' ? null : $value;
    }

    public static function isValid(?string $value): bool
    {
        $value = self::normalize($value);

        if ($value === null) {
            return false;
        }

        if (! preg_match((string) config('tax.gstin_pattern'), $value)) {
            return false;
        }

        return self::checksumCharacter($value) === $value[14];
    }

    public static function stateCode(?string $value): ?string
    {
        $value = self::normalize($value);

        if ($value === null || strlen($value) < 2) {
            return null;
        }

        $code = substr($value, 0, 2);

        return array_key_exists($code, config('tax.states', [])) ? $code : null;
    }

    public static function panFromGstin(?string $value): ?string
    {
        $value = self::normalize($value);

        if ($value === null || strlen($value) < 12) {
            return null;
        }

        return substr($value, 2, 10);
    }

    public static function checksumCharacter(string $gstin): string
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $factor = 1;
        $sum = 0;

        for ($i = 0; $i < 14; $i++) {
            $codePoint = strpos($chars, $gstin[$i]);
            $product = $codePoint * $factor;
            $factor = $factor === 1 ? 2 : 1;
            $sum += intdiv($product, 36) + ($product % 36);
        }

        $checksum = (36 - ($sum % 36)) % 36;

        return $chars[$checksum];
    }
}
