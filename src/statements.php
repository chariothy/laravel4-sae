<?php
/**
 * Created by PhpStorm.
 * User: Henry
 * Date: 2014-10-16
 * Time: 16:04
 */
namespace Chariothy;

return array(
    'handler' => <<<'HANDLER'
/*
|--------------------------------------------------------------------------
| SaePatch - add SaeDebugHandler for MonoLog
|--------------------------------------------------------------------------
*/
if(App::environment('sae')) {
    Log::getMonoLog()->pushHandler($handler = new Chariothy\SaeDebugHandler());
}
/*
|--------------------------------------------------------------------------
| End of SaePatch
|--------------------------------------------------------------------------
*/
HANDLER
    , 'yaml' => <<<'YAML'
handle:
- directoryindex: index.sae.php
- rewrite:  if ( !is_dir() && !is_file() && path ~ "^(.*)$" ) goto "index.sae.php/$1"
YAML
    , 'db' => <<<'DB_CONFIG'
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
DB_CONFIG
    , 'app' => <<<'APP_CONFIG'
<?php


return array(

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

    'sae' => array(
        'wrapper' => 'saekv://',

    /*
	|--------------------------------------------------------------------------
	| SAE Storage Domain
	|--------------------------------------------------------------------------
	| User-defined string while you open Storage service at SAE control panel.
	*/

        'domain' => 'example',
    /*
	|--------------------------------------------------------------------------
	| SAE static file location
	|--------------------------------------------------------------------------
	|
	| Sae's code capacity has limit up to 100MB. So it's a good idea to put static
	| files such as images, videos on Sae storage.
	| This setting will work when mark {{SAE::style}} , {{SAE::script}} , {{SAE::image}} , {{SAE::asset}}
	| is used in blade template.
	|
	| Supported:
    |	        "storage"       (put file on Sae storage),
    |           "code"          (put file under root/public/ such as local environment),
    |
    | Url map example ("root/public/images/sample.png"): {{SAE::image('images/sample.png'}}
    |       "code":        'appname.sinaapp.com/public/images/sample.png'
    |      "storage":      'appname-domain.stor.sinaapp.com/images/sample.png'
	|
	*/

        'style'     => 'code',
        'script'    => 'code',
        'image'     => 'storage',
    ),
);
APP_CONFIG
    , 'env' => <<<'NEW_ENV'
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
NEW_ENV
    , 'bind' => <<<'BIND'
/*
|--------------------------------------------------------------------------
| SaePatch - wrap storage path with SAE wrapper
|--------------------------------------------------------------------------
*/
if($app->environment('sae')) {
    $config = require $app['path'].'/config/sae/app.php';
    $app->instance("path.storage", $config['sae']['wrapper'].$app['path.storage']);
}
/*
|--------------------------------------------------------------------------
| End of SaePatch
|--------------------------------------------------------------------------
*/
BIND
    , 'index' => <<<'INDEX'
<?php
/*
|--------------------------------------------------------------------------
| SaePatch - adapt for SAE rewrite rule
|--------------------------------------------------------------------------
*/
ini_set('display_errors',0);
require __DIR__.'/public/index.php';
/*
|--------------------------------------------------------------------------
| End of SaePatch
|--------------------------------------------------------------------------
*/
INDEX
);