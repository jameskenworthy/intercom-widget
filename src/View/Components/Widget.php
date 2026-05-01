<?php

declare(strict_types=1);

namespace Unified\IntercomWidget\View\Components;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Unified\IntercomWidget\Contracts\IntercomPayloadResolver;

class Widget extends Component
{
    public function __construct(
        protected ConfigRepository $config,
        protected IntercomPayloadResolver $resolver,
    ) {}

    public function render(): View|string
    {
        $appId = $this->config->get('intercom.app_id');

        if (! is_string($appId) || $appId === '') {
            return '';
        }

        $payload = $this->resolver->resolve();

        if ($payload === null) {
            return '';
        }

        $secret = $this->config->get('intercom.identity_secret');
        $userHash = is_string($secret) && $secret !== ''
            ? hash_hmac('sha256', (string) $payload->userId, $secret)
            : null;

        $apiBase = (string) $this->config->get('intercom.api_base', 'https://api-iam.intercom.io');

        return view('intercom-widget::components.widget', [
            'appId' => $appId,
            'settings' => $payload->toIntercomSettings($appId, $apiBase, $userHash),
        ]);
    }
}
