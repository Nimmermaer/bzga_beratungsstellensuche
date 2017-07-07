<?php

namespace Bzga\BzgaBeratungsstellensuche\Domain\Repository;

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
use Bzga\BzgaBeratungsstellensuche\Domain\Model\Dto\Demand;
use Bzga\BzgaBeratungsstellensuche\Domain\Model\GeopositionInterface;
use Bzga\BzgaBeratungsstellensuche\Events;
use Bzga\BzgaBeratungsstellensuche\Service\Geolocation\GeolocationService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * @author Sebastian Schreiber
 */
class EntryRepository extends AbstractBaseRepository
{

    /**
     * @var \Bzga\BzgaBeratungsstellensuche\Service\Geolocation\Decorator\GeolocationServiceCacheDecorator
     * @inject
     */
    protected $geolocationService;

    /**
     * @param Demand $demand
     * @return array|QueryResultInterface
     */
    public function findDemanded(Demand $demand)
    {
        $query = $this->createQuery();
        $constraints = $this->createCoordsConstraints($demand, $query, $demand->getKilometers());

        if ($keywords = $demand->getKeywords()) {
            $searchFields = GeneralUtility::trimExplode(',', $demand->getSearchFields(), true);
            $searchConstraints = [];

            if (count($searchFields) === 0) {
                throw new \UnexpectedValueException('No search fields defined', 1318497755);
            }

            $keywordsArray = GeneralUtility::trimExplode(' ', $keywords);
            foreach ($keywordsArray as $keyword) {
                foreach ($searchFields as $field) {
                    $searchConstraints[] = $query->like($field, '%' . $keyword . '%');
                }
            }

            if (count($searchConstraints)) {
                $constraints[] = $query->logicalOr($searchConstraints);
            }
        }

        if ($demand->getCategories()->count() > 0) {
            $categoryConstraints = [];
            foreach ($demand->getCategories() as $category) {
                $categoryConstraints[] = $query->contains('categories', $category);
            }
            if (!empty($categoryConstraints)) {
                $constraints[] = $query->logicalOr($categoryConstraints);
            }
        }

        if ($demand->getCountryZone()) {
            $constraints[] = $query->equals('state', $demand->getCountryZone());
        }

        // Call hook functions for additional constraints
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche']['Domain/Repository/EntryRepository.php']['findDemanded'])) {
            $params = [
                'demand' => $demand,
                'query' => $query,
                'constraints' => &$constraints,
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche']['Domain/Repository/EntryRepository.php']['findDemanded'] as $reference) {
                GeneralUtility::callUserFunction($reference, $params, $this);
            }
        }

        if (!empty($constraints) && is_array($constraints)) {
            $query->matching($query->logicalAnd($constraints));
        }

        return $query->execute();
    }

    /**
     * @param GeopositionInterface $userLocation
     * @param QueryInterface $query
     * @param int $radius
     * @return array
     */
    private function createCoordsConstraints(
        GeopositionInterface $userLocation,
        QueryInterface $query,
        $radius = GeolocationService::DEFAULT_RADIUS
    ) {
        if (!$userLocation->getLatitude() || !$userLocation->getLatitude()) {
            return [];
        }

        $earthRadius = GeolocationService::EARTH_RADIUS;

        $lowestLat = (double)$userLocation->getLatitude() - rad2deg($radius / $earthRadius);
        $highestLat = (double)$userLocation->getLatitude() + rad2deg($radius / $earthRadius);
        $lowestLng = (double)$userLocation->getLongitude() - rad2deg($radius / $earthRadius);
        $highestLng = (double)$userLocation->getLongitude() + rad2deg($radius / $earthRadius);

        return [
            $query->greaterThanOrEqual('latitude', $lowestLat),
            $query->lessThanOrEqual('latitude', $highestLat),
            $query->greaterThanOrEqual('longitude', $lowestLng),
            $query->lessThanOrEqual('longitude', $highestLng),
        ];
    }

    /**
     * Here we delete all relations of an entry, this is not possible with the convenient remove method of this repository class
     *
     * @param $uid
     */
    public function deleteByUid($uid)
    {
        if (MathUtility::canBeInterpretedAsInteger($uid)) {
            $databaseConnection = $this->getDatabaseConnection();

            $fileReferences = $databaseConnection->exec_SELECTgetRows('uid, uid_local', self::SYS_FILE_REFERENCE,
                'table_local = "sys_file" AND fieldname = "image" AND tablenames = "tx_bzgaberatungsstellensuche_domain_model_entry" AND uid_foreign = ' . $uid);

            foreach ($fileReferences as $fileReference) {
                $falFile = ResourceFactory::getInstance()->getFileObject($fileReference['uid_local']);
                $falFile->getStorage()->deleteFile($falFile);
                $databaseConnection->exec_DELETEquery(self::SYS_FILE_REFERENCE, 'uid = ' . $fileReference['uid']);
            }

            $databaseConnection->exec_DELETEquery(self::ENTRY_TABLE, 'uid = ' . $uid);
            $databaseConnection->exec_DELETEquery(
                self::ENTRY_CATEGORY_MM_TABLE,
                'uid_local =' . $uid
            );

            $this->signalSlotDispatcher->dispatch(static::class, Events::REMOVE_ENTRY_FROM_DATABASE_SIGNAL,
                ['databaseConnection' => $databaseConnection]);
        }
    }
}
