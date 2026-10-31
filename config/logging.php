<?php
use Monolog\Handler\NullHandler;
return [
 'default'=>env('LOG_CHANNEL','stack'),
 'channels'=>[
  'stack'=>['driver'=>'stack','channels'=>['single'],'ignore_exceptions'=>false],
  'single'=>['driver'=>'single','path'=>storage_path('logs/laravel.log'),'level'=>env('LOG_LEVEL','debug'),'replace_placeholders'=>true],
  'null'=>['driver'=>'monolog','handler'=>NullHandler::class],
 ],
];
