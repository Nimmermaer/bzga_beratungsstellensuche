<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\ViewHelpers;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\GeopositionInterface;
use Bzga\BzgaBeratungsstellensuche\Service\Geolocation\Decorator\GeolocationServiceCacheDecorator;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @author Sebastian Schreiber
 */
class DistanceViewHelper extends AbstractViewHelper
{
    /**
     * @var GeolocationServiceCacheDecorator
     */
    protected $geolocationService;

    public function __construct(\Bzga\BzgaBeratungsstellensuche\Service\Geolocation\Decorator\GeolocationServiceCacheDecorator $geolocationService)
    {
        $this->geolocationService = $geolocationService;
    }

    /**
     * @return mixed
     */
    public function render()
    {
        $demandPosition = $this->arguments['demandPosition'];
        $location = $this->arguments['location'];
        return $this->geolocationService->calculateDistance($demandPosition, $location);
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('demandPosition', GeopositionInterface::class, '', true);
        $this->registerArgument('location', GeopositionInterface::class, '', true);
    }
}
