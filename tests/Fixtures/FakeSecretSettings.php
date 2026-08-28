<?php

declare(strict_types=1);

namespace HpWebDeveloper\LaravelEnvSettings\Tests\Fixtures;

use HpWebDeveloper\LaravelEnvSettings\Attributes\Sensitive;
use HpWebDeveloper\LaravelEnvSettings\EnvironmentSettings;

final class FakeSecretSettings extends EnvironmentSettings
{
    public function __construct(
        // Marked explicitly — the name matches none of the fallback fragments.
        #[Sensitive] public string $passphrase,
        #[Sensitive] public string $bearer,
        // Unmarked, but the legacy name heuristic still hides it.
        public string $api_key,
        // Contains "key", so the heuristic masks it even though it is public.
        public string $monkey_api_url,
        // Ordinary value, never masked.
        public string $region,
        // Marked but empty: masking would imply a value exists.
        #[Sensitive] public string $optional_token,
        // Numbers whose names contain a flagged word. v1.0.0 showed these
        // because the fallback only ever masked strings; that must hold.
        public int $max_tokens,
        public int $keyboard_rows,
        // Marked and non-string: the attribute masks whatever the type.
        #[Sensitive] public int $pin_code,
    ) {}

    public static function development(): static
    {
        return new self('dev-pass', 'dev-bearer', 'dev-key', 'https://dev.monkey.test', 'eu-west', '', 2000, 12, 1111);
    }

    public static function production(): static
    {
        return new self('live-pass', 'live-bearer', 'live-key', 'https://monkey.test', 'us-east', '', 8000, 12, 2222);
    }
}
