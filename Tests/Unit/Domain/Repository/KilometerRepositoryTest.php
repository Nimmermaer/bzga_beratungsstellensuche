<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Unit\Domain\Repository;

use Bzga\BzgaBeratungsstellensuche\Domain\Repository\KilometerRepository;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class KilometerRepositoryTest extends UnitTestCase
{
    /**
     * @var KilometerRepository
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new KilometerRepository();
    }

    /**
     * @test
     */
    public function findKilometersBySettingsDefault(): void
    {
        self::assertSame([10 => '10', 20 => '20', 50 => '50', 100 => '100'], $this->subject->findKilometersBySettings([]));
    }

    /**
     * @test
     * @dataProvider kilometers
     */
    public function findKilometersByDefinedSettings($expected, $input): void
    {
        $settings = ['form' => ['kilometers' => $input]];
        self::assertSame($expected, $this->subject->findKilometersBySettings($settings));
    }

    /**
     * @return array
     */
    public function kilometers(): array
    {
        return [
            [
                [10 => '10', 20 => '20'],
                '10:10,20:20',
            ],
            [
                [10 => '10', 20 => '20'],
                '10:10, 20:20',
            ],
        ];
    }
}
