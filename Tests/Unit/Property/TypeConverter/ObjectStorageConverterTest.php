<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Unit\Property\TypeConverter;

use Bzga\BzgaBeratungsstellensuche\Property\TypeConverter\ObjectStorageConverter;
use InvalidArgumentException;
use stdClass;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ObjectStorageConverterTest extends UnitTestCase
{
    /**
     * @var ObjectStorageConverter
     */
    protected $subject;

    /**
     * @return array
     */
    public function unsupportedSources(): array
    {
        return [
            [1],
            ['string'],
            [new stdClass()],
        ];
    }

    protected function setUp(): void
    {
        $this->subject = new ObjectStorageConverter();
    }

    /**
     * @param mixed $unsupportedSource
     * @test
     * @dataProvider unsupportedSources
     */
    public function isNotSupported($unsupportedSource): void
    {
        self::assertFalse($this->subject->supports($unsupportedSource));
    }

    /**
     * @param mixed $unsupportedSource
     * @test
     * @dataProvider unsupportedSources
     */
    public function convertThrowsException($unsupportedSource): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->subject->convert($unsupportedSource);
    }

    /**
     * @test
     */
    public function convertThrowsExceptionBecauseObjectStorageContainsUnsupportedItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $storage = new ObjectStorage();
        $storage->attach(new stdClass());
        $this->subject->convert($storage);
    }

    /**
     * @test
     */
    public function convertSuccessfully(): void
    {
        $entity1 = $this->getMockBuilder(DomainObjectInterface::class)->getMock();
        $entity1->method('getUid')->willReturn(1);
        $entity2 = $this->getMockBuilder(DomainObjectInterface::class)->getMock();
        $entity2->method('getUid')->willReturn(2);
        $storage = new ObjectStorage();
        $storage->attach($entity1);
        $storage->attach($entity2);
        self::assertEquals('1,2', $this->subject->convert($storage));
    }
}
