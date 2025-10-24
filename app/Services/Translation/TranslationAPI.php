<?php

namespace App\Services\Translation;

abstract class TranslationAPI
{
    protected array $config;

    protected string $provider;

    /**
     * Translate text from one language to another.
     *
     * @param  string  $text  The text to translate.
     * @param  string  $languageFrom  The source language.
     * @param  string  $languageTo  The target language.
     * @return string The translated text.
     */
    abstract public function translate(string $class, string $text, string $languageFrom, string $languageTo): string;
}
