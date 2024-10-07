<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Unit\Property;

use Bzga\BzgaBeratungsstellensuche\Property\PropertyMapper;
use Bzga\BzgaBeratungsstellensuche\Property\TypeConverterBeforeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PropertyMapperTest extends UnitTestCase
{
    /** @var MockObject|TypeConverterBeforeInterface $typeConverter */
    protected $typeConverter;

    /**
     * @var MockObject|PropertyMapper
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var MockObject|TypeConverterBeforeInterface $typeConverter */
        $this->typeConverter = $this->getMockBuilder(TypeConverterBeforeInterface::class)->getMock();
        $this->typeConverter->method('supports')->willReturn(true);
        $this->subject = new PropertyMapper([$this->typeConverter]);
    }

    /**
     * @test
     */
    public function supportsReturnsTypeConverter(): void
    {
        self::assertSame($this->typeConverter, $this->subject->supports($this->typeConverter));
    }

    /**
     * @test
     */
    public function convertSuccessfully(): void
    {
        $this->typeConverter->expects(self::once())->method('convert')->willReturn(true);
        self::assertTrue($this->subject->convert('array'));
    }
}
