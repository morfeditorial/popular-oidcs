<?php

declare(strict_types=1);

namespace Morfeditorial\PopularOidcs\OAuth;

final class ProviderConfiguration
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $authorizationEndpoint,
        private readonly string $tokenEndpoint,
        private readonly string $userinfoEndpoint,
        private readonly string $scope,
        private readonly string $label,
        private readonly string $icon,
    ) {
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getAuthorizationEndpoint(): string
    {
        return $this->authorizationEndpoint;
    }

    public function getTokenEndpoint(): string
    {
        return $this->tokenEndpoint;
    }

    public function getUserinfoEndpoint(): string
    {
        return $this->userinfoEndpoint;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }
}
