<?php

if (! function_exists('mf_settings')) {
    /**
     * Branding settings from app_settings merged over defaults.
     * Cached per request — the layout, print views, and settings page all call this.
     */
    function mf_settings(): array
    {
        static $settings = null;

        if ($settings === null) {
            $settings = [
                'company_name' => 'MaterialFlow',
                'tagline'      => 'BARCODE INVENTORY',
                'logo_icon'    => '▦',
                'address'      => '',
                'phone'        => '',
                'email'        => '',
            ];

            $rows = db_connect()->table('app_settings')->get()->getResultArray();
            foreach ($rows as $row) {
                if (array_key_exists($row['setting_key'], $settings) && $row['setting_value'] !== '') {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            }
        }

        return $settings;
    }
}
