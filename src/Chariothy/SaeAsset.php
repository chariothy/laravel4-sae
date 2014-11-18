<?php
/**
 * Created by PhpStorm.
 * User: Henry
 * Date: 2014-11-17
 * Time: 14:17
 */

namespace Chariothy;


use Illuminate\Html\HtmlBuilder;
use Illuminate\Routing\UrlGenerator;

class SaeAsset extends HtmlBuilder {

    private $app = null;

    public function __construct($app = null)
    {
        $this->app = $app;
        parent::__construct($app['url']);
    }

    private function buildUrl($urlComponents) {
        $url = $urlComponents['scheme'] . '://';
        if(isset($urlComponents['user']) && isset($urlComponents['pass'])) {
            $url .= $urlComponents['user'] . ':' . $urlComponents['pass'] . '@';
        }
        $url .= $urlComponents['host'] . $urlComponents['path'];
        if(isset($urlComponents['query'])) {
            $url .= '?' . $urlComponents['query'];
        }
        if(isset($urlComponents['fragment'])) {
            $url .= '#' . $urlComponents['fragment'];
        }
        return $url;
    }

    private function patchUrl($url, $secure, $configName) {
        $src = $this->url->asset($url, $secure);
        $parsedSrc = parse_url($src);
        if($this->app['config']['app']['sae'][$configName] == 'code') {
            $parsedSrc['path'] = '/public' . $parsedSrc['path'];
        } elseif($this->app['config']['app']['sae'][$configName] == 'storage') {
            $hosts = explode('.', $parsedSrc['host']);
            $host = array_shift($hosts);
            if($host === $_SERVER["HTTP_APPVERSION"]) {
                $host = array_shift($hosts);
            }
            $host .= '-' . $this->app['config']['app']['sae']['domain'];
            $host .= '.stor.';
            $host .= implode('.', $hosts);
            $parsedSrc['host'] = $host;
        }
        return $this->buildUrl($parsedSrc);
    }

    public function script($url, $attributes = array(), $secure = null)
    {
        if($this->url->isValidUrl($url) or !$this->app->environment('sae')) {
            return parent::script($url, $attributes, $secure);
        } else {
            $attributes['src'] = $this->patchUrl($url, $secure, 'script');
            return '<script'.$this->attributes($attributes).'></script>'.PHP_EOL;
        }
    }

    public function image($url, $alt = null, $attributes = array(), $secure = null)
    {
        if($this->url->isValidUrl($url) or !$this->app->environment('sae')) {
            return parent::image($url, $alt, $attributes, $secure);
        } else {
            $attributes['alt'] = $alt;
            return '<img src="'.$this->patchUrl($url, $secure, 'image').'"'.$this->attributes($attributes).'>';
        }
    }

    public function style($url, $attributes = array(), $secure = null)
    {
        if($this->url->isValidUrl($url) or !$this->app->environment('sae')) {
            return parent::style($url, $attributes, $secure);
        } else {
            $defaults = array('media' => 'all', 'type' => 'text/css', 'rel' => 'stylesheet');

            $attributes = $attributes + $defaults;

            $attributes['href'] = $this->patchUrl($url, $secure, 'style');

            return '<link'.$this->attributes($attributes).'>'.PHP_EOL;
        }
    }
} 