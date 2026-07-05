<?php

declare(strict_types=1);

namespace Morfeditorial\PopularOidcs;

use Morfeditorial\PopularOidcs\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class PopularOidcsBundle extends AbstractBundle
{
    public function getConfiguration(): ConfigurationInterface
    {
        return new Configuration();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->load('Morfeditorial\\PopularOidcs\\', '../src/')
            ->exclude([
                '../src/PopularOidcsBundle.php',
                '../src/DependencyInjection/',
            ]);

        foreach ($config['providers'] as $name => $providerConfig) {
            $configDefinition = new Definition('Morfeditorial\\PopularOidcs\\OAuth\\ProviderConfiguration', [
                'clientId' => $providerConfig['client_id'],
                'clientSecret' => $providerConfig['client_secret'],
                'authorizationEndpoint' => $providerConfig['authorization_endpoint'],
                'tokenEndpoint' => $providerConfig['token_endpoint'],
                'userinfoEndpoint' => $providerConfig['userinfo_endpoint'],
                'scope' => $providerConfig['scope'],
                'label' => $providerConfig['label'],
                'icon' => $providerConfig['icon'],
            ]);

            $builder->setDefinition('popular_oidcs.provider.'.$name.'.config', $configDefinition);

            $clientDefinition = new Definition('Morfeditorial\\PopularOidcs\\OAuth\\OAuthClient');
            $clientDefinition->setArgument('$httpClient', new \Symfony\Component\DependencyInjection\Reference('http_client'));

            $builder->setDefinition('popular_oidcs.provider.'.$name.'.client', $clientDefinition);

            $providerDefinition = new Definition(
                'Morfeditorial\\PopularOidcs\\IdentityProvider\\OauthIdentityProvider',
            );
            $providerDefinition->setArgument('$config', new \Symfony\Component\DependencyInjection\Reference('popular_oidcs.provider.'.$name.'.config'));
            $providerDefinition->setArgument('$client', new \Symfony\Component\DependencyInjection\Reference('popular_oidcs.provider.'.$name.'.client'));
            $providerDefinition->addTag('app.identity_provider');

            $builder->setDefinition('popular_oidcs.provider.'.$name, $providerDefinition);
        }
    }
}
