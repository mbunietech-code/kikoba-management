<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

if (! function_exists('t')) {
    /** Short translation helper for the ported dictionary. */
    function t(string $key, array $replace = []): string
    {
        $full = "k.{$key}";
        $value = __($full, $replace);

        return $value === $full ? $key : $value;
    }
}

if (! function_exists('money')) {
    function money(int|float|null $value, bool $compact = false, bool $symbol = true): string
    {
        $value ??= 0;
        $code = app()->bound('kikoba.currency') ? app('kikoba.currency') : 'TZS';

        if ($compact) {
            $abs = abs($value);
            if ($abs >= 1_000_000_000) {
                $n = number_format($value / 1_000_000_000, 1).'B';
            } elseif ($abs >= 1_000_000) {
                $n = number_format($value / 1_000_000, 1).'M';
            } elseif ($abs >= 1_000) {
                $n = number_format($value / 1_000, 0).'K';
            } else {
                $n = number_format($value, 0);
            }
        } else {
            $n = number_format($value, 0);
        }

        return $symbol ? "{$code} {$n}" : $n;
    }
}

if (! function_exists('num')) {
    function num(int|float|null $value): string
    {
        return number_format($value ?? 0, 0);
    }
}

if (! function_exists('pct')) {
    function pct(int|float|null $value, int $digits = 1): string
    {
        return number_format($value ?? 0, $digits).'%';
    }
}

if (! function_exists('fdate')) {
    function fdate($value, string $style = 'medium'): string
    {
        if (! $value) {
            return '—';
        }
        $d = $value instanceof DateTimeInterface ? $value : Carbon::parse($value);

        return match ($style) {
            'short' => $d->format('d/m/Y'),
            'long' => $d->format('l, d F Y'),
            'datetime' => $d->format('d M Y, H:i'),
            default => $d->format('d M Y'),
        };
    }
}

if (! function_exists('initials')) {
    function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];

        return collect($parts)->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    }
}

if (! function_exists('status_tone')) {
    function status_tone(string $status): string
    {
        return [
            'active' => 'success', 'successful' => 'success', 'paid' => 'success', 'approved' => 'success',
            'completed' => 'success', 'confirmed' => 'success', 'distributed' => 'success', 'released' => 'success',
            'pending' => 'warning', 'submitted' => 'warning', 'partial' => 'warning', 'suspended' => 'warning',
            'under_review' => 'info', 'calculated' => 'info', 'disbursed' => 'info',
            'draft' => 'neutral', 'inactive' => 'neutral', 'dormant' => 'neutral', 'cancelled' => 'neutral',
            'closed' => 'neutral', 'expired' => 'neutral', 'deceased' => 'neutral',
            'overdue' => 'danger', 'rejected' => 'danger', 'failed' => 'danger', 'defaulted' => 'danger', 'reversed' => 'danger',
        ][$status] ?? 'neutral';
    }
}

if (! function_exists('next_reference')) {
    /**
     * Next free sequential reference such as TXN-2026-000031 for the given model column.
     *
     * @param  class-string<Model>  $model
     */
    function next_reference(string $prefix, string $model, string $column, int $pad = 6): string
    {
        $query = fn () => $model::query()->withoutGlobalScopes();
        $year = now()->format('Y');
        $sequence = $query()->count() + 1;

        do {
            $reference = "{$prefix}-{$year}-".str_pad((string) $sequence++, $pad, '0', STR_PAD_LEFT);
        } while ($query()->where($column, $reference)->exists());

        return $reference;
    }
}
