<?php

declare(strict_types=1);

namespace Unified\IntercomWidget\Resolvers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Unified\IntercomWidget\Contracts\IntercomPayloadResolver;
use Unified\IntercomWidget\IntercomPayload;

/**
 * Default payload resolver — works in any consuming app whose User and
 * Company models follow the standard Unified conventions:
 *   - users.sso_id          (or users.id is itself the SSO id, as in SSO)
 *   - companies.sso_company_id
 *
 * Apps with non-standard schemas can bind their own
 * IntercomPayloadResolver in their AppServiceProvider — every other
 * piece (Blade component, HMAC signing, JS snippet) keeps working.
 */
class DefaultIntercomPayloadResolver implements IntercomPayloadResolver
{
    /**
     * @param  object|null  $metricContext  An sso-client MetricContextResolver, when sso-client is installed.
     *                                      Typed as `object` to keep this package usable in apps (like SSO)
     *                                      that don't depend on unified/sso-client.
     */
    public function __construct(
        protected AuthFactory $auth,
        protected ConfigRepository $config,
        protected ?Session $session = null,
        protected ?object $metricContext = null,
    ) {}

    public function resolve(): ?IntercomPayload
    {
        $user = $this->auth->guard()->user();

        if ($user === null) {
            return null;
        }

        $userId = $this->resolveUserId($user);

        if ($userId === null) {
            return null;
        }

        return new IntercomPayload(
            userId: $userId,
            name: $this->stringAttribute($user, 'name'),
            email: $this->stringAttribute($user, 'email'),
            createdAt: $this->timestampAttribute($user, 'created_at'),
            company: $this->resolveCompany($user),
        );
    }

    protected function resolveUserId(Authenticatable $user): string|int|null
    {
        $ssoIdAttribute = $user->getAttribute('sso_id');

        if ($ssoIdAttribute !== null && $ssoIdAttribute !== '') {
            return (string) $ssoIdAttribute;
        }

        $localId = $user->getAuthIdentifier();

        if ($localId === null) {
            return null;
        }

        if ($this->metricContext !== null && method_exists($this->metricContext, 'ssoUserId') && is_int($localId)) {
            $ssoId = $this->metricContext->ssoUserId($localId);

            if ($ssoId !== null) {
                return (string) $ssoId;
            }
        }

        return (string) $localId;
    }

    /**
     * @return array{company_id: string, name?: string|null, created_at?: int|null}|null
     */
    protected function resolveCompany(Authenticatable $user): ?array
    {
        $localCompanyId = $this->resolveActiveCompanyId($user);

        if ($localCompanyId === null) {
            return null;
        }

        $companyModel = $this->config->get('intercom.company_model');
        $ssoIdColumn = (string) $this->config->get('intercom.company_sso_id_column', 'sso_company_id');

        if (! is_string($companyModel) || ! class_exists($companyModel)) {
            return null;
        }

        /** @var Model|null $company */
        $company = $companyModel::query()->find($localCompanyId);

        if ($company === null) {
            return null;
        }

        $ssoCompanyId = $company->getAttribute($ssoIdColumn);

        if ($ssoCompanyId === null || $ssoCompanyId === '') {
            return null;
        }

        $payload = [
            'company_id' => (string) $ssoCompanyId,
        ];

        $name = $company->getAttribute('name');
        if (is_string($name) && $name !== '') {
            $payload['name'] = $name;
        }

        $createdAt = $company->getAttribute('created_at');
        if ($createdAt !== null && method_exists($createdAt, 'getTimestamp')) {
            $payload['created_at'] = (int) $createdAt->getTimestamp();
        }

        return $payload;
    }

    protected function resolveActiveCompanyId(Authenticatable $user): int|string|null
    {
        $sessionKey = (string) $this->config->get('intercom.session_company_key', 'selected_company_id');

        if ($this->session !== null && $this->session->has($sessionKey)) {
            $value = $this->session->get($sessionKey);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        $attribute = $user->getAttribute('company_id');
        if ($attribute !== null && $attribute !== '') {
            return $attribute;
        }

        $relation = $user->getAttribute('company');
        if ($relation instanceof Model) {
            $key = $relation->getKey();
            if ($key !== null) {
                return $key;
            }
        }

        if ($user instanceof Model && method_exists($user, 'companies')) {
            $companiesRelation = $user->companies();
            if ($companiesRelation instanceof Relation) {
                $first = $companiesRelation->first();
                if ($first instanceof Model) {
                    $key = $first->getKey();
                    if ($key !== null) {
                        return $key;
                    }
                }
            }
        }

        return null;
    }

    protected function stringAttribute(Authenticatable $user, string $key): ?string
    {
        $value = $user->getAttribute($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function timestampAttribute(Authenticatable $user, string $key): ?int
    {
        $value = $user->getAttribute($key);

        if ($value === null) {
            return null;
        }

        if (method_exists($value, 'getTimestamp')) {
            return (int) $value->getTimestamp();
        }

        return null;
    }
}
