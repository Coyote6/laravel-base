<?php
	
	
namespace Coyote6\LaravelBase\Traits;

//
// Seeder script based on the answer by Huy Nguyen
//
// @see https://stackoverflow.com/questions/40095764/laravel-registering-seeds-in-a-package
//


use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use Symfony\Component\Console\Output\ConsoleOutput;

trait ServiceProviderSeedsDb {
	
	protected $seederDir;
	
	
	//
	// If the app is running from the console,
	// check the commmand to see if it is a seed command
	//
	protected function seedDbOnCommand ($dir) {
		$this->seederDir = $dir;
		if ($this->app->runningInConsole()) {
            if ($this->isSeedCommand()) {
                $this->addSeedsAfterConsoleCommandFinished();
            }
        }
    }
	
	
	//
    // Get the current commands arguments, and see if they
    // contain db:seed or --seed and do not include
    // --class, help, or -h.
    //
    // @return bool
    //
    protected function isSeedCommand () : bool {
        $args = Request::server('argv', null);
        if (is_array ($args)) {
            $command = implode (' ', $args);
            if (
            	(str_contains ($command, 'db:seed') || str_contains ($command, '--seed')) && 
            	!str_contains ($command, '--class') && !str_contains ($command,'help') && !str_contains ($command, '-h')
            ) {
                return true;
            }
        }
        return false;
    }
    
    
    /**
     * Add seeds from the $seed_path after the current command in console finished.
     */
    protected function addSeedsAfterConsoleCommandFinished () {
        Event::listen (CommandFinished::class, function (CommandFinished $event) {
            //
            // Accept command in console only,
            // exclude all commands from Artisan::call() method.
            //
            if ($event->output instanceof ConsoleOutput) {
                $this->addSeeds ();
            }
        });
    }
    
    //
    // Loop through the seeded directory and call
    // db:seed on each file.
    //
    // @return null
    //
    protected function addSeeds () {
        $file_names = glob ($this->seederDir . '/*.php');
        foreach ($file_names as $filename) {
            $classes = $this->getClassesFromFile ($filename);
            foreach ($classes as $class) {
                Artisan::call('db:seed', [ '--class' => $class, '--force' => '' ]);
            }
        }
    }
	
	
	//
    // Get full class names declared in the specified file.
    //
    // @param string $filename
    // @return [] (class names)
    //
    private function getClassesFromFile (string $filename) : array {
        
        // Get namespace of class (if vary)
        $namespace = "";
        $lines = file ($filename);
        $namespaceLines = preg_grep('/^namespace /', $lines);
        
        if (is_array($namespaceLines)) {
            $namespaceLine = array_shift ($namespaceLines);
            $match = array();
            preg_match('/^namespace (.*);$/', $namespaceLine, $match);
            $namespace = array_pop($match);
        }

        // Get name of all class has in the file.
        $classes = array();
        $php_code = file_get_contents ($filename);
        $tokens = token_get_all ($php_code);
        $count = count ($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {
                $class_name = $tokens[$i][1];
                if ($namespace !== "") {
                    $classes[] = $namespace . "\\$class_name";
                } 
                else {
                    $classes[] = $class_name;
                }
            }
        }

        return $classes;
    }

}
