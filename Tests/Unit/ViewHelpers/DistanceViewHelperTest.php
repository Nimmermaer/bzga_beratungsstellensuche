<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Unit\ViewHelpers;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\GeopositionInterface;
use Bzga\BzgaBeratungsstellensuche\Service\Geolocation\Decorator\GeolocationServiceCacheDecorator;
use Bzga\BzgaBeratungsstellensuche\ViewHelpers\DistanceViewHelper;
use Nimut\TestingFramework\TestCase\ViewHelperBaseTestcase;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\PhpUnit\ProphecyTrait;

class DistanceViewHelperTest extends ViewHelperBaseTestcase
{
    use ProphecyTrait;
    /**
     * @var GeolocationServiceCacheDecorator|MockObject
     */
    protected $geolocationService;

    /**
     * @var DistanceViewHelper|MockObject
     */
    protected $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->geolocationService = $this->getMockBuilder(GeolocationServiceCacheDecorator::class)->disableOriginalConstructor()->getMock();
        $this->subject = $this->getMockBuilder(DistanceViewHelper::class)->setMethods(['renderChildren'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->subject);

        $this->subject->injectGeolocationService($this->geolocationService);
    }

    /**
     * @test
     */
    public function render(): void
    {
        $this->geolocationService->expects(self::once())->method('calculateDistance')->willReturn(1);
        $demandPosition = $this->getMockBuilder(GeopositionInterface::class)->getMock();
        $location = $this->getMockBuilder(GeopositionInterface::class)->getMock();
        $this->subject->setArguments([
            'demandPosition' => $demandPosition,
            'location' => $location,
        ]);
        self::assertEquals(1, $this->subject->render());
    }
}
