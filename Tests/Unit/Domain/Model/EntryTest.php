<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Unit\Domain\Model;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\Entry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class EntryTest extends UnitTestCase
{
    /**
     * @var Entry
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new Entry();
    }

    /**
     * @test
     */
    public function getAddress(): void
    {
        $address = 'Zip City, Street';
        $this->subject->setCity('City');
        $this->subject->setZip('Zip');
        $this->subject->setStreet('Street');
        self::assertEquals($address, $this->subject->getAddress());
    }

    /**
     * @test
     */
    public function getInfoWindowWithoutLink(): void
    {
        $this->subject->setTitle('Title');
        $this->subject->setCity('City');
        $this->subject->setZip('Zip');
        $this->subject->setStreet('Street');
        $infoWindow = '<p><strong>Title</strong><br>Street<br>Zip City</p>';
        self::assertEquals($infoWindow, $this->subject->getInfoWindow());
    }

    /**
     * @test
     */
    public function getInfoWindowWithLink(): void
    {
        $this->subject->setTitle('Title');
        $this->subject->setCity('City');
        $this->subject->setZip('Zip');
        $this->subject->setStreet('Street');
        $infoWindow = '<p><strong><a href="http://domain.com">Title</a></strong><br>Street<br>Zip City</p>';
        self::assertEquals($infoWindow, $this->subject->getInfoWindow(['detailLink' => 'http://domain.com']));
    }

    /**
     * @test
     */
    public function stringCastingEntityReturnsTitle(): void
    {
        $this->subject->setTitle('Title');
        self::assertEquals('Title', (string)$this->subject);
    }
}
