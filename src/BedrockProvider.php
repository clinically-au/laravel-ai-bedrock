<?php

namespace Clinically\LaravelAiBedrock;

use Illuminate\Support\Arr;
use Laravel\Ai\Contracts\Providers\EmbeddingProvider;
use Laravel\Ai\Contracts\Providers\ImageProvider;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Providers\Concerns;
use Laravel\Ai\Providers\Provider;

class BedrockProvider extends Provider implements EmbeddingProvider, ImageProvider, TextProvider
{
    use Concerns\GeneratesEmbeddings;
    use Concerns\GeneratesImages;
    use Concerns\GeneratesText;
    use Concerns\HasEmbeddingGateway;
    use Concerns\HasImageGateway;
    use Concerns\HasTextGateway;
    use Concerns\StreamsText;

    public function providerCredentials(): array
    {
        return [
            'key' => $this->config['access_key'] ?? null,
        ];
    }

    public function additionalConfiguration(): array
    {
        return array_filter([
            'api_secret' => $this->config['secret_key'] ?? null,
            'session_token' => $this->config['session_token'] ?? null,
            'region' => $this->config['region'] ?? 'us-east-1',
            'use_default_credential_provider' => $this->useDefaultCredentialProvider(),
        ]);
    }

    public function defaultTextModel(): string
    {
        return $this->config['models']['text']['default'] ?? 'anthropic.claude-sonnet-4-5-20250929-v1:0';
    }

    public function cheapestTextModel(): string
    {
        return $this->config['models']['text']['cheapest'] ?? 'anthropic.claude-haiku-4-5-20251001-v1:0';
    }

    public function smartestTextModel(): string
    {
        return $this->config['models']['text']['smartest'] ?? 'anthropic.claude-opus-4-6-v1:0';
    }

    public function defaultEmbeddingsModel(): string
    {
        return $this->config['models']['embeddings']['default'] ?? 'amazon.titan-embed-text-v2:0';
    }

    public function defaultEmbeddingsDimensions(): int
    {
        return $this->config['models']['embeddings']['dimensions'] ?? 1024;
    }

    public function defaultImageModel(): string
    {
        return $this->config['models']['image']['default'] ?? 'amazon.titan-image-generator-v2:0';
    }

    public function defaultImageOptions(?string $size = null, $quality = null): array
    {
        return $this->imageOptionsFor($this->defaultImageModel(), $size, $quality);
    }

    public function imageOptionsFor(string $model, ?string $size = null, ?string $quality = null): array
    {
        $options = $this->config['models']['image']['options'] ?? [];

        return match (true) {
            str_contains($model, 'amazon.titan-image') => array_filter([
                ...Arr::only($options, [
                    'taskType',
                    'negativeText',
                    'numberOfImages',
                    'cfgScale',
                    'seed',
                ]),
                ...$this->titanImageSizeOptions($size),
                'quality' => $this->normalizeTitanQuality($quality) ?? data_get($options, 'quality'),
            ], fn (mixed $value): bool => $value !== null),
            str_contains($model, 'stability.') => array_filter([
                ...Arr::only($options, [
                    'negative_prompt',
                    'output_format',
                    'seed',
                    'cfg_scale',
                    'steps',
                    'style_preset',
                ]),
                'aspect_ratio' => match ($size) {
                    '1:1', '2:3', '3:2' => $size,
                    default => data_get($options, 'aspect_ratio'),
                },
            ], fn (mixed $value): bool => $value !== null),
            default => [],
        };
    }

    public function defaultMaxTokens(): int
    {
        return $this->config['max_tokens'] ?? 16_384;
    }

    protected function useDefaultCredentialProvider(): bool
    {
        return empty($this->config['access_key']) && empty($this->config['secret_key']);
    }

    protected function titanImageSizeOptions(?string $size): array
    {
        return match ($size) {
            '1:1' => ['height' => 1024, 'width' => 1024],
            '2:3' => ['height' => 1536, 'width' => 1024],
            '3:2' => ['height' => 1024, 'width' => 1536],
            default => Arr::only($this->config['models']['image']['options'] ?? [], ['height', 'width']),
        };
    }

    protected function normalizeTitanQuality(?string $quality): ?string
    {
        return match ($quality) {
            'high' => 'premium',
            'low', 'medium' => 'standard',
            default => null,
        };
    }
}
