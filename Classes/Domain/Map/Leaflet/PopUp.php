<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Map\Leaflet;

use Bzga\BzgaBeratungsstellensuche\Domain\Map\PopUpInterface;
use Netzmacht\LeafletPHP\Definition\UI\Popup as CorePopUp;

final class PopUp implements PopUpInterface
{
    /**
     * @var \Netzmacht\LeafletPHP\Definition\UI\Popup
     */
    private CorePopUp $popUp;

    public function __construct(string $identifier)
    {
        $this->popUp = new CorePopUp($identifier);
        $this->popUp->setAutoPan(true);
    }

    public function getPopUp(): CorePopUp
    {
        return $this->popUp;
    }

    public function setOptions(array $options): void
    {
        // @extensionScannerIgnoreLine
        $this->popUp->setOptions($options);
    }
}
