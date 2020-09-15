<?php
namespace Stepkim\Translate;

interface TranslatorInterface
{
    public function trans($key, array $replace, $locale = null);
}