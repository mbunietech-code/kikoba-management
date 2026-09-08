<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Optional modules that an organisation can switch on/off from
 * Settings → Modules. A missing setting means "enabled".
 */
class Features
{
    /** Toggleable module keys and their default state. */
    public const MODULES = [
        'opening_shares' => true,
        'shares' => true,
        'community' => true,
        'insurance' => true,
    ];

    /** @return array<string,bool> */
    public static function forOrg(?string $orgId): array
    {
        $stored = $orgId
            ? Setting::where('organization_id', $orgId)
                ->where('key', 'like', 'module_%')
                ->pluck('value', 'key')
            : collect();

        $out = [];
        foreach (self::MODULES as $key => $default) {
            $v = $stored->get("module_{$key}");
            $out[$key] = $v === null ? $default : ($v === '1' || $v === 'true' || $v === 1);
        }

        return $out;
    }

    public static function enabled(string $module): bool
    {
        $features = app()->bound('kikoba.features') ? app('kikoba.features') : self::MODULES;

        return $features[$module] ?? true;
    }
}
