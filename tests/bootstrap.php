<?php

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "Please install composer dependencies: composer install\n";
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

