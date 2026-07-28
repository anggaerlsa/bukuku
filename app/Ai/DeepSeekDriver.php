<?php

namespace App\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * DeepSeek, spoken in the OpenAI request format.
 *
 * There is no SDK here on purpose: the whole surface this app uses is two
 * endpoints, and Laravel's HTTP client already handles timeouts and testing
 * fakes. A dependency would buy nothing and would have to be trusted with keys.
 */
class DeepSeekDriver implements AiProvider
{
    public function __construct(private readonly string $provider = 'deepseek') {}

    public function chat(string $apiKey, string $model, array $messages): AiReply
    {
        $response = $this->send($apiKey, 'post', '/chat/completions', [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => (int) config('ai.max_output_tokens'),
            'stream' => false,
        ]);

        $content = trim((string) data_get($response->json(), 'choices.0.message.content', ''));

        if ($content === '') {
            throw AiException::empty();
        }

        return new AiReply(
            content: $content,
            model: (string) data_get($response->json(), 'model', $model),
            tokensIn: $this->intOrNull(data_get($response->json(), 'usage.prompt_tokens')),
            tokensOut: $this->intOrNull(data_get($response->json(), 'usage.completion_tokens')),
            // The cache hit count is the number worth surfacing: it is the
            // difference between a request that costs cents and one that
            // costs a fraction of one.
            tokensCached: $this->intOrNull(data_get($response->json(), 'usage.prompt_cache_hit_tokens')),
        );
    }

    /**
     * Listing the models is metadata, not inference — it proves the key is
     * accepted without putting a single token on the author's bill.
     */
    public function verify(string $apiKey): void
    {
        $this->send($apiKey, 'get', '/models');
    }

    private function send(string $apiKey, string $method, string $path, array $payload = []): Response
    {
        $base = rtrim((string) config("ai.providers.{$this->provider}.base_url"), '/');

        try {
            $request = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout((int) config('ai.timeout'))
                // One retry only, and never on a rejected key or a spent
                // balance — repeating those just burns the author's rate limit.
                ->retry(2, 500, fn ($e) => $e instanceof ConnectionException, throw: false);

            $response = $method === 'get'
                ? $request->get($base . $path)
                : $request->post($base . $path, $payload);
        } catch (ConnectionException) {
            throw AiException::unreachable();
        }

        if ($response->failed()) {
            throw AiException::fromResponse($response);
        }

        return $response;
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
