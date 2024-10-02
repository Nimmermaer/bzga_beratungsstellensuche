<?php

namespace Bzga\BzgaBeratungsstellensuche\Events\Entry\Repository;

use Bzga\BzgaBeratungsstellensuche\Domain\Repository\AbstractBaseRepository;

final class TruncateAllEvent
{
    public function __construct(
        private AbstractBaseRepository $abstractBaseRepository
    )
    {
    }

    public function getAbstractBaseRepository(): AbstractBaseRepository
    {
        return $this->abstractBaseRepository;
    }

    public function setAbstractBaseRepository(AbstractBaseRepository $abstractBaseRepository): void
    {
        $this->abstractBaseRepository = $abstractBaseRepository;
    }
}
