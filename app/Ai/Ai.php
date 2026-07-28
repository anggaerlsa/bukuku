<?php

namespace App\Ai;

/**
 * Entry point for the assistant: which providers exist, and how to talk to one.
 *
 * Deliberately a plain resolver rather than a container binding — there is one
 * provider, drivers hold no state, and a `new` in one place is easier to follow
 * than a service provider that exists to hide a match expression.
 */
class Ai
{
    public static function enabled(): bool
    {
        return (bool) config('ai.enabled');
    }

    public static function driver(?string $provider = null): AiProvider
    {
        $provider = $provider ?: (string) config('ai.default');

        return match ($provider) {
            'deepseek' => new DeepSeekDriver('deepseek'),
            default => throw new AiException("Penyedia AI \"{$provider}\" tidak dikenal."),
        };
    }

    /** @return array<string, string> provider key → label */
    public static function providers(): array
    {
        return collect((array) config('ai.providers'))
            ->map(fn (array $config, string $key) => (string) ($config['label'] ?? ucfirst($key)))
            ->all();
    }

    /** @return array<string, string> model id → label */
    public static function models(?string $provider = null): array
    {
        $provider = $provider ?: (string) config('ai.default');

        return (array) config("ai.providers.{$provider}.models", []);
    }

    public static function defaultModel(?string $provider = null): string
    {
        $provider = $provider ?: (string) config('ai.default');

        return (string) config("ai.providers.{$provider}.default_model", array_key_first(self::models($provider)) ?? '');
    }

    /** Rough token count for a piece of text — no tokenizer, just a ratio. */
    public static function estimateTokens(string $text): int
    {
        $ratio = (float) config('ai.chars_per_token', 3.0);

        return (int) ceil(mb_strlen($text) / max($ratio, 1.0));
    }
}
