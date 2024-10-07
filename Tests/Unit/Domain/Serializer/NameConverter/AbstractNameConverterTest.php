<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Unit\Domain\Serializer\NameConverter;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @author Sebastian Schreiber
 */
abstract class AbstractNameConverterTest extends UnitTestCase
{
    /**
     * @var NameConverterInterface
     */
    protected $subject;

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function denormalize($input, $expected): void
    {
        $expected = GeneralUtility::underscoredToLowerCamelCase($expected);
        $propertyName = $this->subject->denormalize($input);
        self::assertSame($expected, $propertyName);
    }

    abstract public function dataProvider();
}
