<?php


namespace Bzga\BzgaBeratungsstellensuche\ViewHelpers;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use InvalidArgumentException;
use Traversable;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @author Sebastian Schreiber
 */
class ImplodeViewHelper extends AbstractViewHelper
{
    public function render(): string
    {
        $pieces = $this->arguments['pieces'];
        $glue = $this->arguments['glue'];
        if (null === $pieces) {
            $pieces = $this->renderChildren();
        }
        if (! is_array($pieces) && ! $pieces instanceof Traversable) {
            throw new InvalidArgumentException('The value is not of type array or not implementing the Traversable interface');
        }
        // This is only working with objects implementing __toString method
        if ($pieces instanceof Traversable) {
            $pieces = iterator_to_array($pieces);
            $this->validatePieces($pieces);
        }
        return implode($glue, $pieces);
    }

    private function validatePieces(array $pieces): void
    {
        foreach ($pieces as $piece) {
            if (! method_exists($piece, '__toString') && ! is_scalar($piece)) {
                throw new InvalidArgumentException('The provided value must be of type scalar or implementing the __toString method');
            }
        }
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('pieces', 'mixed', '', false, null);
        $this->registerArgument('glue', 'string', '', false, ',');
    }
}
