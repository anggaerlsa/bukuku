<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The writing assistant
    |--------------------------------------------------------------------------
    |
    | An author may point their own API key at their own novel and ask questions
    | about it. Nothing here holds a key: every request is made with the key the
    | signed-in author saved, so the installation never carries a shared secret
    | and never carries the bill.
    |
    | Off by default. A fresh checkout — and every test run — must be unable to
    | reach a provider by accident.
    |
    */

    'enabled' => env('AI_ENABLED', false),

    'default' => env('AI_PROVIDER', 'deepseek'),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Only DeepSeek for now. It speaks the OpenAI request format, so a second
    | provider is a base URL and a model list rather than another client.
    |
    | `context_tokens` is the provider's own input limit; the dossier builder
    | measures against `context_budget` below, which leaves room for the reply
    | and the conversation so far.
    |
    */

    'providers' => [
        'deepseek' => [
            'label' => 'DeepSeek',
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
            'context_tokens' => 1_000_000,
            'key_prefix' => 'sk-',
            'docs_url' => 'https://platform.deepseek.com/api_keys',
            'models' => [
                'deepseek-v4-flash' => 'DeepSeek V4 Flash — murah, cepat',
                'deepseek-v4-pro' => 'DeepSeek V4 Pro — penalaran lebih kuat',
            ],
            'default_model' => 'deepseek-v4-flash',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | How much of a novel goes into one request
    |--------------------------------------------------------------------------
    |
    | The dossier is built in a fixed order and sent whole, so the provider's
    | prompt cache recognises the prefix and every turn after the first is
    | charged at the cached rate. That only works while the dossier is stable —
    | do not append anything that changes per request.
    |
    | The budget is a ceiling on the assembled dossier, well under the model's
    | context so the reply and the chat history still fit. When a novel does not
    | fit, the manuscript is trimmed from the end and the page says where it was
    | cut. It is never trimmed silently.
    |
    */

    'context_budget' => (int) env('AI_CONTEXT_BUDGET', 600_000),

    /** Rough characters-per-token used to estimate size without a tokenizer. */
    'chars_per_token' => 3.0,

    /*
    |--------------------------------------------------------------------------
    | Reply shape
    |--------------------------------------------------------------------------
    |
    | A ceiling on the reply, not a target. The models will happily generate
    | hundreds of thousands of tokens, and the author pays for every one; a
    | question about their own lore does not need a novella back.
    |
    */

    'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 4000),

    /*
    |--------------------------------------------------------------------------
    | How much of the transcript travels with each question
    |--------------------------------------------------------------------------
    |
    | The dossier stays the same size all conversation; the transcript does not,
    | and every message in it is re-sent every turn. This caps how far back the
    | assistant remembers. The chat page says so when a thread grows past it.
    |
    */

    'history_messages' => (int) env('AI_HISTORY_MESSAGES', 30),

    'timeout' => (int) env('AI_TIMEOUT', 180),

];
