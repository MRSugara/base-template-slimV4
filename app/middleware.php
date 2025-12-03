<?php

declare(strict_types=1);

use App\Application\Middleware\SessionMiddleware;
use Slim\App;
use App\Application\Middleware\RateLimiterMiddleware;


return function (App $app) {
    $app->add(SessionMiddleware::class);
    $app->add(RateLimiterMiddleware::class);

};
