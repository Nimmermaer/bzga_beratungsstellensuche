<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Manager;

use Bzga\BzgaBeratungsstellensuche\Domain\Repository\AbstractBaseRepository;
use Bzga\BzgaBeratungsstellensuche\Domain\Repository\EntryRepository;

/**
 * @author Sebastian Schreiber
 */
class EntryManager extends AbstractManager
{
    /**
     * @var EntryRepository
     */
    protected $repository;

    public function getRepository(): AbstractBaseRepository
    {
        return $this->repository;
    }

    public function injectRepository(EntryRepository $repository): void
    {
        $this->repository = $repository;
    }
}
