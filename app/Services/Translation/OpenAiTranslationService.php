<?php

namespace App\Services\Translation;

use App\Models\Pim\Cache\PimCacheTranslation;
use App\Services\Pim\PimGenerateIdService;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAiTranslationService extends TranslationAPI
{
    protected array $config;

    protected string $provider = 'openai';

    public function __construct()
    {
        $this->config = config('openai.translation');
    }

    public function translate(string $class, string $text, string $languageFrom, string $languageTo, ?string $instructionKey = null): string
    {
        // if text empty, return input text
        $text = trim($text);
        if (empty($text)) {
            return $text;
        }

        $id = PimGenerateIdService::getCacheTranslationId($this->provider, $class, $languageFrom, $languageTo, $text);
        $cachedTranslation = $this->getTranslationFromCache($id);
        if ($cachedTranslation !== null) {
            return $cachedTranslation;
        }

        $prompt = $this->getPrompt($text, $languageFrom, $languageTo, $instructionKey);
        $translation = $this->request($prompt, $text);
        $isUnclear = $this->isUnclear($translation);
        if ($isUnclear) {
            $this->storeResponse($id, $class, $languageFrom, $languageTo, $text, $text, false);

            return $text;
        } else {
            $this->storeResponse($id, $class, $languageFrom, $languageTo, $text, $translation, true);

            return $translation;
        }
    }

    protected function storeResponse(string $id, string $class, string $fromLang, string $toLang, string $input, string $response, bool $successful): void
    {
        PimCacheTranslation::upsert([
            'id' => $id,
            'class' => $class,
            'provider' => $this->provider,
            'from_lang' => $fromLang,
            'to_lang' => $toLang,
            'input' => $input,
            'translation' => $response,
            'successful' => $successful,
        ], ['id']);
    }

    protected function request(string $prompt, string $text): string
    {
        $messages = [
            [
                'role' => 'system',
                'content' => $prompt,
            ],
            [
                'role' => 'user',
                'content' => e($text),
            ],
        ];

        $result = OpenAI::chat()->create([
            'model' => $this->config['model'],
            'messages' => $messages,
            'temperature' => $this->config['temperature'],
        ]);

        return $result->choices[0]->message->content;
    }

    protected function getTranslationFromCache(string $id): ?string
    {
        return PimCacheTranslation::query()
            ->where('id', $id)
            // ->where('successful', true)
            ->value('translation');
    }

    protected function getPrompt(string $text, string $languageFrom, string $languageTo, ?string $instructionKey = null): string
    {
        $instruction = $this->getInstruction($text, $instructionKey);

        return sprintf($instruction, $languageFrom, $languageTo);
    }

    protected function isUnclear(string $response): bool
    {
        return $response === $this->config['unclearTerm'];
    }

    protected function getInstruction(string $text, ?string $instructionKey = null)
    {
        if ($instructionKey) {
            $instruction = $this->config[$instructionKey];
        } else {
            $longText = str_word_count($text) > 4;
            $instruction = $longText ? $this->config['instructionProduct'] : $this->config['instructionGeneral'];
        }

        return $instruction;
    }
}
