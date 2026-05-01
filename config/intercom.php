<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Intercom workspace
    |--------------------------------------------------------------------------
    |
    | The Messenger widget app id (workspace id from Intercom). The same
    | value is used across every consuming app so users land in a single
    | Intercom workspace regardless of which Unified app they chat from.
    |
    */

    'app_id' => env('INTERCOM_APP_ID'),

    'api_base' => env('INTERCOM_API_BASE', 'https://api-iam.intercom.io'),

    /*
    |--------------------------------------------------------------------------
    | Messenger API Secret (JWT signing key)
    |--------------------------------------------------------------------------
    |
    | Intercom now authenticates Messenger users with HS256-signed JWTs
    | instead of the legacy HMAC `user_hash` flow. The shared secret is
    | found in your Intercom workspace at:
    |
    |   Settings > Messenger > Security > Messenger API Secret
    |
    | The package signs a short-lived JWT server-side per request and
    | passes it to the browser as `intercom_user_jwt`. Identifying claims
    | (user_id, email, company) live INSIDE the JWT so the browser can't
    | tamper with them.
    |
    | Leave this null in local dev to load the Messenger unauthenticated.
    |
    */

    'jwt_secret' => env('INTERCOM_JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT lifetime
    |--------------------------------------------------------------------------
    |
    | How long each generated JWT stays valid. Intercom recommends a
    | minimum of 5 minutes; 1 hour balances security with avoiding
    | unexpected re-auth on slow networks.
    |
    */

    'jwt_ttl_seconds' => (int) env('INTERCOM_JWT_TTL_SECONDS', 3600),

    /*
    |--------------------------------------------------------------------------
    | Local company → SSO id translation
    |--------------------------------------------------------------------------
    |
    | Each app stores companies in its own table; the widget needs to
    | report a stable id across apps so a single Intercom Company groups
    | every user in that agency. By default the widget looks up the
    | active user's local company_id and translates it via the
    | sso-client MetricContextResolver — so the value sent to Intercom
    | is the SSO company id, not the local one.
    |
    | Apps with non-standard schemas can override by binding a custom
    | IntercomPayloadResolver in their AppServiceProvider.
    |
    */

    'company_model' => env('INTERCOM_COMPANY_MODEL', 'App\\Models\\Company'),

    'company_sso_id_column' => env('INTERCOM_COMPANY_SSO_ID_COLUMN', 'sso_company_id'),

    /*
    |--------------------------------------------------------------------------
    | Active company resolution
    |--------------------------------------------------------------------------
    |
    | The default resolver picks the active company in this order:
    |   1. session('selected_company_id')   (Crew-Scheduling-style apps)
    |   2. auth()->user()->company_id        (most apps)
    |   3. auth()->user()->company           (Eloquent relation)
    |
    */

    'session_company_key' => env('INTERCOM_SESSION_COMPANY_KEY', 'selected_company_id'),

];
