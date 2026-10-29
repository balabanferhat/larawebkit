<?php

return [
    // Currency for the (free) offer in the Event schema; search engines expect one.
    'currency' => env('EVENTS_CURRENCY', 'USD'),

    // Events per page on /events.
    'per_page' => 12,
];
