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
use Bzga\BzgaBeratungsstellensuche\Domain\Model\ValueObject\ImageLink;
use Bzga\BzgaBeratungsstellensuche\Domain\Repository\CategoryRepository;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer\EntryNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use SJBR\StaticInfoTables\Domain\Model\CountryZone;
use SJBR\StaticInfoTables\Domain\Repository\CountryZoneRepository;
use Symfony\Component\Serializer\SerializerInterface;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @author Sebastian Schreiber
 */
class EntryNormalizerTest extends UnitTestCase
{
    /**
     * @var EntryNormalizer
     */
    protected $subject;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var CountryZoneRepository|MockObject
     */
    protected $countryZoneRepository;

    /**
     * @var CategoryRepository|MockObject
     */
    protected $categoryRepository;

    /**
     * @var Dispatcher|MockObject
     */
    protected $signalSlotDispatcher;

    protected function setUp(): void
    {
        $this->signalSlotDispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->getMock();
        $this->countryZoneRepository = $this->getMockBuilder(CountryZoneRepository::class)->setMethods(['findOneByExternalId'])->disableOriginalConstructor()->getMock();
        $this->categoryRepository = $this->getMockBuilder(CategoryRepository::class)->setMethods(['findOneByExternalId'])->disableOriginalConstructor()->getMock();
        $this->serializer = $this->getMockForAbstractClass(SerializerNormalizer::class);

        $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->getMock();
        $dispatcher->method('dispatch')->willReturn(['extendedMapNames' => []]);
        $this->subject = new EntryNormalizer(null, $dispatcher);
        $this->subject->setSerializer($this->serializer);
        $this->subject->injectSignalSlotDispatcher($this->signalSlotDispatcher);
        $this->subject->injectCategoryRepository($this->categoryRepository);
        $this->subject->injectCountryZoneRepository($this->countryZoneRepository);
    }

    /**
     * @test
     */
    public function denormalizeEntryWithEntryNameConverter(): void
    {
        $data = [
            'bundesland' => 2,
            'traeger' => 'Institution',
            'titel' => 'Title',
            'untertitel' => 'Subtitle',
            'ansprechpartner' => 'Contact Person',
            'mapy' => 'Latitude',
            'mapx' => 'Longitude',
            'kurztext' => 'Teaser',
            'plz' => 'Zip',
            'ort' => 'City',
            'logo' => 'https://www.domain.com/logo.png',
            'strasse' => 'Street',
            'telefon' => 'Telephone',
            'fax' => 'Telefax',
            'email' => 'Email',
            'link' => 'Link',
            'website' => 'Website',
            'beratertelefon' => 'Hotline',
            'hinweistext' => 'Notice',
            'beratungsschein' => 1,
            'angebot' => 'Description',
            'verband' => 'Association',
            'kontaktemail' => 'Contact email',
            'suchcontent' => 'Keywords',
            'beratungsart' => [],
        ];
        $countryZoneMock = $this->getMockBuilder(CountryZone::class)->getMock();
        $this->countryZoneRepository->expects(self::once())->method('findOneByExternalId')->willReturn($countryZoneMock);

        $object = $this->subject->denormalize($data, Entry::class);
        /* @var $object Entry */
        self::assertSame($countryZoneMock, $object->getState());
        self::assertSame('Institution', $object->getInstitution());
        self::assertSame('Title', $object->getTitle());
        self::assertSame('Subtitle', $object->getSubtitle());
        self::assertSame('Contact Person', $object->getContactPerson());
        self::assertSame(0.0, $object->getLatitude());
        self::assertSame(0.0, $object->getLongitude());
        self::assertSame('Zip', $object->getZip());
        self::assertSame('City', $object->getCity());
        self::assertInstanceOf(ImageLink::class, $object->getImage());
        self::assertSame('Street', $object->getStreet());
        self::assertSame('Telephone', $object->getTelephone());
        self::assertSame('Telefax', $object->getTelefax());
        self::assertSame('Email', $object->getEmail());
        self::assertSame('Website', $object->getWebsite());
        self::assertSame('Hotline', $object->getHotline());
        self::assertSame('Notice', $object->getNotice());
        self::assertSame('Description', $object->getDescription());
        self::assertSame('Association', $object->getAssociation());
    }
}
