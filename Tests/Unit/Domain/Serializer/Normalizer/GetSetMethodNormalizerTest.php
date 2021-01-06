<?php

declare(strict_types=1);

namespace Bzga\BzgaBeratungsstellensuche\Tests\Unit\Domain\Serializer\Normalizer;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use Bzga\BzgaBeratungsstellensuche\Domain\Model\Entry;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\NameConverter\EntryNameConverter;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer\GetSetMethodNormalizer;
use SJBR\StaticInfoTables\Domain\Model\CountryZone;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @author Sebastian Schreiber
 */
class GetSetMethodNormalizerTest extends UnitTestCase
{

    /**
     * @var GetSetMethodNormalizer
     */
    protected $subject;

    /**
     * @var Dispatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $signalSlotDispatcher;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject\MockObject|SerializerNormalizer
     */
    protected $serializer;

    protected function setUp(): void
    {
        $this->signalSlotDispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->getMock();
        $this->serializer = $this->getMockForAbstractClass(SerializerNormalizer::class);
        $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->getMock();
        $dispatcher->method('dispatch')->willReturn(['extendedMapNames' => []]);
        $this->subject = new GetSetMethodNormalizer(null, new EntryNameConverter([], true, $dispatcher));
        $this->inject($this->subject, 'signalSlotDispatcher', $this->signalSlotDispatcher);
        $this->subject->setSerializer($this->serializer);
    }

    /**
     * @test
     */
    public function denormalizeEntryWithEntryNameConverter()
    {
        $latitude = (float)81;

        $data = [
            'mapy' => $latitude,
        ];
        $object = $this->subject->denormalize($data, 'Bzga\BzgaBeratungsstellensuche\Domain\Model\Entry');
        /* @var $object Entry */
        self::assertSame($latitude, $object->getLatitude());
    }

    /**
     * @test
     */
    public function denormalizeEntryWithEntryNameConverterAndStateCallback()
    {
        $countryZoneMock = $this->getMockBuilder(CountryZone::class)->getMock();

        $stateCallback = function ($bundesland) use ($countryZoneMock) {
            return $countryZoneMock;
        };

        $this->subject->setDenormalizeCallbacks(['state' => $stateCallback]);

        $data = [
            'bundesland' => 81,
        ];
        $object = $this->subject->denormalize($data, Entry::class);
        /* @var $object Entry */
        self::assertSame($countryZoneMock, $object->getState());
    }
}

abstract class SerializerNormalizer implements SerializerInterface, NormalizerInterface
{
}
