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

    const PHP_EXT = '.php';
    private $patches = array();

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
        $configFolder = 'app/config/sae';
        $start = 'bootstrap/start.php';
        $global = 'app/start/global.php';
        $dbConfig = $configFolder. '/database.php.';
        $appConfig = $configFolder.'/app.php';
        $index = 'index.sae.php';
        $yaml = 'config.yaml';
        $favicon = 'favicon.ico';

        $statements = require __DIR__ . '/../statements.php';

        $this->patches = array(
            'config' => array("Add folder $configFolder.", 'addFolder', $configFolder,),
            'db' => array("Add file $dbConfig.", 'addFile', $dbConfig, $statements['db'],),
            'app' => array("Add file $appConfig.", 'addFile', $appConfig, $statements['app'],),
            'index' => array("Add file $index.", 'addFile', $index, $statements['index'],),
            'yaml' => array("Add rewrite rule for SAE.", 'patchFile', $yaml, '/version:\s*\d+/s', $statements['yaml'],
                false, 'SAE rewrite rule', null, ),
            'favicon' => array("Add file $favicon.",'addFile', $favicon, '', ),
            'env' => array('Add closure for $app->detectEnvironment().', 'patchFile', $start, '/\s\$env\s*=\s*\$app->detectEnvironment\((.+?)[\)}]\);/s',
                $statements['env'], true, 'detectEnvironment', '/\s\$isSae\s*=\s*class_exists\(\'SaeObject\'\);/', ),
            'wrap' => array('Wrap storage path with SAE wrapper prefix.', 'patchFile', $start, '/\s\$app->bindInstallPaths\(require __DIR__\.\'\/paths\.php\'\);/s',
                $statements['bind'], false, 'wrap storage', null, ),
            'log' => array('Add SaeDebugHandler for MonoLog.', 'patchFile', $global, '/\srequire app_path\(\)\.\'\/filters\.php\';/s',
                $statements['handler'], false, 'SaeDebugHandler', null, ),
        );

        parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
    {
        if ($this->patch($this->patches)) {
            $this->info(' - THE END.');
        } else {
            $this->info('');
            $this->info('Seems you have already patched SAE before,');
            $this->info('You can use "php artisan sae -o item-name" to overwrite ignored item(s), ');
            $this->info('See detailed help message using "php artisan sae -h".');
        }
    }

    private function patch($patches)
    {
        $nonPatched = true;

        $overwrite = $this->option('overwrite');
        $verbose = $this->option('verbose');
        foreach ($patches as $key => $patch) {
            if(empty($overwrite) or strcasecmp($overwrite, 'all')==0 or strcasecmp($overwrite, $key)==0) {
                $method = $patch[1];
                $params = array_slice($patch, 2);
                $params = array_merge($params, array(
                    $key,
                    !empty($overwrite) and (strcasecmp($overwrite, 'all') or strcasecmp($overwrite, $key)),
                    !empty($verbose),
                ));
                $patched = call_user_func_array(array($this, $method), $params);
                $nonPatched = $nonPatched && $patched;
            }
        }
        return $nonPatched;
    }

    private function addFile()
    {
        //Arguments
        list($path, $content, $key, $overwrite, $verbose) = func_get_args();
        //$undo = $this->option('undo');

        if(!$overwrite and file_exists($path)) {
            $this->info(" - [$key] \tIgnored. File '$path' already exists.");
            return false;
        }
        $this->backupFile($path);
        if(file_put_contents($path, $content) !== false) {
            $this->info("   [$key] \tSuccessfully added file '$path'.");
        } else {
            throw new \Exception(" Failed to add file '$path'.");
        }
        return true;
    }

    private function addFolder()
    {
        //Arguments
        list($folder, $key, $overwrite, $verbose) = func_get_args();
        //$undo = $this->option('undo');

        if(!$overwrite and file_exists($folder)) {
            $this->info(" - [$key] \tIgnored. Folder '$folder' already exists.");
        }

        if (!file_exists($folder)) {
            if (mkdir($folder)) {
                $this->info("   [$key] \tSuccessfully created folder '$folder'");
                return true;
            } else {
                throw new \Exception(" Failed to create folder '$folder'");
            }
        }
        if($overwrite) $this->info(" - [$key] \tSuccessfully created folder '$folder'");
        return true;
    }

    private function patchFile()
    {
        //Arguments
        list($path, $targetPattern, $patch, $commentTarget, $description, $kernalPattern, $key, $overwrite, $verbose) = func_get_args();
        //$undo = $this->option('undo');

        if(!file_exists($path)) {
            file_put_contents($path, $patch);
            $this->info("   [$key] \tSuccessfully added '$description' for sae".($verbose?" at file '$path'.":'.'));
            return true;
        }

        $content = file_get_contents($path);
        if(!$overwrite and (empty($kernalPattern) ? strpos($content, $patch) : preg_match($kernalPattern, $content))) {
            $this->info(" - [$key] \tIgnored. Patch '$description' for sae ".($verbose?"at file '$path' ":'')."already exists.");
            return false;
        }
        $this->backupFile($path);

        if(($output=$this->insertString(
                $targetPattern, $content, $patch, $commentTarget)
            ) !== false) {
            file_put_contents($path, $output);
            $this->info("   [$key] \tSuccessfully patched '$description' for sae".($verbose?" at file '$path'.":'.'));
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
            $this->info(" - \t\tSuccessfully backed up '$path'");
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
        $overwrite = <<<OVERWRITE
Patch laravel4 even it has been patched before.\n
Option value:\n
OVERWRITE;
        foreach ($this->patches as $key => $params) {
            $overwrite .= $key."\t".$params[0]."\n";
        }
        $overwrite .= "all \toverwrite all above.\n";
        $overwrite .= "\nExample1: php artisan sae -o db";
        $overwrite .= "\nExample2: php artisan sae -o all\n";

        return array(
            array('--overwrite', '-o', InputOption::VALUE_OPTIONAL, $overwrite),
            //array('--undo', '-u', InputOption::VALUE_NONE, 'Undo all patch done by SaePatch.'),
		);
	}

}
