<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Map\Leaflet;

use Bzga\BzgaBeratungsstellensuche\Domain\Map\CoordinateInterface;
use Bzga\BzgaBeratungsstellensuche\Domain\Map\MarkerInterface;
use Bzga\BzgaBeratungsstellensuche\Domain\Map\PopUpInterface;
use Netzmacht\JavascriptBuilder\Type\Expression;
use Netzmacht\LeafletPHP\Definition\Type\ImageIcon;
use Netzmacht\LeafletPHP\Definition\UI\Marker as LeafletMarker;

final class Marker implements MarkerInterface
{
    /**
     * @var LeafletMarker
     */
    private LeafletMarker $marker;

    public function __construct(string $identifier, CoordinateInterface $coordinate)
    {
        $this->marker = new LeafletMarker($identifier, $coordinate->getCoordinate());
    }

    public function getMarker(): LeafletMarker
    {
        return $this->marker;
    }

    public function setOptions(array $options): void
    {
        // @extensionScannerIgnoreLine
        $this->marker->setOptions($options);
    }

    public function addIconFromPath(string $iconPath): void
    {
        $this->marker->setIcon(new ImageIcon('icon', $iconPath));
    }

    public function addPopUp(PopUpInterface $popUp, string $content, bool $open = false): void
    {
        $this->marker->bindPopup($popUp->getPopUp());
        $this->marker->setPopupContent($content);

        if ($open) {
            $handler = <<<'JS'
function (event) {
  event.target.openPopup();
}
JS;
            $this->marker->on('add', new Expression($handler));
        }
    }
}
