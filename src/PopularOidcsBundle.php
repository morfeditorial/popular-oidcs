<?php

declare(strict_types=1);

namespace Morfeditorial\PopularOidcs;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class PopularOidcsBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->load('Morfeditorial\\PopularOidcs\\', '../src/')
            ->exclude('../src/PopularOidcsBundle.php');
    }
}
