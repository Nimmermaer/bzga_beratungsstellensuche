<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Unit\Persistence\Mapper;

use Bzga\BzgaBeratungsstellensuche\Persistence\Mapper\DataMap;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap as CoreDataMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @author Sebastian Schreiber
 */
class DataMapTest extends UnitTestCase
{
    /**
     * @var DataMap
     */
    protected $subject;

    /**
     * @var DataMapFactory|MockObject
     */
    protected $dataMapFactory;

    protected function setUp(): void
    {
        $this->dataMapFactory = $this->getMockBuilder(DataMapFactory::class)->disableOriginalConstructor()->getMock();
        $this->subject = new DataMap($this->dataMapFactory);
    }

    /**
     * @test
     */
    public function getTableNameByClassNameCalledOnceForSameClassName(): void
    {
        $expectedTableName = 'tablename';
        $dataMap = $this->getMockBuilder(CoreDataMap::class)->disableOriginalConstructor()->getMock();
        $this->dataMapFactory->expects(self::once())->method('buildDataMap')->willReturn($dataMap);
        $dataMap->expects(self::once())->method('getTableName')->willReturn($expectedTableName);
        for ($i = 0; $i <= 5; $i++) {
            $tableName = $this->subject->getTableNameByClassName(self::class);
        }
        self::assertSame($expectedTableName, $tableName);
    }
}
