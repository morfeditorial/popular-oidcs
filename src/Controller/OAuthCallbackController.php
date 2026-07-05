<?php

declare(strict_types=1);

namespace Morfeditorial\PopularOidcs\Controller;

use App\Event\UserAuthenticatedEvent;
use Morfeditorial\PopularOidcs\IdentityProvider\OauthIdentityProvider;
use Morfeditorial\PopularOidcs\OAuth\OAuthStateStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class OAuthCallbackController extends AbstractController
{
    public function __construct(
        private OAuthStateStorage $stateStorage,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    #[Route('/oauth/callback', name: 'popular_oidcs_callback')]
    public function callback(Request $request, OauthIdentityProvider $identityProvider): Response
    {
        $code = $request->query->get('code');
        $state = $request->query->get('state');

        if (!$code || !$state) {
            $this->addFlash('error', 'Invalid OAuth response.');

            return $this->redirectToRoute('app_home');
        }

        $stateData = $this->stateStorage->get($state);

        if (null === $stateData) {
            $this->addFlash('error', 'OAuth state expired or invalid.');

            return $this->redirectToRoute('app_home');
        }

        $config = $identityProvider->getConfig();
        $client = $identityProvider->getClient();

        $redirectUri = $this->generateUrl('popular_oidcs_callback', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            $tokenData = $client->exchangeCodeForToken($config, $code, $redirectUri);
            $accessToken = $tokenData['access_token'] ?? '';

            if (!$accessToken) {
                $this->addFlash('error', 'Failed to obtain access token.');

                return $this->redirectToRoute('app_home');
            }

            $userInfo = $client->fetchUserInfo($config, $accessToken);
            $assertion = $identityProvider->createAssertion($userInfo);

            $authEvent = new UserAuthenticatedEvent($assertion);
            $this->eventDispatcher->dispatch($authEvent);

            if ($authEvent->getUser()) {
                $this->addFlash('success', 'Successfully authenticated.');

                return $this->redirectToRoute('app_home');
            }

            $this->addFlash('error', 'Authentication failed.');

            return $this->redirectToRoute('app_home');
        } catch (\Exception $e) {
            $this->addFlash('error', 'OAuth authentication failed: '.$e->getMessage());

            return $this->redirectToRoute('app_home');
        }
    }
}
