<?php

declare(strict_types=1);

namespace Morfeditorial\PopularOidcs\OAuth;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OAuthClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function exchangeCodeForToken(ProviderConfiguration $config, string $code, string $redirectUri): array
    {
        $response = $this->httpClient->request('POST', $config->getTokenEndpoint(), [
            'body' => [
                'client_id' => $config->getClientId(),
                'client_secret' => $config->getClientSecret(),
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ],
        ]);

        return $response->toArray();
    }

    public function fetchUserInfo(ProviderConfiguration $config, string $accessToken): array
    {
        $response = $this->httpClient->request('GET', $config->getUserinfoEndpoint(), [
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
            ],
        ]);

        return $response->toArray();
    }
}
