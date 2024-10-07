<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Bzga\BzgaBeratungsstellensuche\Command\ImportCommand;
use Bzga\BzgaBeratungsstellensuche\Command\TruncateCommand;
use Bzga\BzgaBeratungsstellensuche\Domain\Map\Leaflet\MapBuilder;
use Bzga\BzgaBeratungsstellensuche\Domain\Map\MapBuilderInterface;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer\EntryNormalizer;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer\GetSetMethodNormalizer;
use Bzga\BzgaBeratungsstellensuche\Factory\GeocoderFactory;
use Bzga\BzgaBeratungsstellensuche\Service\Geolocation\Decorator\GeolocationServiceCacheDecorator;
use Bzga\BzgaBeratungsstellensuche\Service\Geolocation\GeolocationService;
use Bzga\BzgaBeratungsstellensuche\Service\Geolocation\GeolocationServiceInterface;
use Bzga\BzgaBeratungsstellensuche\Service\Importer\ImporterInterface;
use Bzga\BzgaBeratungsstellensuche\Service\Importer\XmlImporter;
use Geocoder\Provider\Provider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void {
    $services = $containerConfigurator->services();
    $services->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();
    $services->load('Bzga\\BzgaBeratungsstellensuche\\', __DIR__ . '/../Classes/')->exclude([
        __DIR__ . '/../Classes/Domain/Model',
    ]);

    $services->set(GeolocationService::class)->arg('$geocoder', service('beratungsstellensuche.geocoder'));
    $services->alias(GeolocationServiceInterface::class, GeolocationService::class);
    $services->alias(ImporterInterface::class, XmlImporter::class);

    $services->set('beratungsstellensuche.cache.geolcation', FrontendInterface::class)
            ->factory([service(CacheManager::class), 'getCache'])
            ->args(['bzgaberatungsstellensuche_cache_coordinates']);

    $services->set('beratungsstellensuche.geocoder', Provider::class)
        ->factory([service(GeocoderFactory::class), 'createInstance']);

    $services->alias(MapBuilderInterface::class, MapBuilder::class);

    $services->set(GeolocationServiceCacheDecorator::class)->arg('$cache', service('beratungsstellensuche.cache.geolcation'))->public();
    $services->set(GetSetMethodNormalizer::class)->public();
    $services->set(EntryNormalizer::class)->public();
    $services->set(\Bzga\BzgaBeratungsstellensuche\Persistence\Mapper\DataMap::class)
        ->arg('$dataMapFactory', service(DataMapFactory::class))
        ->public();

    $services->set('event.listener.plugin_backend_preview', \Bzga\BzgaBeratungsstellensuche\EventListener\ElementPreviewEventListener::class)
        ->tag('event.listener');
    $services->set('reports.status.status_allow_url_fopen_or_curl', \Bzga\BzgaBeratungsstellensuche\Report\StatusAllowUrlFopenOrCurlReport::class)
        ->tag('reports.status');
    // Add commands
    $services->set('console.command.beratungsstellensuche_import', ImportCommand::class)
        ->tag('console.command', [
            'command' => 'bzga:beratungsstellensuche:import',
            'schedulable' => true,
        ]);

    $services->set('console.command.beratungsstellensuche_truncate', TruncateCommand::class)
        ->tag('console.command', [
            'command' => 'bzga:beratungsstellensuche:truncate',
            'schedulable' => true,
        ]);
};
