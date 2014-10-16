<?php
/**
 * Created by PhpStorm.
 * User: Henry
 * Date: 2014-10-16
 * Time: 12:12
 */

namespace Chariothy;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class SaeDebugHandler extends AbstractProcessingHandler
{
    /**
     * {@inheritdoc}
     */

    public function __construct($level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(array $record)
    {
        sae_debug('<'.$record['level'].'> '.$record['formatted']);
    }
}