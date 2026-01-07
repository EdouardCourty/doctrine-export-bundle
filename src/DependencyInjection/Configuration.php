<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('doctrine_export');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('google_sheets')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('credentials_path')
                            ->defaultNull()
                            ->info('Path to Google Service Account JSON credentials file OR a JSON string')
                        ->end()
                        ->integerNode('batch_size')
                            ->defaultValue(10000)
                            ->min(1)
                            ->max(40000) // Google Sheets API limit
                            ->info('Number of rows to buffer before batch writing')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
