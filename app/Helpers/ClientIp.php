<?php

declare(strict_types=1);

namespace App\Helpers;

final class ClientIp
{
    private const MAX_FORWARDED_LENGTH = 2048;
    private const MAX_FORWARD_CHAIN = 32;

    public static function resolve(): string
    {
        $remoteAddress = self::normalize((string) ($_SERVER['REMOTE_ADDR'] ?? '')) ?? '0.0.0.0';
        $trustedProxies = self::trustedProxies();

        if ($trustedProxies === [] || !self::matchesAny($remoteAddress, $trustedProxies)) {
            return $remoteAddress;
        }

        return match (mb_strtolower(Env::get('TRUSTED_PROXY_HEADER', 'X-Forwarded-For'))) {
            'cf-connecting-ip' => self::singleHeader('HTTP_CF_CONNECTING_IP') ?? $remoteAddress,
            'x-real-ip' => self::singleHeader('HTTP_X_REAL_IP') ?? $remoteAddress,
            default => self::fromForwardedChain($remoteAddress, $trustedProxies),
        };
    }

    private static function fromForwardedChain(string $remoteAddress, array $trustedProxies): string
    {
        $header = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($header === '' || strlen($header) > self::MAX_FORWARDED_LENGTH) {
            return $remoteAddress;
        }

        $parts = explode(',', $header);
        if (count($parts) > self::MAX_FORWARD_CHAIN) {
            return $remoteAddress;
        }

        $chain = [];
        foreach ($parts as $part) {
            $candidate = self::normalize($part);
            if ($candidate !== null) {
                $chain[] = $candidate;
            }
        }
        $chain[] = $remoteAddress;

        for ($index = count($chain) - 1; $index >= 0; $index--) {
            if (!self::matchesAny($chain[$index], $trustedProxies)) {
                return $chain[$index];
            }
        }

        return $remoteAddress;
    }

    private static function singleHeader(string $serverKey): ?string
    {
        return self::normalize((string) ($_SERVER[$serverKey] ?? ''));
    }

    private static function trustedProxies(): array
    {
        $configured = Env::get('TRUSTED_PROXIES', '');
        if ($configured === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $configured))));
    }

    private static function normalize(string $ip): ?string
    {
        $ip = trim($ip, " \t\n\r\0\x0B\"[]");
        if (str_starts_with(mb_strtolower($ip), '::ffff:')) {
            $mapped = substr($ip, 7);
            if (filter_var($mapped, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $mapped;
            }
        }

        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : null;
    }

    private static function matchesAny(string $ip, array $trustedProxies): bool
    {
        foreach ($trustedProxies as $trustedProxy) {
            if (self::matches($ip, $trustedProxy)) {
                return true;
            }
        }
        return false;
    }

    private static function matches(string $ip, string $rule): bool
    {
        if (!str_contains($rule, '/')) {
            $normalizedRule = self::normalize($rule);
            return $normalizedRule !== null && $normalizedRule === $ip;
        }

        [$network, $prefixText] = explode('/', $rule, 2);
        $normalizedNetwork = self::normalize($network);
        if ($normalizedNetwork === null || !ctype_digit($prefixText)) {
            return false;
        }

        $ipBinary = inet_pton($ip);
        $networkBinary = inet_pton($normalizedNetwork);
        if ($ipBinary === false || $networkBinary === false || strlen($ipBinary) !== strlen($networkBinary)) {
            return false;
        }

        $prefix = (int) $prefixText;
        $maximumBits = strlen($ipBinary) * 8;
        if ($prefix < 0 || $prefix > $maximumBits) {
            return false;
        }

        $wholeBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;
        if ($wholeBytes > 0 && substr($ipBinary, 0, $wholeBytes) !== substr($networkBinary, 0, $wholeBytes)) {
            return false;
        }
        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        return (ord($ipBinary[$wholeBytes]) & $mask) === (ord($networkBinary[$wholeBytes]) & $mask);
    }
}
