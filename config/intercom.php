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
    | Identity Verification (HMAC) secret
    |--------------------------------------------------------------------------
    |
    | The Identity Verification secret from Intercom. When set, the
    | server-side `user_hash` is signed and emitted in `intercomSettings`
    | so a malicious client cannot impersonate another user_id in chat.
    | Intercom strongly recommends turning this on for any logged-in
    | Messenger.
    |
    | If null, the widget still loads but unverified — useful for local
    | dev where the secret is not provisioned.
    |
    */

    'identity_secret' => env('INTERCOM_IDENTITY_SECRET'),

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
