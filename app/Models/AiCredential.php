<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One author's API key for one provider.
 *
 * The key is encrypted at rest and never leaves this object except on its way
 * into an outgoing request. Everything the interface needs to show — which
 * provider, which model, the last four characters, whether it ever answered —
 * is readable without touching the ciphertext.
 */
class AiCredential extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'model',
    ];

    /**
     * `api_key` is deliberately absent from $fillable: it may only be set
     * through fillKey(), which keeps key_tail in step and clears the previous
     * verification. Mass assignment could set one without the other.
     */
    protected $hidden = [
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Store a new key. The tail is kept in the clear so the settings page can
     * render a recognisable fragment without a decryption, and the previous
     * verification is dropped because it said nothing about this key.
     */
    public function fillKey(string $plain): void
    {
        $plain = trim($plain);

        $this->api_key = $plain;
        $this->key_tail = static::tailOf($plain);
        $this->verified_at = null;
    }

    public static function tailOf(string $plain): string
    {
        return substr(trim($plain), -4) ?: '';
    }

    /** What the author sees in place of their key. */
    public function masked(): string
    {
        $prefix = (string) $this->providerConfig('key_prefix', '');

        return $prefix . '••••••••' . $this->key_tail;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function providerLabel(): string
    {
        return (string) $this->providerConfig('label', ucfirst((string) $this->provider));
    }

    /** @return array<string, string> */
    public function modelOptions(): array
    {
        return (array) $this->providerConfig('models', []);
    }

    public function modelLabel(): string
    {
        return $this->modelOptions()[$this->model] ?? (string) $this->model;
    }

    public function providerConfig(string $key, mixed $default = null): mixed
    {
        return config("ai.providers.{$this->provider}.{$key}", $default);
    }
}
