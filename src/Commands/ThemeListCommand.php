<?php

namespace Theanh\MultiTheme\Commands;

use Illuminate\Console\Command;
use Theanh\MultiTheme\ThemeContract;

class ThemeListCommand extends Command
{
    protected $signature = 'theme:list';
    
    protected $description = 'List all available themes';

    /**
     * Execute the console command.
     * Get all themes
     *
     * @return void
     */
    public function handle()
    {
        $themes = $this->laravel[ThemeContract::class]->all();
        $headers = ['Name', 'Author', 'Version', 'Parent'];
        $output = [];
        foreach ($themes as $theme) {
            $output[] = [
                'Name'    => $theme->get('name'),
                'Author'  => $theme->get('author'),
                'version' => $theme->get('version'),
                'parent'  => $theme->get('parent'),
            ];
        }
        
        $this->table($headers, $output);
    }
}
