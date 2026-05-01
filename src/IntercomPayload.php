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
     * Claims to embed in the signed JWT. Identifying / protected fields
     * (user_id, email, name, company) live here so the browser can't
     * tamper with them. iat/exp are added by the Widget at sign time.
     *
     * @return array<string, mixed>
     */
    public function toJwtClaims(): array
    {
        $claims = [
            'user_id' => (string) $this->userId,
        ];

        if ($this->email !== null) {
            $claims['email'] = $this->email;
        }

        if ($this->name !== null) {
            $claims['name'] = $this->name;
        }

        if ($this->createdAt !== null) {
            $claims['created_at'] = $this->createdAt;
        }

        if ($this->company !== null) {
            $claims['company'] = array_filter(
                $this->company,
                static fn ($value) => $value !== null,
            );
        }

        return $claims;
    }
}
