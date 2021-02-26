<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\DependencyInjection\CompilerPass;

use Doctrine\Migrations\DependencyFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function assert;
use function is_string;
use function sprintf;

class ConfigureDependencyFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $preferredEm         = $container->getParameter('doctrine.migrations.preferred_em');
        $preferredConnection = $container->getParameter('doctrine.migrations.preferred_connection');
        $diDefinition        = $container->getDefinition('doctrine.migrations.dependency_factory');
        assert(is_string($preferredEm) || $preferredEm === null);
        assert(is_string($preferredConnection) || $preferredConnection === null);

        $emID = sprintf('doctrine.orm.%s_entity_manager', $preferredEm ?? 'default');

        if ($preferredConnection === null && $container->has($emID)) {
            $container->getDefinition('doctrine.migrations.em_loader')
                ->setArgument(0, new Reference($emID));

            $diDefinition->setFactory([DependencyFactory::class, 'fromEntityManager']);
            $diDefinition->setArgument(1, new Reference('doctrine.migrations.em_loader'));
        } else {
            $connectionId = sprintf('doctrine.dbal.%s_connection', $preferredConnection ?? 'default');
            $container->getDefinition('doctrine.migrations.connection_loader')
                ->setArgument(0, new Reference($connectionId));

            $diDefinition->setFactory([DependencyFactory::class, 'fromConnection']);
            $diDefinition->setArgument(1, new Reference('doctrine.migrations.connection_loader'));
        }
    }
}
