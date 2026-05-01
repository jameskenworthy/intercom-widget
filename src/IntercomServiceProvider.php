<?php

declare(strict_types=1);

namespace Unified\IntercomWidget;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Unified\IntercomWidget\Contracts\IntercomPayloadResolver;
use Unified\IntercomWidget\Resolvers\DefaultIntercomPayloadResolver;
use Unified\IntercomWidget\View\Components\Widget;

class IntercomServiceProvider extends ServiceProvider
{
    /**
     * FQN for the optional sso-client metric context resolver. Kept as a
     * string so this package can be installed in apps that don't depend
     * on unified/sso-client (e.g. SSO itself).
     */
    private const METRIC_CONTEXT_RESOLVER_CONTRACT = 'Unified\\SsoClient\\Metrics\\Contracts\\MetricContextResolver';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/intercom.php', 'intercom');

        $this->app->bindIf(IntercomPayloadResolver::class, function (Container $app): IntercomPayloadResolver {
            $session = null;
            if ($app->bound('session.store')) {
                /** @var Session $session */
                $session = $app->make('session.store');
            }

            $metricContext = null;
            if ($app->bound(self::METRIC_CONTEXT_RESOLVER_CONTRACT)) {
                $metricContext = $app->make(self::METRIC_CONTEXT_RESOLVER_CONTRACT);
            }

            return new DefaultIntercomPayloadResolver(
                auth: $app->make(AuthFactory::class),
                config: $app->make(ConfigRepository::class),
                session: $session,
                metricContext: $metricContext,
            );
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'intercom-widget');

        $this->loadViewComponentsAs('intercom', [
            Widget::class,
        ]);

        $this->publishes([
            __DIR__.'/../config/intercom.php' => config_path('intercom.php'),
        ], 'intercom-config');

        $this->registerFilamentRenderHook();
    }

    /**
     * Filament panels render their own Blade root template, so a tag
     * dropped into the consuming app's layouts/app.blade.php never
     * reaches them. When Filament is installed we register a render
     * hook so the widget auto-injects on every panel page.
     */
    private function registerFilamentRenderHook(): void
    {
        $facade = 'Filament\\Support\\Facades\\FilamentView';

        if (! class_exists($facade)) {
            return;
        }

        $facade::registerRenderHook(
            'panels::body.end',
            fn (): string => Blade::render('<x-intercom-widget />'),
        );
    }
}
