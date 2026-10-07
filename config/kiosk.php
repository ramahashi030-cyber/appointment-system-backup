<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kiosk Identity
    |--------------------------------------------------------------------------
    |
    | Display name of this kiosk terminal. Recorded on every check-in audit
    | row so multi-terminal deployments can be told apart in Kiosk History.
    |
    */

    'name' => env('KIOSK_NAME', 'OPD-KIOSK-01'),

    /*
    |--------------------------------------------------------------------------
    | Scan Ticket Lifetime (seconds)
    |--------------------------------------------------------------------------
    |
    | How long a verified scan stays confirmable before the kiosk must scan
    | the QR again. The expiry is enforced server-side.
    |
    */

    'scan_ticket_ttl' => (int) env('KIOSK_SCAN_TICKET_TTL', 120),

    /*
    |--------------------------------------------------------------------------
    | Screensaver (seconds)
    |--------------------------------------------------------------------------
    |
    | Idle seconds before the branded screensaver appears, and idle seconds
    | during an unfinished transaction before the kiosk force-resets.
    |
    */

    'screensaver_after' => (int) env('KIOSK_SCREENSAVER_AFTER', 20),

    'reset_after' => (int) env('KIOSK_RESET_AFTER', 60),

    /*
    |--------------------------------------------------------------------------
    | HOMIS Registration Constants
    |--------------------------------------------------------------------------
    |
    | Fixed values the legacy QALINGA1 kiosk wrote into every HOMIS encounter.
    | They are kept in config so operations can override them without code
    | changes; the defaults match the legacy hard-coded values.
    |
    */

    'homis' => [
        'fhud' => env('KIOSK_HOMIS_FHUD', '0001818'),
        'entry_by' => env('KIOSK_HOMIS_ENTRY_BY', 'na'),
        'tacode' => env('KIOSK_HOMIS_TACODE', 'SERVI'),
        'opd_remark' => env('KIOSK_HOMIS_OPD_REMARK', 'PAS Registration'),
        'toecode' => env('KIOSK_HOMIS_TOECODE', 'OPD'),
        'sopcode1' => env('KIOSK_HOMIS_SOPCODE1', 'SELPA'),
        'tele_flag' => env('KIOSK_HOMIS_TELE_FLAG', 'N'),
    ],

];
