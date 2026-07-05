<?php

declare(strict_types=1);

namespace Morfeditorial\PopularOidcs\OAuth;

use Symfony\Component\Cache\Adapter\AdapterInterface;

final class OAuthStateStorage
{
    private const STATE_TTL = 600; // 10 minutes

    public function __construct(
        private AdapterInterface $cache,
    ) {
    }

    public function generate(string $providerName, bool $linking = false, ?int $userId = null): string
    {
        $state = bin2hex(random_bytes(32));

        $data = [
            'provider' => $providerName,
            'linking' => $linking,
            'user_id' => $userId,
            'created_at' => time(),
        ];

        $this->cache->save(
            $this->cache->getItem('oauth_state_'.$state)->set($data)->expiresAfter(self::STATE_TTL),
        );

        return $state;
    }

    public function get(string $state): ?array
    {
        $item = $this->cache->getItem('oauth_state_'.$state);

        if (!$item->isHit()) {
            return null;
        }

        $data = $item->get();
        $this->cache->deleteItem('oauth_state_'.$state);

        return $data;
    }
}
