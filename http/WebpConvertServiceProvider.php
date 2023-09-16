<?php

declare(strict_types=1);

namespace http;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

/**
 * Class VoyagerDuplicateServiceProvider
 *
 * @category  Package
 * @package   JoyVoyagerDuplicate
 * @author    Ramakant Gangwar <gangwar.ramakant@gmail.com>
 * @copyright 2021 Copyright (c) Ramakant Gangwar (https://github.com/rxcod9)
 * @license   http://github.com/rxcod9/joy-voyager-duplicate/blob/main/LICENSE New BSD License
 * @link      https://github.com/rxcod9/joy-voyager-duplicate
 */
class WebpConvertServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerPublishables();
    }
    protected function registerPublishables(): void
    {
        $this->publishes([
            __DIR__ . '/../config/img-webp-convert.php' => config_path('img-webp-convert.php'),
        ], 'config');
    }
}
