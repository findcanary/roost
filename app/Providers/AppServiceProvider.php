<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Symfony\Component\Console\Helper\ProgressBar;
use App\Traits\FormattedFileSize;

class AppServiceProvider extends ServiceProvider
{
    use FormattedFileSize;

    /**
     * @return void
     */
    public function boot(): void
    {
        ProgressBar::setPlaceholderFormatterDefinition(
            'current_size',
            function (ProgressBar $progressBar, \Illuminate\Console\OutputStyle $output) {
                return $this->getFormattedFileSize($progressBar->getProgress());
            }
        );
        ProgressBar::setPlaceholderFormatterDefinition(
            'max_size',
            function (ProgressBar $progressBar, \Illuminate\Console\OutputStyle $output) {
                return $this->getFormattedFileSize($progressBar->getMaxSteps());
            }
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
