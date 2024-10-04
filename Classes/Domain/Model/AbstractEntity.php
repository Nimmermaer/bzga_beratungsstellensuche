<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity as CoreAbstractEntity;

/**
 * @author Sebastian Schreiber
 */
abstract class AbstractEntity extends CoreAbstractEntity implements DummyInterface, ExternalIdInterface
{
    use DummyTrait;
    use ExternalIdTrait;

    protected string $title;

    public function __construct(string $title = '')
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = trim($title);
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }
}
