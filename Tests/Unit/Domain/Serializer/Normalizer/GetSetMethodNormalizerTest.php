<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Unit\Domain\Serializer\Normalizer;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\Entry;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\NameConverter\EntryNameConverter;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer\GetSetMethodNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @var Dispatcher|MockObject
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
        $this->subject->injectSignalSlotDispatcher($this->signalSlotDispatcher);
        $this->subject->setSerializer($this->serializer);
    }

    /**
     * @test
     */
    public function denormalizeEntryWithEntryNameConverter(): void
    {
        $latitude = (float)81;

        $data = [
            'mapy' => $latitude,
        ];
        $object = $this->subject->denormalize($data, Entry::class);
        /* @var $object Entry */
        self::assertSame($latitude, $object->getLatitude());
    }

    /**
     * @test
     */
    public function denormalizeEntryWithEntryNameConverterAndStateCallback(): void
    {
        $countryZoneMock = $this->getMockBuilder(CountryZone::class)->getMock();

        $stateCallback = fn ($bundesland) => $countryZoneMock;

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
