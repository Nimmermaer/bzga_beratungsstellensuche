<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Unit\Domain\Serializer\NameConverter;

use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\NameConverter\EntryNameConverter;

class EntryNameConverterTest extends AbstractNameConverterTest
{
    protected function setUp(): void
    {
        $dispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->getMock();
        $dispatcher->method('dispatch')->willReturn(['extendedMapNames' => []]);
        $this->subject = new EntryNameConverter([], true, $dispatcher);
    }

    /**
     * @return array
     */
    public function dataProvider(): array
    {
        return [
            ['index', 'external_id'],
            ['titel', 'title'],
            ['untertitel', 'subtitle'],
            ['ansprechpartner', 'contact_person'],
            ['mapy', 'latitude'],
            ['mapx', 'longitude'],
            ['bundesland', 'state'],
            ['plz', 'zip'],
            ['ort', 'city'],
            ['logo', 'image'],
            ['strasse', 'street'],
            ['telefon', 'telephone'],
            ['fax', 'telefax'],
            ['email', 'email'],
            ['link', 'link'],
            ['traeger', 'institution'],
            ['website', 'website'],
            ['beratertelefon', 'hotline'],
            ['hinweistext', 'notice'],
            ['angebot', 'description'],
            ['verband', 'association'],
            ['beratungsart', 'categories'],
        ];
    }
}
