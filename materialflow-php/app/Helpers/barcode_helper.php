<?php

/**
 * Barcode helpers for the compact Code 128 unit-barcode scheme.
 *
 * Each physical unit gets a compact, globally-unique value of the form
 * PREFIX + zero-padded serial (e.g. MFU000000123). The value encodes ONLY a
 * lookup id — item code, batch reference and unit number are stored in their
 * own columns, not in the barcode.
 */

if (! function_exists('mf_barcode_prefix')) {
    /**
     * The fixed prefix for compact unit barcodes.
     */
    function mf_barcode_prefix(): string
    {
        return 'MFU';
    }
}

if (! function_exists('mf_unit_barcode')) {
    /**
     * Build the compact unit barcode from a global serial.
     * e.g. 123 -> "MFU000000123" (prefix + 9-digit zero-padded serial).
     */
    function mf_unit_barcode(int $serial): string
    {
        return mf_barcode_prefix() . str_pad((string) $serial, 9, '0', STR_PAD_LEFT);
    }
}

if (! function_exists('mf_is_code128_safe')) {
    /**
     * Validate a barcode string for reliable Code 128 encoding + printing.
     *
     * Returns null when valid, otherwise a user-facing error message.
     *
     * @param bool $allowHyphen item codes (MF-#####) keep the hyphen; compact
     *                          unit codes do not.
     */
    function mf_is_code128_safe(string $value, bool $allowHyphen = false): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return 'Barcode cannot be empty.';
        }
        if (strlen($value) > 64) {
            return 'Barcode is too long (max 64 characters).';
        }
        // Reject multibyte / non-ASCII — Code 128 + utf8mb4_bin columns need
        // plain single-byte characters or the printed bars won't match the DB.
        if (strlen($value) !== mb_strlen($value)) {
            return 'Barcode must use plain ASCII characters only.';
        }

        $pattern = $allowHyphen ? '/^[A-Za-z0-9._-]+$/' : '/^[A-Z0-9]+$/';
        if (! preg_match($pattern, $value)) {
            return $allowHyphen
                ? 'Barcode may contain only letters, digits, dot, underscore and hyphen.'
                : 'Barcode may contain only uppercase letters and digits (no spaces or symbols).';
        }

        return null;
    }
}
