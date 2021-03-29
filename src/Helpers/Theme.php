<?php

namespace Theanh\MultiTheme\Helpers;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\File;
use Illuminate\View\ViewFinderInterface;
use Noodlehaus\Config;
use Theanh\MultiTheme\ThemeContract;
use Theanh\MultiTheme\Exceptions\ThemeNotFoundException;

/**
 * Class Tadcms\Helpers\Theme
 *
 * @package    Theanh\Tadcms
 * @author     The Anh Dang <dangtheanh16@gmail.com>
 * @link       https://github.com/theanhk/tadcms
 * @license    MIT
 */
class Theme implements ThemeContract
{
    /**
     * Theme Root Path.
     *
     * @var string
     */
    protected $basePath;
    
    /**
     * All Theme Information.
     *
     * @var array
     */
    protected $themes = [];
    
    /**
     * Blade View Finder.
     *
     * @var \Illuminate\View\ViewFinderInterface
     */
    protected $finder;
    
    /**
     * Application Container.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app;
    
    /**
     * Translator.
     *
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $lang;
    
    /**
     * FileConfig.
     *
     * @var Repository
     */
    protected $config;
    
    /**
     * Current Active Theme.
     *
     * @var string|string
     */
    private $activeTheme = null;
    
    /**
     * Theme constructor.
     *
     * @param Container           $app
     * @param ViewFinderInterface $finder
     * @param Repository          $config
     * @param Translator          $lang
     */
    public function __construct(Container $app, ViewFinderInterface $finder, Repository $config, Translator $lang)
    {
        $this->config = $config;
        $this->app = $app;
        $this->finder = $finder;
        $this->lang = $lang;
        $this->basePath = base_path('Themes');
    }
    
    /**
     * Set current theme.
     *
     * @param string $theme
     * @return void
     */
    public function set($theme)
    {
        if (!$this->has($theme)) {
            throw new ThemeNotFoundException($theme);
        }
        
        $this->loadTheme($theme);
        $this->activeTheme = $theme;
    }
    
    /**
     * Check if theme exists.
     *
     * @param string $theme
     * @return bool
     */
    public function has($theme)
    {
        return is_dir($this->basePath . '/' . $theme);
    }
    
    /**
     * Get particular theme all information.
     *
     * @param string $themeName
     * @return null|string
     */
    public function getThemeInfo($themeName)
    {
        $themePath = $this->basePath . '/' . $themeName;
        $configPath = $themePath . '/theme.json';
        
        if (file_exists($configPath)) {
            $theme = Config::load($configPath);
            $theme['path'] = $themePath;
        
            if (file_exists($themePath . '/screenshot.png')) {
            
            } else {
                $theme['screenshot'] = asset('vendor/tadcms/images/avatar.png');
            }
            
            return $theme;
        }
        
        return null;
    }
    
    public function getChangeLog($themeName)
    {
        $themePath = $this->basePath . '/' . $themeName;
        $changeLogPath = $themePath . '/changelog.yml';
        
        if (file_exists($changeLogPath)) {
            return Config::load($changeLogPath)->all();
        }
    
        return null;
    }
    
    /**
     * Returns current theme or particular theme information.
     *
     * @param string $theme
     * @param bool   $collection
     *
     * @return array|null|string
     */
    public function get($theme = null, $collection = false)
    {
        if (is_null($theme) || !$this->has($theme)) {
            return !$collection ? $this->themes[$this->activeTheme]->all() : $this->themes[$this->activeTheme];
        }
        
        return !$collection ? $this->themes[$theme]->all() : $this->themes[$theme];
    }
    
    /**
     * Get current active theme name only or themeinfo collection.
     *
     * @param bool $collection
     * @return null|string
     */
    public function current($collection = false)
    {
        return !$collection ? $this->activeTheme : $this->getThemeInfo($this->activeTheme);
    }
    
    /**
     * Get all theme information.
     *
     * @return array
     */
    public function all()
    {
        $this->scanThemes();
        
        return $this->themes;
    }
    
    /**
     * Find asset file for theme asset.
     *
     * @param string    $path
     * @param null|bool $secure
     * @return string
     */
    public function assets($path, $secure = null)
    {
        $fullPath = $this->getFullPath($path);
        
        return $this->app['url']->asset($fullPath, $secure);
    }
    
