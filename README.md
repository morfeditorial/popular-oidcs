<div align="center">

<img src="assets/images/morf-logo.svg" alt="Morf Editorial" width="320" />

# popular-oidcs

*OpenID Connect / OAuth 2.0 identity provider adapter for the Machinima platform.*

[![Latest Stable Version](https://img.shields.io/packagist/v/morfeditorial/popular-oidcs.svg?label=Packagist&logo=packagist)](https://packagist.org/packages/morfeditorial/popular-oidcs)
[![Total Downloads](https://img.shields.io/packagist/dt/morfeditorial/popular-oidcs.svg?label=Downloads&logo=packagist)](https://packagist.org/packages/morfeditorial/popular-oidcs)
[![License](https://img.shields.io/packagist/l/morfeditorial/popular-oidcs.svg?label=Licence&logo=open-source-initiative)](https://packagist.org/packages/morfeditorial/popular-oidcs)

[Architecture](#architecture) · [Tech stack](#tech-stack) · [Installation](#installation) · [Configuration](#configuration) · [Routes](#routes) · [Contributing](#contributing)

---

</div>

This Symfony Bundle plugs OAuth 2.0 / OpenID Connect sign-in (Google, GitHub, or any other compliant provider) into the Machinima platform. Providers are described purely by configuration — there is no per-provider code, so adding a new one means adding a few lines of YAML.

## Architecture

The bundle is an adapter for [`machinima-core`](https://github.com/morfeditorial/machinima-core). It implements the core's identity contracts and leaves all domain logic (creating users, authors, etc.) to the host application:

- **`OauthIdentityProvider`** — implements `IdentityProviderPort` and `IdentityProviderMetadataProvider`. One service is registered per configured provider and tagged `app.identity_provider`, so the core picks it up automatically. It exposes the provider's `label`, `icon` and `login_url` as metadata and converts raw userinfo into an `IdentityAssertion` (`sub`/`id` → subject id, `name`/`login` → display name, `picture`/`avatar_url` → avatar).
- **`OAuthClient`** — a thin wrapper around Symfony HttpClient that exchanges an authorization code for a token and fetches userinfo.
- **`OAuthStateStorage`** — generates a random `state` and keeps it in the app cache for 10 minutes. A state can be used only once.
- **`ProviderConfiguration`** — an immutable value object holding a single provider's settings.
- **Controllers** — handle the three flows described below.

### Flows

**Sign-in**

1. `GET /oauth/login/{provider}` generates a `state` and redirects to the provider's authorization endpoint.
2. The provider redirects back to `GET /oauth/callback?code=…&state=…`.
3. The bundle validates the `state`, exchanges the `code` for a token, fetches userinfo and builds an `IdentityAssertion`.
4. A `UserAuthenticatedEvent` is dispatched. The core's listener finds or creates the user.

**Account linking**

1. A signed-in user opens `GET /oauth/link/{provider}`; the `state` carries the user id and a `linking` flag.
2. The provider redirects back to `GET /oauth/link/callback`.
3. A new `UserIdentity` is attached to the current user. If that external account is already linked to someone else, linking is refused.

## Tech stack

- **Backend**: PHP 8.4+, Symfony 8.1 (FrameworkBundle, HttpClient, Routing, SecurityBundle)
- **Host dependency**: [`morfeditorial/machinima-app`](https://github.com/morfeditorial/machinima-app) (contracts, `UserIdentity`, `UserAuthenticatedEvent`)

## Installation

```bash
composer require morfeditorial/popular-oidcs
```

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    Morfeditorial\PopularOidcs\PopularOidcsBundle::class => ['all' => true],
];
```

The bundle expects the host application to provide:

- `App\Entity\UserIdentity` and `App\Entity\User` (Doctrine entities)
- `App\Event\UserAuthenticatedEvent`
- a route named `app_home` (used for redirects after success or failure)
- a cache pool injectable as `Symfony\Component\Cache\Adapter\AdapterInterface`
- public access to `^/oauth/` in `security.yaml`

## Configuration

Providers live under the `popular_oidcs.providers` key:

```yaml
# config/packages/popular_oidcs.yaml
popular_oidcs:
    providers:
        google:
            client_id: '%env(OAUTH_GOOGLE_CLIENT_ID)%'
            client_secret: '%env(OAUTH_GOOGLE_CLIENT_SECRET)%'
            authorization_endpoint: 'https://accounts.google.com/o/oauth2/v2/auth'
            token_endpoint: 'https://oauth2.googleapis.com/token'
            userinfo_endpoint: 'https://openidconnect.googleapis.com/v1/userinfo'
            scope: 'openid email profile'
            label: 'Google'
            icon: 'google'
        github:
            client_id: '%env(OAUTH_GITHUB_CLIENT_ID)%'
            client_secret: '%env(OAUTH_GITHUB_CLIENT_SECRET)%'
            authorization_endpoint: 'https://github.com/login/oauth/authorize'
            token_endpoint: 'https://github.com/login/oauth/access_token'
            userinfo_endpoint: 'https://api.github.com/user'
            scope: 'read:user user:email'
            label: 'GitHub'
            icon: 'github'
```

| Option | Required | Default | Description |
|---|---|---|---|
| `client_id` | yes | — | OAuth client ID issued by the provider |
| `client_secret` | yes | — | OAuth client secret |
| `authorization_endpoint` | yes | — | URL the user is redirected to for consent |
| `token_endpoint` | yes | — | URL where the authorization code is exchanged for a token |
| `userinfo_endpoint` | yes | — | URL returning the user's profile |
| `scope` | no | `openid email profile` | Space-separated scopes |
| `label` | yes | — | Human-readable name shown on the login button |
| `icon` | yes | — | Provider identifier; also used as the provider name in assertions and in `/oauth/login/{provider}` (lowercase letters only) |

When registering the app with a provider, add these redirect URIs:

- `https://your-host/oauth/callback` — sign-in
- `https://your-host/oauth/link/callback` — account linking

## Routes

| Route name | Path | Purpose |
|---|---|---|
| `popular_oidcs_login` | `/oauth/login/{provider}` | Start the sign-in flow |
| `popular_oidcs_callback` | `/oauth/callback` | Sign-in callback |
| `popular_oidcs_link` | `/oauth/link/{provider}` | Start account linking (authenticated users only) |
| `popular_oidcs_link_callback` | `/oauth/link/callback` | Account linking callback |

The controllers use PHP attributes, so make sure your host application's routing loads them.

## Project structure

```text
popular-oidcs/
+-- assets/
|   \-- images/                  # Logo
+-- config/
|   \-- services.yaml            # Service autowiring for the bundle
\-- src/
    +-- Controller/              # login, callback and account-linking controllers
    +-- DependencyInjection/     # configuration tree for popular_oidcs
    +-- IdentityProvider/        # IdentityProviderPort implementation
    +-- OAuth/                   # HTTP client, state storage, provider config
    \-- PopularOidcsBundle.php   # Main bundle class
```

## Contributing

Contributions are welcome and appreciated! Here's how you can contribute:

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

Please make sure to update tests as appropriate and adhere to the existing coding style.

## License

This bundle is licensed under the CSSM Unlimited License v2.0 (CSSM-ULv2). See the [LICENSE](LICENSE) file for details.
