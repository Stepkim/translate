<?php

namespace Stepkim\Translate;

use Stepkim\Translate\Tools\Arr;
use Stepkim\Translate\Tools\Str;

class Translator implements TranslatorInterface
{
    protected $loader;

    protected $locale;

    protected $fallback;

    protected $loaded = [];

    protected $parsed = [];

    private static $instance;

    private function __construct(FileLoader $loader, $locale)
    {
        $this->loader = $loader;
        $this->locale = $locale;
    }

    public static function getInstance($locale, $fallback)
    {
        if (!self::$instance) {
            $loader = new FileLoader();
            $translator = new Translator($loader, $locale);
            $translator->setFallback($fallback);
            self::$instance = $translator;
        }

        return self::$instance;
    }

    public function load($namespace, $group, $locale)
    {
        if ($this->isLoaded($namespace, $group, $locale)) {
            return;
        }

        $lines = $this->loader->load($locale, $group, $namespace);

        $this->loaded[$namespace][$group][$locale] = $lines;
    }

    public function getLoader()
    {
        return $this->loader;
    }

    public function locale()
    {
        return $this->getLocale();
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    protected function isLoaded($namespace, $group, $locale)
    {
        return isset($this->loaded[$namespace][$group][$locale]);
    }

    public function hasForLocale($key, $locale = null)
    {
        return $this->has($key, $locale, false);
    }

    public function has($key, $locale = null, $fallback = true)
    {
        return $this->get($key, [], $locale, $fallback) !== $key;
    }

    public function trans($key, array $replace = [], $locale = null)
    {
        return $this->get($key, $replace, $locale);
    }

    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        list($namespace, $group, $item) = $this->parseKey($key);

        $locales = $fallback ? $this->localeArray($locale)
            : [$locale ?: $this->locale];

        foreach ($locales as $locale) {
            if (!is_null($line = $this->getLine(
                $namespace, $group, $locale, $item, $replace
            ))) {
                break;
            }
        }

        return $line ?? $key;
    }

    protected function localeArray($locale)
    {
        return array_filter([$locale ?: $this->locale, $this->fallback]);
    }

    protected function parseKeyAndSetParsed($key)
    {
        if (isset($this->parsed[$key])) {
            return $this->parsed[$key];
        }

        if (strpos($key, '::') === false) {
            $segments = explode('.', $key);
            $parsed = $this->parseBasicSegments($segments);
        } else {
            $parsed = $this->parseNamespacedSegments($key);
        }

        return $this->parsed[$key] = $parsed;
    }

    public function parseKey($key)
    {
        $segments = $this->parseKeyAndSetParsed($key);

        if (is_null($segments[0])) {
            $segments[0] = '*';
        }

        return $segments;
    }

    protected function parseBasicSegments($segments)
    {
        $group = $segments[0];

        $item = count($segments) === 1
            ? null
            : implode('.', array_slice($segments, 1));

        return [null, $group, $item];
    }

    protected function parseNamespacedSegments($key)
    {
        list($namespace, $item) = explode('::', $key);

        $itemSegments = explode('.', $item);

        $groupAndItem = array_slice(
            $this->parseBasicSegments($itemSegments), 1
        );

        return array_merge([$namespace], $groupAndItem);
    }

    public function setParsedKey($key, $parsed)
    {
        $this->parsed[$key] = $parsed;
    }

    protected function getLine($namespace, $group, $locale, $item, array $replace)
    {
        $this->load($namespace, $group, $locale);

        $line = Arr::get($this->loaded[$namespace][$group][$locale], $item);

        if (is_string($line)) {
            return $this->makeReplacements($line, $replace);
        } elseif (is_Array($line) && count($line) > 0) {
            foreach ($line as $key => $value) {
                $line[$key] = $this->makeReplacements($value, $replace);
            }
            return $line;
        }
    }

    protected function makeReplacements($line, array $replace)
    {
        if (empty($replace)) {
            return $line;
        }

        foreach ($replace as $key => $value) {
            $line = str_replace(
                [':' . $key, ':' . Str::upper($key), ':' . Str::ucfirst($key)],
                [$value, Str::upper($value), Str::ucfirst($value)],
                $line
            );
        }

        return $line;
    }

    public function getFallback()
    {
        return $this->fallback;
    }

    public function setFallback($fallback)
    {
        $this->fallback = $fallback;
    }
}