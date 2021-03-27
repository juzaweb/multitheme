<?php

namespace Theanh\MultiTheme\Providers;

use Illuminate\Support\ServiceProvider;
use Theanh\MultiTheme\ThemeContract;
use Theanh\MultiTheme\Helpers\Theme;

/**
 * Class Theanh\MultiTheme\Providers\ThemeServiceProvider
 *
 * @package    Theanh\Tadcms
 * @author     The Anh Dang <dangtheanh16@gmail.com>
 * @link       https://github.com/theanhk/tadcms
 * @license    MIT
 */
class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        /*if (!File::exists(public_path('Themes')) && !File::exists(config('theme.symlink_path')) && config('theme.symlink') && File::exists(config('theme.theme_path'))) {
            App::make('files')->link(config('theme.theme_path'), config('theme.symlink_path', public_path('Themes')));
        }*/
       
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTheme();
    
        $this->commands([
            \Theanh\MultiTheme\Commands\ThemeGeneratorCommand::class,
        ]);
        
        //$this->loadViewsFrom(__DIR__.'/../Views', 'theme');
    }
    
    /**
     * Register theme required components .
     *
     * @return void
     */
    public function registerTheme()
    {
        $this->app->singleton(ThemeContract::class, function ($app) {
            return new Theme($app, $this->app['view']->getFinder(), $this->app['config'], $this->app['translator']);
        });
    }
}
