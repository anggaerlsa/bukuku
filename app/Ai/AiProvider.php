<?php

namespace App\Ai;

/**
 * What the app needs from a language-model provider — nothing more, so a second
 * provider can be added without the chat controller learning about it.
 *
 * The API key is passed in on every call rather than injected once: keys belong
 * to authors, not to the installation, so a driver must never hold one.
 */
interface AiProvider
{
    /**
     * Send a conversation and get one reply.
     *
     * @param  list<array{role: string, content: string}>  $messages
     *
     * @throws AiException when the provider refuses or cannot be reached
     */
    public function chat(string $apiKey, string $model, array $messages): AiReply;

    /**
     * Prove a key works, without spending tokens on it.
     *
     * @throws AiException when the key is rejected
     */
    public function verify(string $apiKey): void;
}
