<?php

namespace Clinically\LaravelAiBedrock;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Providers\ImageProvider;
use Laravel\Ai\Gateway\Prism\PrismGateway;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Providers\Provider;
use Laravel\Ai\Responses\Data\GeneratedImage;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\ImageResponse;
use Prism\Prism\Exceptions\PrismException as PrismVendorException;
use Prism\Prism\Facades\Prism;

class BedrockPrismGateway extends PrismGateway
{
    protected function configure($prism, Provider $provider, string $model): mixed
    {
        if ($provider->driver() === 'bedrock') {
            return $prism->using(
                'bedrock',
                $model,
                array_filter([
                    ...$provider->additionalConfiguration(),
                    'api_key' => $provider->providerCredentials()['key'],
                ]),
            );
        }

        return parent::configure($prism, $provider, $model);
    }

    protected function withProviderOptions($request, Provider $provider, ?array $schema, ?TextGenerationOptions $options)
    {
        if ($provider instanceof BedrockProvider) {
            return $request
                ->withProviderOptions(array_filter([
                    'use_tool_calling' => $schema ? true : null,
                ], fn (mixed $value): bool => $value !== null))
                ->withMaxTokens($options?->maxTokens ?? $provider->defaultMaxTokens());
        }

        return parent::withProviderOptions($request, $provider, $schema, $options);
    }

    public function generateImage(
        ImageProvider $provider,
        string $model,
        string $prompt,
        array $attachments = [],
        ?string $size = null,
        ?string $quality = null,
        ?int $timeout = null,
    ): ImageResponse {
        if (! $provider instanceof BedrockProvider) {
            return parent::generateImage($provider, $model, $prompt, $attachments, $size, $quality, $timeout);
        }

        if ($attachments !== []) {
            throw new InvalidArgumentException('Bedrock image generation does not support image attachments.');
        }

        try {
            $response = tap(
                Prism::image(),
                fn ($prism) => $this->configure($prism, $provider, $model)
            )
                ->withPrompt($prompt)
                ->withProviderOptions($provider->imageOptionsFor($model, $size, $quality))
                ->withClientOptions([
                    'timeout' => $timeout ?? 120,
                ])
                ->generate();
        } catch (PrismVendorException $e) {
            throw \Laravel\Ai\Gateway\Prism\PrismException::toAiException($e, $provider, $model);
        }

        return new ImageResponse(
            (new Collection($response->images))->map(function ($image) {
                return new GeneratedImage($image->base64, $image->mimeType);
            }),
            \Laravel\Ai\Gateway\Prism\PrismUsage::toLaravelUsage($response->usage),
            new Meta($provider->name(), $model),
        );
    }
}
