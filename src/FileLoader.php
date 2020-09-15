<?php
namespace Stepkim\Translate;

class FileLoader
{
    protected $path;

    public function __construct($path = '')
    {
        if ($path == '') {
            $path = dirname(dirname(dirname(dirname(__DIR__)))) . '/lang';
        }
        $this->path = $path;
    }

    public function load($locale, $group, $namespace = null)
    {
        if (is_null($namespace) || $namespace === '*') {
            return $this->loadPath($this->path, $locale, $group);
        }
    }

    public function loadPath($path, $locale, $group)
    {
        $fullPath = "{$path}/{$locale}/{$group}.php";

        if (!file_exists($fullPath)) {
            return [];
        }

        if (!is_file($fullPath)) {
            return [];
        }

        return @require($fullPath);
    }
}