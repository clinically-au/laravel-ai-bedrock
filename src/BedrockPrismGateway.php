<?php

namespace Clinically\LaravelAiBedrock;

use Laravel\Ai\Gateway\Prism\PrismGateway;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Providers\Provider;

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
            $agentProviderOptions = $options?->providerOptions($provider->driver());

            return $request
                ->withProviderOptions(array_filter([
                    'use_tool_calling' => $schema ? true : null,
                    ...($agentProviderOptions ?? []),
                ], fn (mixed $value): bool => $value !== null))
                ->withMaxTokens($options?->maxTokens ?? $provider->defaultMaxTokens());
        }

        return parent::withProviderOptions($request, $provider, $schema, $options);
    }
}
