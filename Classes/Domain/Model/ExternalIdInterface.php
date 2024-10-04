<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Model;

/**
 * @author Sebastian Schreiber
 */
interface ExternalIdInterface
{
    /**
     * @return mixed
     */
    public function getExternalId():string;

    /**
     * @param mixed $externalId
     */
    public function setExternalId(string $externalId);
}