    /**
     * Find theme asset from theme directory.
     *
     * @param string $path
     * @return string|null
     */
    public function getFullPath($path)
    {
        $splitThemeAndPath = explode(':', $path);
        
        if (count($splitThemeAndPath) > 1) {
            if (is_null($splitThemeAndPath[0])) {
                return null;
            }
            $themeName = $splitThemeAndPath[0];
            $path = $splitThemeAndPath[1];
        } else {
            $themeName = $this->activeTheme;
            $path = $splitThemeAndPath[0];
        }
        
        $themeInfo = $this->getThemeInfo($themeName);
        
        if ($this->config['theme.symlink']) {
            $themePath = str_replace(base_path('public').DIRECTORY_SEPARATOR, '', $this->config['theme.symlink_path']).DIRECTORY_SEPARATOR.$themeName.DIRECTORY_SEPARATOR;
        } else {
            $themePath = str_replace(base_path('public').DIRECTORY_SEPARATOR, '', $themeInfo->get('path')).DIRECTORY_SEPARATOR;
        }
        
        $assetPath = 'assets'.DIRECTORY_SEPARATOR;
        $fullPath = $themePath.$assetPath.$path;
        
        if (!file_exists($fullPath) && $themeInfo->has('parent') && !empty($themeInfo->get('parent'))) {
            $themePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $this->getThemeInfo($themeInfo->get('parent'))->get('path')).DIRECTORY_SEPARATOR;
            $fullPath = $themePath.$assetPath.$path;
            
            return $fullPath;
        }
        
        return $fullPath;
    }
    
    /**
     * Get the current theme path to a versioned Mix file.
     *
     * @param string $path
     * @param string $manifestDirectory
     * @return \Illuminate\Support\HtmlString|string
     * @throws \Exception
     */
    public function themeMix($path, $manifestDirectory = '')
    {
        return mix($this->getFullPath($path), $manifestDirectory);
    }
    
    /**
     * Get lang content from current theme.
     *
     * @param string $fallback
     * @return \Illuminate\Contracts\Translation\Translator|string
     */
    public function lang($fallback)
    {
        $splitLang = explode('::', $fallback);
        
        if (count($splitLang) > 1) {
            if (is_null($splitLang[0])) {
                $fallback = $splitLang[1];
            } else {
                $fallback = $splitLang[0].'::'.$splitLang[1];
            }
        } else {
            $fallback = $this->current() . '::' . $splitLang[0];
            if (!$this->lang->has($fallback)) {
                $fallback = $this->getThemeInfo($this->current())->get('parent').'::'.$splitLang[0];
            }
        }
        
        return trans($fallback);
    }
    
    public function isActivate($theme)
    {
        return (get_config('active_theme') == $theme);
    }
    
    public function activate($theme)
    {
        set_config('activated_theme', $theme);
        return $this;
    }
    
    public function deActivate($theme)
    {
        set_config('activated_theme', $theme);
        return $this;
    }
    
    public function delete($theme) {
        if (self::isActivate($theme)) {
            self::deActivate($theme);
        }
        
        File::deleteDirectory(self::getPath($theme), true);
    }
    
    public function getPath($theme)
    {
        return $this->basePath . '/' . $theme;
    }
    
    /**
     * Scan for all available themes.
     *
     * @return void
     */
    private function scanThemes()
    {
        $themeDirectories = glob($this->basePath.'/*', GLOB_ONLYDIR);
        $themes = [];
        foreach ($themeDirectories as $themePath) {
            $themeFileConfig = $this->getThemeInfo(basename($themePath));
            if ($themeFileConfig->has('name')) {
                $themes[$themeFileConfig->get('name')] = $themeFileConfig->all();
            }
        }
        
        $this->themes = $themes;
    }
    
    /**
     * Map view map for particular theme.
     *
     * @param string $theme
     * @return void
     */
    private function loadTheme($theme)
    {
        
        if (is_null($theme)) {
            throw new ThemeNotFoundException($theme);
        }
        
        $themeInfo = $this->getThemeInfo($theme);
        
        if (is_null($themeInfo)) {
            throw new ThemeNotFoundException($theme);
        }
        
        if (@$themeInfo->get('parent')) {
            $this->loadTheme($themeInfo->get('parent'));
        }
        
        $viewPath = $themeInfo->get('path') .'/views';
        $langPath = $themeInfo->get('path') .'/lang';
        
        $this->finder->prependLocation($viewPath);
        $this->finder->prependNamespace($theme, $viewPath);
       
        $this->lang->addNamespace($theme, $langPath);
    }
}