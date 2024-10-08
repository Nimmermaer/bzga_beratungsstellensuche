<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\Entry;
use Bzga\BzgaBeratungsstellensuche\Domain\Model\ValueObject\ImageLink;
use Bzga\BzgaBeratungsstellensuche\Domain\Repository\CategoryRepository;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\NameConverter\EntryNameConverter;
use SJBR\StaticInfoTables\Domain\Repository\CountryZoneRepository;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * @author Sebastian Schreiber
 */
class EntryNormalizer extends GetSetMethodNormalizer
{

    public function __construct(
        protected CategoryRepository             $categoryRepository,
        protected CountryZoneRepository          $countryZoneRepository,
        protected ?ClassMetadataFactoryInterface $classMetadataFactory = null
    )
    {
        parent::__construct($classMetadataFactory, new EntryNameConverter([], true));
    }

    /**
     * @param array|object $data
     */
    protected function prepareForDenormalization($data): array
    {
        $stateCallback = fn ($externalId) => $this->countryZoneRepository->findOneBy(['externalId' => $externalId]);

        $categoriesCallback = fn () => self::convertToObjectStorage($this->categoryRepository, func_get_args());

        $logoCallback = fn ($logo) => new ImageLink($logo);

        $this->setDenormalizeCallbacks(
            [
                'state' => $stateCallback,
                'categories' => $categoriesCallback,
                'image' => $logoCallback,
            ]
        );

        return parent::prepareForDenormalization($data);
    }

    public static function convertToObjectStorage(
        RepositoryInterface $repository,
        array $array,
        string $method = 'findOneByExternalId'
    ): ObjectStorage {
        $objectStorage = new ObjectStorage();
        if (is_array($array[0])) {
            foreach ($array[0] as $key => $item) {
                if (! is_array($item)) {
                    $item = (array)$item;
                }

                foreach ($item as $id) {
                    $object = $repository->{$method}($id);
                    if ($object !== null) {
                        $objectStorage->attach($object);
                    }
                }
            }
        }

        return $objectStorage;
    }


    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return $type === Entry::class;
    }
}
