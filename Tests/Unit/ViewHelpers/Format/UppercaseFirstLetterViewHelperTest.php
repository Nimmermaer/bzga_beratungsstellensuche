<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Unit\ViewHelpers\Format;

use Bzga\BzgaBeratungsstellensuche\ViewHelpers\Format\UppercaseFirstLetterViewHelper;
use Nimut\TestingFramework\TestCase\ViewHelperBaseTestcase;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\PhpUnit\ProphecyTrait;

class UppercaseFirstLetterViewHelperTest extends ViewHelperBaseTestcase
{
    use ProphecyTrait;
    /**
     * @var MockObject|UppercaseFirstLetterViewHelper
     */
    protected $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(UppercaseFirstLetterViewHelper::class)->setMethods(['renderChildren'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->subject);
    }

    /**
     * @test
     * @dataProvider validValuesProvider
     */
    public function renderWithRenderChildren($input, $expected): void
    {
        $this->setArgumentsUnderTest($this->subject);
        $this->subject->expects(self::once())->method('renderChildren')->willReturn($input);
        self::assertEquals($expected, $this->subject->render());
    }

    /**
     * @test
     */
    public function renderThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->setArgumentsUnderTest($this->subject, ['subject' => new \stdClass()]);
        $this->subject->render();
    }

    /**
     * @return array
     */
    public function validValuesProvider()
    {
        return [
            ['string', 'String'],
            ['motherAndChild', 'MotherAndChild'],
            ['extension_key_with', 'ExtensionKeyWith'],
        ];
    }
}
