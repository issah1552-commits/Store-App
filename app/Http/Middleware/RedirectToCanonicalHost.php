<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('local')) {
            return $next($request);
        }

        $configuredUrl = config('app.url');

        if (! is_string($configuredUrl) || $configuredUrl === '') {
            return $next($request);
        }

        $parsedUrl = parse_url($configuredUrl);

        if (! is_array($parsedUrl) || empty($parsedUrl['host'])) {
            return $next($request);
        }

        $currentHost = $request->getHost();
        $targetHost = $parsedUrl['host'];
        $localHosts = ['127.0.0.1', 'localhost'];

        if (! in_array($currentHost, $localHosts, true) || ! in_array($targetHost, $localHosts, true)) {
            return $next($request);
        }

        $targetScheme = $parsedUrl['scheme'] ?? $request->getScheme();
        $targetPort = isset($parsedUrl['port']) ? (int) $parsedUrl['port'] : null;
        $portMatches = $targetPort === null || $request->getPort() === $targetPort;

        if ($currentHost === $targetHost && $request->getScheme() === $targetScheme && $portMatches) {
            return $next($request);
        }

        $url = $targetScheme.'://'.$targetHost;

        if ($targetPort !== null && ! (($targetScheme === 'http' && $targetPort === 80) || ($targetScheme === 'https' && $targetPort === 443))) {
            $url .= ':'.$targetPort;
        }

        $status = $request->isMethodSafe() ? 302 : 307;

        return redirect()->away($url.$request->getRequestUri(), $status);
    }
}
