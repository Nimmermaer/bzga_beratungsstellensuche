<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Service\Geolocation\Decorator;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\Dto\Demand;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\GeoPositionDemandInterface;
use Bzga\BzgaBeratungsstellensuche\Domain\Model\GeopositionInterface;
use Bzga\BzgaBeratungsstellensuche\Service\Geolocation\GeolocationServiceInterface;
use Geocoder\Model\Address;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface as CacheInterface;

/**
 * @author Sebastian Schreiber
 */
class GeolocationServiceCacheDecorator implements GeolocationServiceInterface
{
    protected CacheInterface $cache;

    protected GeolocationServiceInterface $geolocationService;

    public function __construct(GeolocationServiceInterface $geolocationService, CacheInterface $cache)
    {
        $this->geolocationService = $geolocationService;
        $this->cache              = $cache;
    }

    public function findAddressByDemand(Demand $demand): ?Address
    {
        $cacheIdentifier = sha1($demand->getAddressToGeocode());
        if ($this->cache->has($cacheIdentifier)) {
            return unserialize($this->cache->get($cacheIdentifier));
        }

        $address = $this->geolocationService->findAddressByDemand($demand);
        $this->cache->set($cacheIdentifier, serialize($address));

        return $address;
    }

    /**
     * @return mixed
     */
    public function getDistanceSqlField(GeoPositionDemandInterface $demandPosition, string $table, string $alias = 'distance')
    {
        return $this->geolocationService->getDistanceSqlField($demandPosition, $table, $alias);
    }

    /**
     * @return mixed
     */
    public function calculateDistance(GeopositionInterface $demandPosition, GeopositionInterface $locationPosition)
    {
        return $this->geolocationService->calculateDistance($demandPosition, $locationPosition);
    }
}
