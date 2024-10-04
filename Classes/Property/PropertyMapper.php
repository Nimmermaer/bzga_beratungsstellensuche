<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Property;

use Bzga\BzgaBeratungsstellensuche\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @author Sebastian Schreiber
 */
class PropertyMapper implements TypeConverterInterface
{
    /**
     * @var TypeConverterInterface[]
     */
    private array $typeConverters;

    public function __construct(?array $typeConverters = null)
    {
        $this->typeConverters = $typeConverters ?? $this->initializeTypeConverters();
    }

    /**
     * @inheritDoc
     */
    public function supports($source, string $type = TypeConverterInterface::CONVERT_BEFORE)
    {
        foreach ($this->typeConverters as $typeConverter) {
            if ($typeConverter->supports($source, $type) === true && $this->converterSupportsType(
                $typeConverter,
                $type
            )
            ) {
                return $typeConverter;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function convert($source, array $configuration = null)
    {
        foreach ($this->typeConverters as $typeConverter) {
            if ($typeConverter->supports($source) === true) {
                return $typeConverter->convert($source, $configuration);
            }
        }

        return $source;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getRegisteredTypeConverters(): array
    {
        return ExtensionManagementUtility::getRegisteredTypeConverters();
    }

    private function converterSupportsType(TypeConverterInterface $typeConverter, string $type): bool
    {
        $interfaces = class_implements($typeConverter);
        switch ($type) {
            case TypeConverterInterface::CONVERT_AFTER:
                $className = TypeConverterAfterInterface::class;
                break;
            default:
                $className = TypeConverterBeforeInterface::class;
                break;
        }
        return in_array($className, $interfaces, true) ? true : false;
    }

    /**
     * @return TypeConverterInterface[]
     */
    private function initializeTypeConverters(): array
    {
        $typeConverters = [];
        foreach ($this->getRegisteredTypeConverters() as $typeConverterClassName) {
            $typeConverters[] = GeneralUtility::makeInstance($typeConverterClassName);
        }
        return $typeConverters;
    }
}
