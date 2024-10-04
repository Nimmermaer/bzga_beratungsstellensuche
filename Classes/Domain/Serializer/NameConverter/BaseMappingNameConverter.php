<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Serializer\NameConverter;

use Bzga\BzgaBeratungsstellensuche\Events;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @author Sebastian Schreiber
 */
class BaseMappingNameConverter extends CamelCaseToSnakeCaseNameConverter
{
    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * Mapping of names, left side incoming names in xml|array, right side name for object
     * @var array
     */
    protected $mapNames = [
        'label' => 'title',
        'index' => 'external_id',
    ];

    /**
     * @var array
     */
    protected $mapNamesFlipped = [];

    /**
     * EntryNameConverter constructor.
     *
     * @param array|null $attributes
     * @param bool $lowerCamelCase
     * @param Dispatcher|null $signalSlotDispatcher
     */
    public function __construct(array $attributes = null, $lowerCamelCase = true, EventDispatcher $eventDispatcher = null)
    {
        parent::__construct($attributes, $lowerCamelCase);

        if (!$eventDispatcher instanceof EventDispatcher) {
            $eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
        }
        $event = new Events\Converter\EntryEvent($this->mapNames);
        $event = $eventDispatcher->dispatch($event);
        $this->mapNames = $event->getMapNames();
        $this->mapNamesFlipped();
    }

    private function mapNamesFlipped(): void
    {
        $this->mapNamesFlipped = array_flip($this->mapNames);
    }

    /**
     * @param array|string|null $propertyName
     * @return mixed|string|null
     */
    public function denormalize($propertyName): string
    {
        if (isset($this->mapNames[$propertyName])) {
            $propertyName = GeneralUtility::underscoredToLowerCamelCase($this->mapNames[$propertyName]);
        }

        return $propertyName;
    }
}
