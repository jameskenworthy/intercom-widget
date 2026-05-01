<?php

declare(strict_types=1);

namespace Unified\IntercomWidget;

class IntercomPayload
{
    /**
     * @param  array{company_id: string|int, name?: string|null, created_at?: int|null}|null  $company
     */
    public function __construct(
        public readonly string|int $userId,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?int $createdAt,
        public readonly ?array $company = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toIntercomSettings(string $appId, string $apiBase, ?string $userHash): array
    {
        $settings = [
            'api_base' => $apiBase,
            'app_id' => $appId,
            'user_id' => (string) $this->userId,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->createdAt,
        ];

        if ($userHash !== null) {
            $settings['user_hash'] = $userHash;
        }

        if ($this->company !== null) {
            $settings['company'] = array_filter(
                $this->company,
                static fn ($value) => $value !== null,
            );
        }

        return array_filter(
            $settings,
            static fn ($value) => $value !== null,
        );
    }
}
