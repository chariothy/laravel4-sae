<?php namespace Chariothy;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SaePatch extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'sae';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Non-intrusively patches Laravel4 for SAE.';

    const CONFIG_FOLDER = 'app/config/sae';
    const PHP_EXT = '.php';
    const BOOTSTRAP_START = 'bootstrap/start.php';
    const START_GLOBAL = 'app/start/global.php';

    const OVERWRITE = 0;
    const IGNORE = 1;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
    {
        $statements = require __DIR__ . '/../statements.php';

        $patches = array(
            array('addFolder', SaePatch::CONFIG_FOLDER),
            array('addFile', SaePatch::CONFIG_FOLDER . '/database.php', $statements['db']),
            array('addFile', SaePatch::CONFIG_FOLDER . '/app.php', $statements['app']),
            array('addFile', 'config.yaml', $statements['yaml']),
            array('addFile', 'favicon.ico', ''),
            array('patchFile', SaePatch::BOOTSTRAP_START, '/\s\$env\s*=\s*\$app->detectEnvironment\((.+?)[\)}]\);/s',
                $statements['env'], true, 'detectEnvironment', '/\s\$isSae\s*=\s*class_exists\(\'SaeObject\'\);/'),
            array('patchFile', SaePatch::BOOTSTRAP_START, '/\srequire\s+\$framework.\'\/Illuminate\/Foundation\/start.php\';/s',
                $statements['bind'], false, 'wrap storage', null),
            array('patchFile', SaePatch::START_GLOBAL, '/\srequire app_path\(\)\.\'\/filters\.php\';/s',
                $statements['handler'], false, 'SaeDebugHandler', null),
        );

        if ($this->patch($patches)) {
            $this->info(' - THE END.');
        } else {
            $this->info('');
            $this->info('Seems you have already patched SAE before,');
            $this->info('You can use "php artisan sae -o" to overwrite all ignored items.');
            $this->info('See help message using "php artisan sae -h".');
        }
    }

    private function patch($patches)
    {
        $nonPatched = true;

        foreach ($patches as $patch) {
            $method = $patch[0];
            $params = array_slice($patch, 1);
            $patched = call_user_func_array(array($this, $method), $params);
            $nonPatched = $nonPatched && $patched;
        }
        return $nonPatched;
    }

    private function addFile()
    {
        //Arguments
        list($path, $content) = func_get_args();
        $overwrite = $this->option('overwrite');
        //$undo = $this->option('undo');

        if(!$overwrite and file_exists($path)) {
            $this->info(" - Ignored. File '$path' already exists.");
            return false;
        }
        $this->backupFile($path);
        if(file_put_contents($path, $content) !== false) {
            $this->info(" - Successfully added file '$path'.");
        } else {
            throw new \Exception(" Failed to add file '$path'.");
        }
        return true;
    }

    private function addFolder()
    {
        //Arguments
        list($folder) = func_get_args();
        $overwrite = $this->option('overwrite');
        //$undo = $this->option('undo');

        if(!$overwrite and file_exists($folder)) {
            $this->info(" - Ignored. Folder '$folder' already exists.");
            return false;
        }

        if (!file_exists($folder)) {
            if (mkdir($folder)) {
                $this->info(" - Successfully created folder '$folder'");
            } else {
                throw new \Exception(" Failed to create folder '$folder'");
            }
        }
        return true;
    }

    private function patchFile()
    {
        //Arguments
        list($path, $targetPattern, $patch, $commentTarget, $description, $kernalPattern) = func_get_args();
        $overwrite = $this->option('overwrite');
        //$undo = $this->option('undo');

        $content = file_get_contents($path);
        if(!$overwrite and (empty($kernalPattern) ? strpos($content, $patch) : preg_match($kernalPattern, $content))) {
            $this->info(" - Ignored. Patch '$description' for sae at file '$path' already exists.");
            return false;
        }
        $this->backupFile($path);

        if(($output=$this->insertString(
                $targetPattern, $content, $patch, $commentTarget)
            ) !== false) {
            file_put_contents($path, $output);
            $this->info(" - Successfully patched '$description' for sae at file '$path'.");
        } else {
            throw new \Exception(" Failed to patch '$description' for sae at file '$path' (Can not match target pattern).");
        }
        return true;
    }

    private function get(&$var, $default=null) {
        return isset($var) ? $var : $default;
    }

    private function backupFile($path)
    {
        if (!file_exists($path)) return false;
        $ext = pathinfo($path)['extension'];
        $basePath = dirname($path)=='.' ? basename($path, $ext) : dirname($path). '/' . basename($path, $ext);
        $n = 1;
        $backupPath = $basePath . 'bak.' . $ext;
        while (file_exists($backupPath)) {
            $backupPath = $basePath . 'bak' . $n . '.' . $ext;
            $n++;
        }
        if(copy($path, $backupPath)) {
            $this->info(" - Successfully backed up '$path'");
        } else {
            throw new \Exception(" Failed to back up '$path'");
        }
        return $backupPath;
    }

    private function insertString($pattern, $content, $insert, $commentMatched=true)
    {
        if(empty($pattern)) {
            return $content . "\n\n" . $insert;
        } elseif(preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $matched = $matches[0][0];
            $matchedOffset = $matches[0][1];
            $comment = $commentMatched===true ? str_replace("\n", "\n//", $matched) : $matched;

            $output = substr($content, 0, $matchedOffset);
            $output .= $comment . "\n\n";

            $output .= $insert;
            $output .= substr($content, $matchedOffset + strlen($matched));
            return $output;
        }
        return false;
    }

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			//array('example', InputArgument::OPTIONAL, 'Example.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
            array('--overwrite', '-o', InputOption::VALUE_NONE, 'Patch laravel4 even it has been patched before.'),
            //array('--undo', '-u', InputOption::VALUE_NONE, 'Undo all patch done by SaePatch.'),
		);
	}

}
