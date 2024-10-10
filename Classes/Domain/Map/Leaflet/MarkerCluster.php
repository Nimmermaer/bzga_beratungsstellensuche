<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Map\Leaflet;

use Bzga\BzgaBeratungsstellensuche\Domain\Map\MarkerClusterInterface;
use Bzga\BzgaBeratungsstellensuche\Domain\Map\MarkerInterface;
use Netzmacht\LeafletPHP\Plugins\MarkerCluster\MarkerClusterGroup;

final class MarkerCluster implements MarkerClusterInterface
{
    private MarkerClusterGroup $markerCluster;

    public function __construct(string $identifier)
    {
        $this->markerCluster = new MarkerClusterGroup($identifier);
    }

    public function getMarkerCluster(): MarkerClusterGroup
    {
        return $this->markerCluster;
    }

    public function addMarker(MarkerInterface $marker): void
    {
        $this->markerCluster->addLayer($marker->getMarker());
    }

    public function setOptions(array $options): void
    {
        // @extensionScannerIgnoreLine
        $this->markerCluster->setOptions($options);
    }
}
