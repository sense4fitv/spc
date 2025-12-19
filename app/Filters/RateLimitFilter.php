<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\Response;

class RateLimitFilter implements FilterInterface
{
    /**
     * Maximum number of attempts allowed
     */
    protected int $maxAttempts = 10;

    /**
     * Time window in seconds (default: 5 minutes)
     */
    protected int $timeWindow = 300; // 5 minutes

    /**
     * Block duration in seconds after max attempts (default: 10 minutes)
     */
    protected int $blockDuration = 600; // 10 minutes

    public function before(RequestInterface $request, $arguments = null)
    {
        $cache = \Config\Services::cache();
        $ipAddress = $request->getIPAddress();

        // Get current attempt count
        $attemptKey = 'rate_limit_' . md5($ipAddress . $request->getUri()->getPath());
        $attempts = $cache->get($attemptKey) ?: 0;

        // Check if IP is blocked
        $blockKey = 'rate_limit_blocked_' . md5($ipAddress);
        $blockedUntil = $cache->get($blockKey);

        if ($blockedUntil && $blockedUntil > time()) {
            $remaining = $blockedUntil - time();
            $minutes = ceil($remaining / 60);

            return service('response')
                ->setStatusCode(429)
                ->setJSON([
                    'success' => false,
                    'error' => "Prea multe încercări. Te rugăm să încerci din nou peste {$minutes} minute.",
                ])
                ->setHeader('Retry-After', (string) $remaining);
        }

        // Check if max attempts reached
        if ($attempts >= $this->maxAttempts) {
            // Block IP for blockDuration
            $cache->save($blockKey, time() + $this->blockDuration, $this->blockDuration);

            // Clear attempts
            $cache->delete($attemptKey);

            $minutes = ceil($this->blockDuration / 60);

            return service('response')
                ->setStatusCode(429)
                ->setJSON([
                    'success' => false,
                    'error' => "Prea multe încercări. IP-ul tău a fost blocat pentru {$minutes} minute.",
                ])
                ->setHeader('Retry-After', (string) $this->blockDuration);
        }

        // Increment attempt counter
        $cache->save($attemptKey, $attempts + 1, $this->timeWindow);

        return null; // Continue with request
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // If login was successful (redirect 302), clear the attempt counter
        // This happens in AuthController after successful login
        // The controller should call clearRateLimit() explicitly after successful login

        return $response;
    }

    /**
     * Clear rate limit for an IP (useful for testing or manual unlock)
     */
    public static function clearRateLimit(string $ipAddress, ?string $path = null): void
    {
        $cache = \Config\Services::cache();

        if ($path) {
            $attemptKey = 'rate_limit_' . md5($ipAddress . $path);
            $cache->delete($attemptKey);
        }

        $blockKey = 'rate_limit_blocked_' . md5($ipAddress);
        $cache->delete($blockKey);
    }
}
