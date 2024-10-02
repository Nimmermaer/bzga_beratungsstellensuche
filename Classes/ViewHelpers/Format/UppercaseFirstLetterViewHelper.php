<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\ViewHelpers\Format;

use Closure;
use InvalidArgumentException;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * @author Sebastian Schreiber
 */
class UppercaseFirstLetterViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public static function renderStatic(array $arguments, Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $subject = $arguments['subject'];
        if ($subject === null) {
            $subject = $renderChildrenClosure();
        }
        if (! is_string($subject)) {
            throw new InvalidArgumentException('This is not a string');
        }
        $parts = array_filter(explode('_', $subject));
        $subjectParts = [];
        foreach ($parts as $part) {
            $subjectParts[] = ucfirst($part[0]) . substr($part, 1);
        }

        return implode('', $subjectParts);
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('subject', 'string', '', false, null);
    }
}
