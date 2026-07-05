<?php

declare(strict_types=1);

namespace Morfeditorial\PopularOidcs\Controller;

use App\Entity\UserIdentity;
use Doctrine\ORM\EntityManagerInterface;
use Morfeditorial\PopularOidcs\IdentityProvider\OauthIdentityProvider;
use Morfeditorial\PopularOidcs\OAuth\OAuthStateStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OAuthLinkController extends AbstractController
{
    public function __construct(
        private OAuthStateStorage $stateStorage,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/oauth/link/{provider}', name: 'popular_oidcs_link', requirements: ['provider' => '[a-z]+'])]
    public function link(string $provider, Request $request, OauthIdentityProvider $identityProvider): RedirectResponse
    {
        $user = $this->getUser();

        if (null === $user) {
            $this->addFlash('error', 'You must be logged in to link an account.');

            return $this->redirectToRoute('app_home');
        }

        $config = $identityProvider->getConfig();

        $state = $this->stateStorage->generate(
            providerName: $provider,
            linking: true,
            userId: (int) $user->getUserIdentifier(),
        );

        $redirectUri = $this->generateUrl('popular_oidcs_link_callback', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

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

    #[Route('/oauth/link/callback', name: 'popular_oidcs_link_callback')]
    public function linkCallback(Request $request, OauthIdentityProvider $identityProvider): Response
    {
        $code = $request->query->get('code');
        $state = $request->query->get('state');

        if (!$code || !$state) {
            $this->addFlash('error', 'Invalid OAuth response.');

            return $this->redirectToRoute('app_home');
        }

        $stateData = $this->stateStorage->get($state);

        if (null === $stateData || !$stateData['linking']) {
            $this->addFlash('error', 'OAuth state expired or invalid.');

            return $this->redirectToRoute('app_home');
        }

        $userId = $stateData['user_id'];
        $config = $identityProvider->getConfig();
        $client = $identityProvider->getClient();

        $redirectUri = $this->generateUrl('popular_oidcs_link_callback', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            $tokenData = $client->exchangeCodeForToken($config, $code, $redirectUri);
            $accessToken = $tokenData['access_token'] ?? '';

            if (!$accessToken) {
                $this->addFlash('error', 'Failed to obtain access token.');

                return $this->redirectToRoute('app_home');
            }

            $userInfo = $client->fetchUserInfo($config, $accessToken);
            $assertion = $identityProvider->createAssertion($userInfo);

            $existingIdentity = $this->entityManager->getRepository(UserIdentity::class)->findOneBy([
                'providerName' => $assertion->getProviderName(),
                'providerId' => $assertion->getProviderSubjectId(),
            ]);

            if (null !== $existingIdentity && (int) $existingIdentity->getUser()->getUserIdentifier() !== $userId) {
                $this->addFlash('error', 'This account is already linked to another user.');

                return $this->redirectToRoute('app_home');
            }

            if (null === $existingIdentity) {
                $user = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);

                if (null === $user) {
                    $this->addFlash('error', 'User not found.');

                    return $this->redirectToRoute('app_home');
                }

                $identity = new UserIdentity();
                $identity->setUser($user);
                $identity->setProviderName($assertion->getProviderName());
                $identity->setProviderId($assertion->getProviderSubjectId());
                $identity->setProviderData($assertion->getClaims());
                $this->entityManager->persist($identity);
                $this->entityManager->flush();
            }

            $this->addFlash('success', 'Account linked successfully.');

            return $this->redirectToRoute('app_home');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Account linking failed: '.$e->getMessage());

            return $this->redirectToRoute('app_home');
        }
    }
}
