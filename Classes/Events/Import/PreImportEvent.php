<?php

namespace Bzga\BzgaBeratungsstellensuche\Events\Import;

use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Serializer;
use Bzga\BzgaBeratungsstellensuche\Service\Importer\AbstractImporter;

final class PreImportEvent
{
    public function __construct(
        private AbstractImporter $importer,
        private \SimpleXMLIterator $simpleXMLIterator,
        private int                $pid,
        private Serializer         $serializer,
    )
    {
    }

    public function getImporter(): AbstractImporter
    {
        return $this->importer;
    }

    public function setImporter(AbstractImporter $importer): void
    {
        $this->importer = $importer;
    }

    public function getSimpleXMLIterator(): \SimpleXMLIterator
    {
        return $this->simpleXMLIterator;
    }

    public function setSimpleXMLIterator(\SimpleXMLIterator $simpleXMLIterator): void
    {
        $this->simpleXMLIterator = $simpleXMLIterator;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    public function getSerializer(): Serializer
    {
        return $this->serializer;
    }

    public function setSerializer(Serializer $serializer): void
    {
        $this->serializer = $serializer;
    }

}
