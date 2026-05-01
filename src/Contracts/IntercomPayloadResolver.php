<?php

declare(strict_types=1);

namespace Unified\IntercomWidget\Contracts;

use Unified\IntercomWidget\IntercomPayload;

interface IntercomPayloadResolver
{
    /**
     * Build the payload for the currently authenticated user, or null
     * if no user is authenticated / no payload should be emitted.
     */
    public function resolve(): ?IntercomPayload;
}
