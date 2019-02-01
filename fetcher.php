<?php

/**
 *
 * This is the MassFetcher controller.
 *
 * Configure your settings in `config.php`, then run `php fetcher.php`.
 *
 */
declare(strict_types=1);

namespace parseword\MassFetcher;

use parseword\logger\Logger;

error_reporting(E_ALL);
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('The autoloader is missing; run `composer install` first.' . PHP_EOL);
}
require_once(__DIR__ . '/vendor/autoload.php');

/* Import the configuration */
if (!file_exists('config.php')) {
    die("Fatal error: config.php wasn't found. Copy config.php-dist to "
            . "config.php." . PHP_EOL);
}
require_once('config.php');

/* Run the fetcher */
Logger::info('Configuration is sane; fetching has begun.', true);
$elapsedSeconds = $mf->fetch();
Logger::info("Fetching has finished after {$elapsedSeconds} seconds.", true);
