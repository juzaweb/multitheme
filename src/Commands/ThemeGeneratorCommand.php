<?php

namespace Tadcms\MultiTheme\Commands;

use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem as File;
use Illuminate\Support\Str;

class ThemeGeneratorCommand extends Command
{
    protected $signature = 'theme:make {name}';
    
    protected $description = 'Create Theme Folder Structure';

    /**
     * Filesystem.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Config.
     *
     * @var \Illuminate\Support\Facades\Config
     */
    protected $config;

    /**
     * Theme Folder Path.
     *
     * @var string
     */
    protected $themePath;

    /**
     * Create Theme Info.
     *
     * @var array
     */
    protected $theme;

    /**
     * Created Theme Structure.
     *
     * @var array
     */
    protected $themeFolders;

    /**
     * Theme Stubs.
     *
     * @var string
     */
    protected $themeStubPath;

    /**
     * ThemeGeneratorCommand constructor.
     *
     * @param Repository $config
     * @param File       $files
     */
    public function __construct(Repository $config, File $files)
    {
        $this->config = $config;

        $this->files = $files;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->themePath = base_path('Themes');
        $this->theme['name'] = strtolower($this->argument('name'));

        $this->init();
    }

    /**
     * Theme Initialize.
     *
     * @return void
     */
    protected function init()
    {
        $createdThemePath = $this->themePath.'/'.$this->theme['name'];

        if ($this->files->isDirectory($createdThemePath)) {
            $this->error('Sorry Boss '.ucfirst($this->theme['name']).' Theme Folder Already Exist !!!');
            exit();
        }

        $this->consoleAsk();

        $this->themeFolders = [
            'assets'  => 'assets',
            'views'   => 'views',
            'lang'    => 'lang',
            'lang/en' => 'lang/en',
        
            'css' => 'assets/css',
            'js'  => 'assets/js',
            'img' => 'assets/images',
        
            'layouts' => 'views/layouts',
        ];
        
        $this->themeStubPath = __DIR__ . '/../../stubs';

        $themeStubFiles =  [
            'css'    => 'assets/css/app.css',
            'layout' => 'views/layouts/master.blade.php',
            'page'   => 'views/welcome.blade.php',
            'lang'   => 'lang/en/content.php',
        ];
        
        $themeStubFiles['theme'] = 'theme.json';
        $themeStubFiles['changelog'] = 'changelog.yml';

        $this->makeDir($createdThemePath);

        foreach ($this->themeFolders as $key => $folder) {
            $this->makeDir($createdThemePath.'/'.$folder);
        }

        $this->createStubs($themeStubFiles, $createdThemePath);

        $this->info(ucfirst($this->theme['name']).' Theme Folder Successfully Generated !!!');
    }

    /**
     * Console command ask questions.
     *
     * @return void
     */
    public function consoleAsk()
    {
        $this->theme['title'] = $this->ask('What is theme title?');

        $this->theme['description'] = $this->ask('What is theme description?', false);
        $this->theme['description'] = !$this->theme['description'] ? '' : Str::title($this->theme['description']);

        $this->theme['author'] = $this->ask('What is theme author name?', false);
        $this->theme['author'] = !$this->theme['author'] ? '' : Str::title($this->theme['author']);

        $this->theme['version'] = $this->ask('What is theme version?', false);
        $this->theme['version'] = !$this->theme['version'] ? '1.0.0' : $this->theme['version'];
        $this->theme['parent'] = '';
        $this->theme['css'] = '';
        $this->theme['js'] = '';

        if ($this->confirm('Any parent theme?')) {
            $this->theme['parent'] = $this->ask('What is parent theme name?');
            $this->theme['parent'] = strtolower($this->theme['parent']);
        }
    }

    /**
     * Create theme stubs.
     *
     * @param array  $themeStubFiles
     * @param string $createdThemePath
     */
    public function createStubs($themeStubFiles, $createdThemePath)
    {
        foreach ($themeStubFiles as $filename => $storePath) {
            if ($filename == 'changelog') {
                $filename = 'changelog'.pathinfo($storePath, PATHINFO_EXTENSION);
            } elseif ($filename == 'theme') {
                $filename = pathinfo($storePath, PATHINFO_EXTENSION);
            } elseif ($filename == 'css' || $filename == 'js') {
                $this->theme[$filename] = ltrim(
                    $storePath,
                    rtrim($this->config->get('theme.folders.assets'), '/').'/'
                );
            }
            $themeStubFile = $this->themeStubPath.'/'.$filename.'.stub';
            $this->makeFile($themeStubFile, $createdThemePath.'/'.$storePath);
        }
    }

    /**
     * Make directory.
     *
     * @param string $directory
     *
     * @return void
     */
    protected function makeDir($directory)
    {
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Make file.
     *
     * @param string $file
     * @param string $storePath
     *
     * @return void
     */
    protected function makeFile($file, $storePath)
    {
        if ($this->files->exists($file)) {
            $content = $this->replaceStubs($this->files->get($file));

            $this->files->put($storePath, $content);
        }
    }

    /**
     * Replace Stub string.
     *
     * @param string $contents
     *
     * @return string
     */
    protected function replaceStubs($contents)
    {
        $mainString = [
            '[NAME]',
            '[TITLE]',
            '[DESCRIPTION]',
            '[AUTHOR]',
            '[PARENT]',
            '[VERSION]',
            '[CSSNAME]',
            '[JSNAME]',
        ];
        $replaceString = [
            $this->theme['name'],
            $this->theme['title'],
            $this->theme['description'],
            $this->theme['author'],
            $this->theme['parent'],
            $this->theme['version'],
            $this->theme['css'],
            $this->theme['js'],
        ];

        $replaceContents = str_replace($mainString, $replaceString, $contents);

        return $replaceContents;
    }
}
