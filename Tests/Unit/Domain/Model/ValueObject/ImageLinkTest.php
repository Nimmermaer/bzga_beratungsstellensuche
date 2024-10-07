<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Unit\Domain\Model\ValueObject;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\ValueObject\ImageLink;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ImageLinkTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getCorrectIdentifierFromExternalUrl(): void
    {
        $identifier = '13e430b77537205400cfdc56aec80fcd';
        $subject    = new ImageLink('http://www.domain.com/path/to/image/pix.php?id=' . $identifier);
        self::assertSame($identifier, $subject->getIdentifier());
    }
}
