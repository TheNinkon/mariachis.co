<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class PortalHosts
{
    public const PORTAL_ADMIN = 'admin';
    public const PORTAL_PARTNER = 'partner';
    public const PORTAL_PUBLIC = 'public';

    public static function root(): string
    {
        return (string) config('domains.root');
    }

    public static function admin(): string
    {
        return (string) config('domains.admin');
    }

    public static function partner(): string
    {
        return (string) config('domains.partner');
    }

    public static function scheme(): string
    {
        return (string) config('domains.scheme', 'http');
    }

    public static function portalFromRequest(Request $request): string
    {
        return self::portalFromHost($request->getHost());
    }

    public static function portalFromHost(?string $host): string
    {
        $normalizedHost = mb_strtolower(trim((string) $host));

        if ($normalizedHost === mb_strtolower(self::admin())) {
            return self::PORTAL_ADMIN;
        }

        if ($normalizedHost === mb_strtolower(self::partner())) {
            return self::PORTAL_PARTNER;
        }

        return self::PORTAL_PUBLIC;
    }

    public static function sessionCookieForRequest(Request $request): string
    {
        return match (self::portalFromRequest($request)) {
            self::PORTAL_ADMIN => 'admin_session',
            self::PORTAL_PARTNER => 'partner_session',
            default => 'mariachis_session',
        };
    }

    public static function loginRouteNameForRequest(Request $request): string
    {
        return match (self::portalFromRequest($request)) {
            self::PORTAL_PARTNER => 'mariachi.login',
            self::PORTAL_PUBLIC => 'client.login',
            default => 'login',
        };
    }

    public static function loginRouteNameForUser(?User $user): string
    {
        return match (true) {
            $user?->isMariachi() === true => 'mariachi.login',
            $user?->isClient() === true => 'client.login',
            default => 'login',
        };
    }

    public static function dashboardRouteNameForUser(?User $user): string
    {
        return match (true) {
            $user?->isAdmin() === true => 'admin.dashboard',
            $user?->isStaff() === true => 'staff.dashboard',
            $user?->isMariachi() === true => 'mariachi.metrics',
            $user?->isClient() === true => 'client.dashboard',
            default => 'home',
        };
    }

    public static function absoluteUrl(string $host, string $path = '/'): string
    {
        $normalizedPath = '/'.ltrim($path, '/');

        return rtrim(self::scheme().'://'.$host, '/').$normalizedPath;
    }
}
