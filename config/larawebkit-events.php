<?php

return [
    // Currency for the (free) offer in the Event schema; search engines expect one.
    'currency' => env('EVENTS_CURRENCY', 'USD'),

    // URL of the listing and event pages: /events/{slug}. Per language: EVENTS_PATHS=tr:etkinlikler,de:veranstaltungen.
    // Changing it moves the event URLs (registration links in sent e-mails use the main path).
    'path' => trim((string) env('EVENTS_PATH', 'events'), '/') ?: 'events',
    'paths' => collect(explode(',', (string) env('EVENTS_PATHS', '')))->filter(fn ($pair) => str_contains($pair, ':'))
        ->mapWithKeys(fn ($pair) => [trim(strstr($pair, ':', true)) => trim(substr(strstr($pair, ':'), 1), ' /')])->filter()->all(),

    // Events per page on /events.
    'per_page' => 12,
];
