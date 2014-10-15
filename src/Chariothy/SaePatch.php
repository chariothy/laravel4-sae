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
        if(!$this->option('force')) {
            list($nonPatched, $output) = $this->checkSaePatch();
            if(!$nonPatched) {
                $this->error('Seems you have already patched SAE:');
                $n = 1;
                foreach ($output as $description) {
                    $this->info("\t$n. ".$description);
                    $n++;
                }
                $this->error('You must use "php artisan sae -f" to enforce it.');
                $this->error('See help message using "php artisan sae -h"');
                return;
            }
        }
        $this->addSaeEnvAndBindStoragePath();
        $this->addSaeConfig();
        $this->addSaeRule();
        $this->addFavIcon();
        $this->info(' THE END.');
    }

    private function checkSaePatch()
    {
        $output = array();
        $nonPatched = true;

        list($exists, $description) = $this->checkFileExists(SaePatch::CONFIG_FOLDER);
        $nonPatched = $nonPatched && !$exists;
        $output[] = $description;

        list($exists, $description) = $this->checkFileExists(SaePatch::CONFIG_FOLDER.'/database.php');
        $nonPatched = $nonPatched && !$exists;
        $output[] = $description;

        list($exists, $description) = $this->checkFileExists(SaePatch::CONFIG_FOLDER.'/app.php');
        $nonPatched = $nonPatched && !$exists;
        $output[] = $description;

        list($exists, $description) = $this->checkFileExists('config.yaml');
        $nonPatched = $nonPatched && !$exists;
        $output[] = $description;

        list($exists, $description) = $this->checkFileExists('favicon.ico');
        $nonPatched = $nonPatched && !$exists;
        $output[] = $description;

        list($exists, $description) = $this->checkStatementExists('bootstrap/start.php', '/\$isSae\s*=\s*class_exists\(\'SaeObject\'\);/', 'Add closure for detectEnvironment().');
        $nonPatched = $nonPatched && !$exists;
        $output[] = $description;

        list($exists, $description) = $this->checkStatementExists('bootstrap/start.php', '/Config::get\(\'app\.wrapper\'\)/', 'Wrap storage path with SAE wrapper.');
        $nonPatched = $nonPatched && !$exists;
        $output[] = $description;

        return array($nonPatched, $output);
    }

    private function checkStatementExists($path, $pattern, $short)
    {
        $exists = false;

        $description = $short;
        $content = file_get_contents($path);
        if(preg_match($pattern, $content)) {
            $exists = true;
            $description .= '(Done)';
        } else {
            $description .= '(UnDone)';
        }
        return array($exists, $description);
    }

    private function checkFileExists($path)
    {
        $exists = false;

        $description = 'Add "'.$path.'".';
        if(file_exists($path)) {
            $exists = true;
            $description .= '(Done)';
        } else {
            $description .= '(UnDone)';
        }
        return array($exists, $description);
    }

    private function addSaeRule()
    {
        $this->info(str_pad(' --------------------- Add SAE Rule ', 60, '-'));
        $yaml = <<<'YAML'
name: laravel4-sae
version: 1
handle:
- rewrite:  if ( !is_dir() && !is_file() && path ~ "^(.*)$" ) goto "public/index.php/$1"
YAML;
        $yamlPath = 'config.yaml';
        if(file_exists($yamlPath)) {
            $this->backupFile($yamlPath, '.yaml');
        }
        if(file_put_contents($yamlPath, $yaml)) {
            $this->info(' Successfully added rule for SAE (in config.yaml).');
        } else {
            $this->error(' Failed to added rule for SAE.');
        }
    }

    private  function addFavIcon()
    {
        $this->info(str_pad(' --------------------- Add SAE Favicon ', 60, '-'));
        $iconPath = 'favicon.ico';
        if(file_exists($iconPath)) {
            $this->backupFile($iconPath, '.ico');
        }
        if(file_put_contents($iconPath, '')!==false) {
            $this->info(' Successfully added favicon.ico for SAE.');
        } else {
            $this->error(' Failed to added favicon.ico for SAE.');
        }
    }

    private function get(&$var, $default=null) {
        return isset($var) ? $var : $default;
    }

    private function addSaeConfig()
    {
        $this->info(str_pad(' --------------------- Add SAE Configuration ', 60, '-'));
        $saeDbConfigPath = SaePatch::CONFIG_FOLDER.'/database.php';
        $saeAppConfigPath = SaePatch::CONFIG_FOLDER.'/app.php';

        if (!file_exists(SaePatch::CONFIG_FOLDER)) {
            if (mkdir(SaePatch::CONFIG_FOLDER)) {
                $this->info(' Successfully created folder "'.SaePatch::CONFIG_FOLDER.'"');
            } else {
                $this->error(' Failed to created folder "'.SaePatch::CONFIG_FOLDER.'"');
            }
        }
        $databaseConfig = <<<'DB_CONFIG'
<?php

    /*
	|--------------------------------------------------------------------------
	| Database Connections for SAE
	|--------------------------------------------------------------------------
	|
	| Here are each of the database connections setup for your application.
	| Of course, examples of configuring each database platform that is
	| supported by Laravel is shown below to make development simple.
	|
	|
	| All database work in Laravel is done through the PHP PDO facilities
	| so make sure you have the driver for your particular database of
	| choice installed on your machine before you begin development.
	|
	*/
return array(
    'connections' => array(

		'mysql' => array(
			'driver'    => 'mysql',
            'host'      => SAE_MYSQL_HOST_M,
            'port'      => SAE_MYSQL_PORT,
            'database'  => SAE_MYSQL_DB,
            'username'  => SAE_MYSQL_USER,
            'password'  => SAE_MYSQL_PASS,
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
		),
	),
);
DB_CONFIG;
        if(file_exists($saeDbConfigPath)) {
            $this->backupFile($saeDbConfigPath);
        }
        if(file_put_contents($saeDbConfigPath, $databaseConfig)) {
            $this->info(' Successfully added database configuration for SAE.');
        } else {
            $this->error(' Failed to added database configuration for SAE.');
        }

        $appConfig = <<<'APP_CONFIG'
<?php

    /*
	|--------------------------------------------------------------------------
	| SAE Wrapper Prefix
	|--------------------------------------------------------------------------
	|
	| This prefix stand for SAE wrapper. Using this prefix, we can access storage,
	| memcached, kvdb of SAE by simply keeping 'drive' of cache, session 'file'.
	|
	| Supported:
    |	        "saekv://"          (recommended for string),
    |           "saemc://"          (fastest but most expensive),
    |           "saestor://domain"  (suitable for resource)
	|
	*/

return array(
    'wrapper' => 'saekv://',
);
APP_CONFIG;

        if(file_exists($saeAppConfigPath)) {
            $this->backupFile($saeAppConfigPath);
        }
        if(file_put_contents($saeAppConfigPath, $appConfig)) {
            $this->info(' Successfully added wrapper configuration for SAE.');
        } else {
            $this->error(' Failed to added wrapper configuration for SAE.');
        }
    }

    private function backupFile($path, $ext=SaePatch::PHP_EXT)
    {
        if (!file_exists($path)) return false;
        $basePath = dirname($path)=='.' ? basename($path, $ext) : dirname($path). '/' . basename($path, $ext);
        $n = 1;
        $backupPath = $basePath . '.bak' . $ext;
        while (file_exists($backupPath)) {
            $backupPath = $basePath . '.bak' . $n . $ext;
            $n++;
        }
        if(copy($path, $backupPath)) {
            $this->info(' Successfully backed up "' . $path);// . '" -> "' . $backupPath . '"');
        } else {
            $this->info(' Failed to back up "' . $path . '"');
        }
        return $backupPath;
    }

    private function insertString($pattern, $content, $insert, $commentMatched=true)
    {
        if(preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
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

    private function addSaeEnvAndBindStoragePath()
    {
        $startPath =  'bootstrap/start.php';
        $this->info(str_pad(' --------------------- Patch "'.$startPath.'" ', 60, '-'));

        $this->backupFile($startPath);
        $content = file_get_contents($startPath);
        $newEnv = <<<'NEW_ENV'
/*
|--------------------------------------------------------------------------
| SaePatch - add closure for detectEnvironment()
|--------------------------------------------------------------------------
*/
$env = $app->detectEnvironment(function(){

  // Set the booleans
  $isLocal      = gethostname()==gethostbyaddr('127.0.0.1');
  $isSae        = class_exists('SaeObject');
  $isTest       = strpos(__DIR__, 'var/www/your-domain.com/test/');

  // Set the environments
  $environment = "production";
  if ($isLocal)       $environment = "local";
  if ($isSae)         $environment = "sae";
  if ($isTest)        $environment = "test";

  // Return the appropriate environment
  return $environment;
});
/*
|--------------------------------------------------------------------------
| End of SaePatch
|--------------------------------------------------------------------------
*/
NEW_ENV;
        if(($output=$this->insertString(
                '/\s\$env = \$app->detectEnvironment\(array\((.+?)\)\);/s', $content, $newEnv)
            ) !== false) {
            $content = $output;
            $this->info(' Successfully patched detectEnvironment() for sae.');
        } elseif(($output=$this->insertString(
            '/\s\$env = \$app->detectEnvironment\(function\(\){(.+?)}\);/s', $content, $newEnv)
            ) !== false) {
            $content = $output;
            $this->info(' Successfully patched detectEnvironment() for sae.');
        } else {
            $this->error(' Failed to patched detectEnvironment() for sae.');
        }

        $bindStorage = <<<'BIND'

/*
|--------------------------------------------------------------------------
| SaePatch - wrap storage path with SAE wrapper
|--------------------------------------------------------------------------
*/
if(class_exists('SaeObject')) {
    $app->instance("path.storage", Config::get('app.wrapper').$app['path.storage']);
}
/*
|--------------------------------------------------------------------------
| End of SaePatch
|--------------------------------------------------------------------------
*/
BIND;

        if(($output=$this->insertString(
                '/require \$framework.\'\/Illuminate\/Foundation\/start.php\';/s', $content, $bindStorage, false)
            ) !== false) {
            file_put_contents($startPath, $output);
            $this->info(' Successfully bound storage path for sae.');
        } else {
            $this->error(' Failed to bind storage path for sae.');
        }
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
            array('--force', '-f', InputOption::VALUE_NONE, 'Patch laravel4 even it has been patched before.'),
		);
	}

}
