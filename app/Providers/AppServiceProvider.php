<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Symfony\Component\Console\Helper\ProgressBar;
use App\Services\FormattedFileSize;
use App\Config as AppConfig;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        ProgressBar::setPlaceholderFormatterDefinition(
            'current_size',
            static function (ProgressBar $progressBar, \Illuminate\Console\OutputStyle $output) {
                return FormattedFileSize::getFormattedFileSize($progressBar->getProgress());
            }
        );
        ProgressBar::setPlaceholderFormatterDefinition(
            'max_size',
            static function (ProgressBar $progressBar, \Illuminate\Console\OutputStyle $output) {
                return FormattedFileSize::getFormattedFileSize($progressBar->getMaxSteps());
            }
        );
    }

    /**
     * @return void
     *
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function register(): void
    {
        $this->app->bind('app-config', static function () {
            return new AppConfig();
        });
    }
}
