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
trait DummyTrait
{
    protected bool $isDummyRecord = false;

    /**
     * @return bool
     */
    public function getIsDummyRecord(): bool
    {
        return $this->isDummyRecord;
    }

    /**
     * @param bool $isDummyRecord
     */
    public function setIsDummyRecord(bool $isDummyRecord): void
    {
        $this->isDummyRecord = $isDummyRecord;
    }
}
