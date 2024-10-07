<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Unit\Domain\Serializer;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\Category;
use Bzga\BzgaBeratungsstellensuche\Domain\Model\Entry;
use Bzga\BzgaBeratungsstellensuche\Domain\Repository\CategoryRepository;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\NameConverter\BaseMappingNameConverter;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer\EntryNormalizer;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer\GetSetMethodNormalizer;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Serializer;
use PHPUnit\Framework\MockObject\MockObject;
use SJBR\StaticInfoTables\Domain\Model\CountryZone;
use SJBR\StaticInfoTables\Domain\Repository\CountryZoneRepository;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @author Sebastian Schreiber
 */
class SerializerTest extends UnitTestCase
{
    /**
     * @var Serializer
     */
    protected $subject;

    /**
     * @var CategoryRepository|MockObject
     */
    protected $categoryRepository;

    /**
     * @var CountryZoneRepository
     */
    protected $countryZoneRepository;

    /**
     * @var EntryNormalizer
     */
    protected $entryNormalizer;

    /**
     * @var Dispatcher|MockObject
     */
    protected $signalSlotDispatcher;

    protected function setUp(): void
    {
        $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->getMock();
        $dispatcher->method('dispatch')->willReturn(['extendedMapNames' => []]);
        $this->entryNormalizer = new EntryNormalizer(null, $dispatcher);
        $this->resetSingletonInstances = true;
        $this->signalSlotDispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->getMock();
        $this->signalSlotDispatcher->method('dispatch')->willReturn(['extendedNormalizers' => []]);
        $this->countryZoneRepository = $this->getMockBuilder(CountryZoneRepository::class)->setMethods(['findOneByExternalId'])->disableOriginalConstructor()->getMock();
        $this->categoryRepository = $this->getMockBuilder(CategoryRepository::class)->setMethods(['findOneByExternalId'])->disableOriginalConstructor()->getMock();

        $this->entryNormalizer->injectSignalSlotDispatcher($this->signalSlotDispatcher);
        $this->entryNormalizer->injectCategoryRepository($this->categoryRepository);
        $this->entryNormalizer->injectCountryZoneRepository($this->countryZoneRepository);

        $normalizers = [
            $this->entryNormalizer,
            new GetSetMethodNormalizer(null, new BaseMappingNameConverter([], true, $dispatcher)),
        ];
        $this->subject = new Serializer($normalizers, [], $this->signalSlotDispatcher);
    }

    /**
     * @test
     */
    public function deserializeCategoryFromXml(): void
    {
        $xml = '<beratungsart><index>1</index><sort>2</sort><label>persönliche Beratung</label></beratungsart>';
        $object = $this->subject->deserialize($xml, Category::class, 'xml');
        /* @var $object Category */
        self::assertSame('persönliche Beratung', $object->getTitle());
        self::assertSame(1, (integer)$object->getExternalId());
    }

    /**
     * @test
     * @dataProvider xmlProvider
     */
    public function deserializeEntryFromXml($xml): void
    {
        $categoryMock = $this->getMockBuilder(Category::class)->getMock();
        $this->categoryRepository->expects(self::any())->method('findOneByExternalId')->willReturn($categoryMock);

        $countryZoneMock = $this->getMockBuilder(CountryZone::class)->getMock();
        $this->countryZoneRepository->expects(self::any())->method('findOneByExternalId')->willReturn($countryZoneMock);

        $object = $this->subject->deserialize($xml, Entry::class, 'xml');
        /* @var $object Entry */
        self::assertSame(
            'Gesundheitsamt Uelzen, Lüchow-Dannenberg, Schwangerschaftskonfliktberatungsstelle',
            $object->getTitle()
        );
        self::assertSame(1858, (integer)$object->getExternalId());
    }

    /**
     * @return array
     */
    public function xmlProvider()
    {
        $xml = '<entry>
            <index>1858</index>
            <titel>Gesundheitsamt Uelzen, Lüchow-Dannenberg, Schwangerschaftskonfliktberatungsstelle</titel>
            <untertitel>Schwangerschaftskonfliktberatungsstelle</untertitel>
            <ansprechpartner></ansprechpartner>
            <link>http://www.bzga-rat.de/referat/famplan/minisite/?idx=1858</link>
            <kurztext></kurztext>
            <plz>29439</plz>
            <ort>Lüchow</ort>
            <bundesland>9</bundesland>
            <strasse>Königsberger Straße 10</strasse>
            <mapx>11.1546438</mapx>
            <mapy>52.9705095</mapy>
            <telefon>05841 120476</telefon>
            <fax>05841 120479</fax>
            <email>r.hoeber-ramlow@gesundheitsamt-ue-dan.de</email>
            <traeger>LandkreisGesundheitsamt Uelzen-Lüchow-Dannenberg</traeger>
            <website>www.luechow-dannenberg.de</website>
            <beratertelefon>05841 120476</beratertelefon>
            <hinweistext>Hinweistext</hinweistext>
            <beratungsschein>1</beratungsschein>
            <angebot></angebot>
            <logo></logo>
            <beratungsart>
                <index>2</index>
                <index>1</index>
            </beratungsart>
            <verband>Kommunale / Freie Land Niedersachsen</verband>
            <kontaktform>0</kontaktform>
            <kontaktemail></kontaktemail>
            <suchcontent>Gesundheitsamt Uelzen  Lüchow Dannenberg  Schwangerschaftskonfliktberatungsstelle</suchcontent>
        </entry>';

        return [
            [$xml],
        ];
    }
}
