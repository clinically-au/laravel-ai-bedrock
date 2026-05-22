# Laravel AI Bedrock Provider — DEPRECATED

> **This package is deprecated and no longer maintained.**
>
> AWS Bedrock support is now built into [`laravel/ai`](https://github.com/laravel/ai) natively (since [v0.6.3](https://github.com/laravel/ai/releases), April 2026, via [PR #270](https://github.com/laravel/ai/pull/270)). The native provider covers everything this package bridged — streaming, tool calling, embeddings (Titan + Cohere), and image generation (Nova Canvas) — built directly on the AWS SDK without a Prism dependency.
>
> **Use the native provider in `laravel/ai` ^0.6.3 (or newer) instead.** This repository is now archived.

## Migration guide

### 1. Update `composer.json`

```diff
 "require": {
-    "clinically/laravel-ai-bedrock": "^0.1",
-    "laravel/ai": "^0.3"
+    "laravel/ai": "^0.7"
 }
```

Then `composer update`.

### 2. Update `config/ai.php`

The native provider uses slightly different credential keys. Rename `access_key` → `access_key_id` and `secret_key` → `secret_access_key`:

```diff
 'providers' => [
     'bedrock' => [
         'driver' => 'bedrock',
-        'access_key' => env('AWS_ACCESS_KEY_ID'),
-        'secret_key' => env('AWS_SECRET_ACCESS_KEY'),
+        'access_key_id' => env('AWS_ACCESS_KEY_ID'),
+        'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
         'session_token' => env('AWS_SESSION_TOKEN'),
         'region' => env('AWS_BEDROCK_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
         'models' => [
             'text' => [
                 'default' => env('AWS_BEDROCK_TEXT_MODEL', 'us.anthropic.claude-sonnet-4-5-20250929-v1:0'),
                 'cheapest' => env('AWS_BEDROCK_CHEAPEST_MODEL', 'us.anthropic.claude-haiku-4-5-20251001-v1:0'),
                 'smartest' => env('AWS_BEDROCK_SMARTEST_MODEL', 'us.anthropic.claude-opus-4-6-v1'),
             ],
             'embeddings' => [
                 'default' => env('AWS_BEDROCK_EMBEDDINGS_MODEL', 'amazon.titan-embed-text-v2:0'),
                 'dimensions' => env('AWS_BEDROCK_EMBEDDINGS_DIMENSIONS', 1024),
             ],
             'image' => [
                 'default' => env('AWS_BEDROCK_IMAGE_MODEL', 'amazon.nova-canvas-v1:0'),
             ],
         ],
     ],
 ],
```

Note: the native provider defaults to `us.`-prefixed cross-region inference profile IDs for text models. Adjust to your preferred region/profile.

### 3. Remove the service provider registration

The native provider is wired automatically by `laravel/ai`. Nothing extra to register.

### 4. Application code

No changes needed. `Ai::textProvider('bedrock')`, `Ai::embeddingProvider('bedrock')`, and the `#[Agent(provider: 'bedrock')]` attribute all continue to work the same way against the native provider.

## Original purpose (historical)

This package existed because the official `laravel/ai` SDK did not include a Bedrock provider, and the upstream `prism-php/bedrock` package was missing streaming support. It bridged the two via [`clinically/prism-bedrock`](https://github.com/clinically-au/prism-bedrock) (also deprecated/archived).

All of these gaps are now closed in `laravel/ai` itself.

## License

MIT License. See [LICENSE](LICENSE) for details.
