<?php

use App\Providers\AppServiceProvider;
use App\Providers\PlatformServiceProvider;
use App\Providers\QueueMonitoringServiceProvider;

return [
    AppServiceProvider::class,
    PlatformServiceProvider::class,
    QueueMonitoringServiceProvider::class,
];
