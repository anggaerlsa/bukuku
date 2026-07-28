<?php

namespace App\Ai;

/**
 * One answer from a provider, reduced to what this app stores and shows.
 */
class AiReply
{
    public function __construct(
        public readonly string $content,
        public readonly string $model,
        public readonly ?int $tokensIn = null,
        public readonly ?int $tokensOut = null,
        public readonly ?int $tokensCached = null,
    ) {}
}
