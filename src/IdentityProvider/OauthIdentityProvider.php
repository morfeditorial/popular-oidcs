<?php

declare(strict_types=1);

namespace Morfeditorial\PopularOidcs\IdentityProvider;

use App\Contract\IdentityAssertion;
use App\Contract\IdentityProviderMetadataProvider;
use App\Contract\IdentityProviderPort;
use Morfeditorial\PopularOidcs\OAuth\OAuthClient;
use Morfeditorial\PopularOidcs\OAuth\ProviderConfiguration;

final class OauthIdentityProvider implements IdentityProviderPort, IdentityProviderMetadataProvider
{
    public function __construct(
        private ProviderConfiguration $config,
        private OAuthClient $client,
    ) {
    }

    public function getProviderName(): string
    {
        return $this->config->getIcon();
    }

    public function validateAssertion(string $rawAssertion): IdentityAssertion
    {
        throw new \RuntimeException('OAuth assertions are handled via callback, not direct validation.');
    }

    public function getMetadata(): array
    {
        return [
            'label' => $this->config->getLabel(),
            'icon' => $this->config->getIcon(),
        ];
    }

    public function getConfig(): ProviderConfiguration
    {
        return $this->config;
    }

    public function getClient(): OAuthClient
    {
        return $this->client;
    }

    public function createAssertion(array $userInfo): IdentityAssertion
    {
        $providerSubjectId = (string) ($userInfo['sub'] ?? $userInfo['id'] ?? '');
        $displayName = $userInfo['name'] ?? $userInfo['login'] ?? null;
        $avatarUrl = $userInfo['picture'] ?? $userInfo['avatar_url'] ?? null;

        return new IdentityAssertion(
            providerName: $this->getProviderName(),
            providerSubjectId: $providerSubjectId,
            displayName: $displayName,
            avatarUrl: $avatarUrl,
            claims: $userInfo,
        );
    }
}
