<?php

define('LARAVEL_START', microtime(true));

$base = '/../../../vendor/autoload.php';
file_exists(__DIR__.$base) ? require __DIR__.$base : require __DIR__ . '/vendor/autoload.php';

Patchwork\Utf8\Bootup::initMbstring();

Illuminate\Support\ClassLoader::register();

if (is_dir($workbench = __DIR__.'/../../../workbench'))
{
    Illuminate\Workbench\Starter::start($workbench);
}