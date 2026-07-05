<?php

declare(strict_types=1);

namespace Morfeditorial\PopularOidcs\Controller;

use Morfeditorial\PopularOidcs\IdentityProvider\OauthIdentityProvider;
use Morfeditorial\PopularOidcs\OAuth\OAuthStateStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class OAuthLoginController extends AbstractController
{
    public function __construct(
        private OAuthStateStorage $stateStorage,
    ) {
    }

    #[Route('/oauth/login/{provider}', name: 'popular_oidcs_login', requirements: ['provider' => '[a-z]+'])]
    public function login(string $provider, Request $request, OauthIdentityProvider $identityProvider): RedirectResponse
    {
        $config = $identityProvider->getConfig();

        $state = $this->stateStorage->generate(
            providerName: $provider,
            linking: false,
        );

        $redirectUri = $this->generateUrl('popular_oidcs_callback', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        $authUrl = $config->getAuthorizationEndpoint().'?'
            .http_build_query([
                'client_id' => $config->getClientId(),
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => $config->getScope(),
                'state' => $state,
            ]);

        return new RedirectResponse($authUrl);
    }
}
