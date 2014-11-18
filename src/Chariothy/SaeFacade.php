<?php
/**
 * Created by PhpStorm.
 * User: Henry
 * Date: 2014-11-17
 * Time: 15:53
 */

namespace Chariothy;


use Illuminate\Support\Facades\Facade;

class SaeFacade extends Facade {
    protected static function getFacadeAccessor() { return 'sae.asset'; }
} 