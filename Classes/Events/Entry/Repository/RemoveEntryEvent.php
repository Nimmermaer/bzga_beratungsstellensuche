<?php

namespace Bzga\BzgaBeratungsstellensuche\Events\Entry\Repository;

use Bzga\BzgaBeratungsstellensuche\Domain\Repository\EntryRepository;

final class RemoveEntryEvent
{
    public function __construct(
        private EntryRepository $param,
        private int             $uid
    )
    {
    }

    public function getParam(): EntryRepository
    {
        return $this->param;
    }

    public function setParam(EntryRepository $param): void
    {
        $this->param = $param;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function setUid(int $uid): void
    {
        $this->uid = $uid;
    }

}
