<?php
return [
  'name' => env('APP_NAME', 'Royal Wedding Invitation'),
  'env' => env('APP_ENV', 'production'),
  'debug' => (bool) env('APP_DEBUG', false),
  'url' => env('APP_URL', 'http://localhost'),
  'timezone' => 'Asia/Jakarta',
  'locale' => env('APP_LOCALE', 'id'),
  'fallback_locale' => env('APP_FALLBACK_LOCALE', 'id'),
  'faker_locale' => env('APP_FAKER_LOCALE', 'id_ID'),
  'key' => env('APP_KEY'),
  'cipher' => 'AES-256-CBC',
  'maintenance' => ['driver' => 'file'],
];
