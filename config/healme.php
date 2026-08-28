<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Doctor availability horizon
    |--------------------------------------------------------------------------
    |
    | How far ahead patients can see and book open doctor slots.
    |
    */
    'availability_horizon_days' => (int) env('AVAILABILITY_HORIZON_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Slots returned in patient-facing lists
    |--------------------------------------------------------------------------
    */
    'availability_slots_limit' => (int) env('AVAILABILITY_SLOTS_LIMIT', 48),
];
