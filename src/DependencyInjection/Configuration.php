<?php

declare(strict_types=1);

namespace Morfeditorial\PopularOidcs\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigurationTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('popular_oidcs');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->arrayNode('providers')
                ->prototype('array')
                    ->children()
                        ->scalarNode('client_id')->isRequired()->end()
                        ->scalarNode('client_secret')->isRequired()->end()
                        ->scalarNode('authorization_endpoint')->isRequired()->end()
                        ->scalarNode('token_endpoint')->isRequired()->end()
                        ->scalarNode('userinfo_endpoint')->isRequired()->end()
                        ->scalarNode('scope')->defaultValue('openid email profile')->end()
                        ->scalarNode('label')->isRequired()->end()
                        ->scalarNode('icon')->isRequired()->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
