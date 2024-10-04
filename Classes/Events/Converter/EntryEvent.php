<?php

namespace Bzga\BzgaBeratungsstellensuche\Events\Converter;

use TYPO3\CMS\Core\Utility\ArrayUtility;

final class EntryEvent
{
    public function __construct(
        public array $mapNames,
    )
    {
    }

    public function getMapNames(): array
    {
        return $this->mapNames;
    }

    public function setNewMapNames(array $newMapNames): void
    {
        ArrayUtility::mergeRecursiveWithOverrule($this->mapNames, $newMapNames);
    }


}
