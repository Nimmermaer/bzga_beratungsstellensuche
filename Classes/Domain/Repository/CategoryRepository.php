<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Repository;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\Category;

/**
 * @author Sebastian Schreiber
 */
class CategoryRepository extends AbstractBaseRepository
{
    protected $objectType = Category::class;
}
