<?php
/**
 * Created by PhpStorm.
 * User: Henry
 * Date: 2014-11-17
 * Time: 14:46
 */
namespace Chariothy;

use Illuminate\Support\ServiceProvider;

class SaeServiceProvider extends ServiceProvider {
    const VERSION = '1.1.0';
    protected $defer = true;

    public function register() {
        $this->registerAssert();
        $this->registerPatch();
    }

    protected function registerAssert() {
        $this->app->bindShared('sae.asset', function($app)
        {
            return new SaeAsset($app);
        });
    }

    protected function registerPatch() {
        $this->app->bindShared('sae.patch', function($app)
        {
            return new SaePatch();
        });
        $this->commands('sae.patch');
    }

    public function provides() {
        return array('sae.patch', 'sae.asset', );
    }
}