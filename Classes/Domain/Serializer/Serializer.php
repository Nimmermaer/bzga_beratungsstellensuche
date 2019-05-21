<?php


namespace Bzga\BzgaBeratungsstellensuche\Domain\Serializer;

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
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer\EntryNormalizer;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer\GetSetMethodNormalizer;
use Bzga\BzgaBeratungsstellensuche\Events;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Serializer as BaseSerializer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * @author Sebastian Schreiber
 */
class Serializer extends BaseSerializer
{

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * Serializer constructor.
     * @param array $normalizers
     * @param array $encoders
     */
    public function __construct(array $normalizers = [], array $encoders = [])
    {
        if (empty($normalizers)) {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            /* @var $objectManager ObjectManager */
            $normalizers = [
                $objectManager->get(EntryNormalizer::class),
                $objectManager->get(GetSetMethodNormalizer::class),
            ];
        }
        if (empty($encoders)) {
            $encoders = [
                new XmlEncoder('beratungsstellen'),
            ];
        }

        // @TODO Working with DI
        if (!$this->signalSlotDispatcher instanceof Dispatcher) {
            $this->signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        }

        $normalizers = $this->emitAdditionalNormalizersSignal($normalizers);

        parent::__construct($normalizers, $encoders);
    }

    /**
     * @param array $normalizers
     * @return array
     */
    private function emitAdditionalNormalizersSignal(array $normalizers)
    {
        $signalArguments = [];
        $signalArguments['extendedNormalizers'] = [];

        $additionalNormalizers = $this->signalSlotDispatcher->dispatch(
            static::class,
            Events::ADDITIONAL_NORMALIZERS_SIGNAL,
            $signalArguments
        );

        return array_merge($normalizers, $additionalNormalizers['extendedNormalizers']);
    }
}
