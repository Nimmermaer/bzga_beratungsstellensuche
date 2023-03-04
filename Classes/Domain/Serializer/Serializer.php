<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Serializer;

use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer\EntryNormalizer;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer\GetSetMethodNormalizer;
use Bzga\BzgaBeratungsstellensuche\Events;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Serializer as BaseSerializer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * @author Sebastian Schreiber
 */
class Serializer extends BaseSerializer
{
    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    public function __construct(array $normalizers = [], array $encoders = [], Dispatcher $signalSlotDispatcher = null)
    {
        if (empty($normalizers)) {
            $normalizers = [
                GeneralUtility::makeInstance(EntryNormalizer::class),
                GeneralUtility::makeInstance(GetSetMethodNormalizer::class),
            ];
        }
        if (empty($encoders)) {
            $encoders = [
                new XmlEncoder(['beratungsstellen']),
            ];
        }

        $this->signalSlotDispatcher = $signalSlotDispatcher ?? GeneralUtility::makeInstance(Dispatcher::class);

        $normalizers = $this->emitAdditionalNormalizersSignal($normalizers);

        parent::__construct($normalizers, $encoders);
    }

    /**
     * @param array $normalizers
     */
    private function emitAdditionalNormalizersSignal(array $normalizers): array
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
