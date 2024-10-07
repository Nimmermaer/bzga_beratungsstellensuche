<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Unit\ViewHelpers;

use Bzga\BzgaBeratungsstellensuche\ViewHelpers\ExplodeViewHelper;
use Nimut\TestingFramework\TestCase\ViewHelperBaseTestcase;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\PhpUnit\ProphecyTrait;

class ExplodeViewHelperTest extends ViewHelperBaseTestcase
{
    use ProphecyTrait;
    /**
     * @var ExplodeViewHelper|MockObject
     */
    protected $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(ExplodeViewHelper::class)->setMethods(['renderChildren'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->subject);
    }

    /**
     * @test
     */
    public function renderWithoutRemovingEmptyValues(): void
    {
        $this->setRenderChildrenDefaultExpectation();
        $this->setArgumentsUnderTest($this->subject, [
            'subject' => null,
            'glue' => ',',
            'removeEmptyValues' => false,
            'valuesAsKeys' => false,
        ]);
        self::assertSame(['Title', '', 'Subject'], $this->subject->render());
    }

    /**
     * @test
     */
    public function renderWithRemovingEmptyValues(): void
    {
        $this->setRenderChildrenDefaultExpectation();
        $this->setArgumentsUnderTest($this->subject, [
            'subject' => null,
            'glue' => ',',
            'removeEmptyValues' => true,
            'valuesAsKeys' => false,
        ]);
        self::assertSame(['Title', 'Subject'], $this->subject->render());
    }

    /**
     * @test
     */
    public function renderWithRemovingEmptyValuesAndSettingsKeysAsValues(): void
    {
        $this->setRenderChildrenDefaultExpectation();
        $this->setArgumentsUnderTest($this->subject, [
            'subject' => null,
            'glue' => ',',
            'removeEmptyValues' => true,
            'valuesAsKeys' => true,
        ]);
        self::assertSame(['Title' => 'Title', 'Subject' => 'Subject'], $this->subject->render());
    }

    /**
     * @test
     */
    public function renderWithWrongSubjectType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->setArgumentsUnderTest($this->subject, ['subject' => new \stdClass()]);
        $this->subject->render();
    }

    private function setRenderChildrenDefaultExpectation(): void
    {
        $subject = 'Title,,Subject';
        $this->subject->expects(self::once())->method('renderChildren')->willReturn($subject);
    }
}
