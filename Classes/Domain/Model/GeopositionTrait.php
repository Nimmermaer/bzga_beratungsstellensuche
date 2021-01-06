<?php

declare(strict_types=1);

namespace Bzga\BzgaBeratungsstellensuche\Domain\Model;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * @author Sebastian Schreiber
 */
trait GeopositionTrait
{
    /**
     * @var float
     */
    protected $longitude = 0.0;

    /**
     * @var float
     */
    protected $latitude = 0.0;

    public function getLongitude(): float
    {
        return (float)$this->longitude;
    }

    /**
     * @param mixed $longitude
     */
    public function setLongitude($longitude): void
    {
        $this->longitude = (float)$longitude;
    }

    public function getLatitude(): float
    {
        return (float)$this->latitude;
    }

    /**
     * @param mixed $latitude
     */
    public function setLatitude($latitude): void
    {
        $this->latitude = (float)$latitude;
    }
}
