<?php

/**
 * Datetime helpers — DB timestamps are always UTC (enforced in
 * app/Config/Events.php by `SET time_zone = '+00:00'` on connect), so
 * every display MUST run them through `mf_local_datetime()` to get the
 * user's local wall-clock time. Without this, users in IST see UTC and
 * think the app "shows different times on different pages".
 *
 * Timezone is taken from the DISPLAY_TIMEZONE .env var, falling back to
 * Asia/Kolkata (the primary deployment). Set it per install.
 */

if (! function_exists('mf_display_timezone')) {
    function mf_display_timezone(): DateTimeZone
    {
        static $tz = null;
        if ($tz === null) {
            $name = getenv('DISPLAY_TIMEZONE') ?: 'Asia/Kolkata';
            try {
                $tz = new DateTimeZone($name);
            } catch (Exception $e) {
                $tz = new DateTimeZone('UTC');
            }
        }
        return $tz;
    }
}

if (! function_exists('mf_local_datetime')) {
    /**
     * Convert a UTC DB timestamp string to the display timezone and format it
     * as "08 Aug 2026, 3:40 pm" — one format used everywhere in the UI.
     */
    function mf_local_datetime(?string $utc, string $format = 'd M Y, g:i a'): string
    {
        if ($utc === null || $utc === '') {
            return '—';
        }
        try {
            $dt = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
            return $dt->setTimezone(mf_display_timezone())->format($format);
        } catch (Exception $e) {
            return $utc;
        }
    }
}

if (! function_exists('mf_local_date')) {
    /**
     * Date-only variant — "08 Aug 2026".
     */
    function mf_local_date(?string $utc): string
    {
        return mf_local_datetime($utc, 'd M Y');
    }
}
