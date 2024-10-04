<?php

namespace Bzga\BzgaBeratungsstellensuche\Events\Serializer;

final class NormalizerEvent
{

    public function __construct(
       protected array $normalizer
    )
    {
    }

    public function setSignalArguments(array $signalArguments): void
    {
        $this->normalizer = array_merge($signalArguments, $this->normalizer);
    }

    /**
     * @return array
     */
    public function getNormalizer(): array
    {
        return $this->normalizer;
    }

}
