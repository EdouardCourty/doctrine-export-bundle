<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\DependencyInjection;

use Ecourty\DoctrineExportBundle\Contract\ExportStrategyInterface;
use Ecourty\DoctrineExportBundle\Strategy\GoogleSheetsExportStrategy;
use Google\Client;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DoctrineExportExtension extends Extension
{
    private const string TAG_EXPORT_STRATEGY = 'doctrine_export.strategy';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');

        $container->registerForAutoconfiguration(ExportStrategyInterface::class)
            ->addTag(self::TAG_EXPORT_STRATEGY);

        // Register Google Sheets strategy if google/apiclient is available AND credentials are configured
        if (class_exists(Client::class) && !empty($config['google_sheets']['credentials_path'])) {
            $this->registerGoogleSheetsStrategy($container, $config['google_sheets']);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerGoogleSheetsStrategy(ContainerBuilder $container, array $config): void
    {
        $container->register(GoogleSheetsExportStrategy::class)
            ->setArguments([
                '$credentialsPath' => $config['credentials_path'],
                '$batchSize' => $config['batch_size'],
            ])
            ->addTag(self::TAG_EXPORT_STRATEGY)
            ->setPublic(false);
    }
}
