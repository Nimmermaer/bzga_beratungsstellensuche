<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Repository;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\Dto\Demand;
use Bzga\BzgaBeratungsstellensuche\Domain\Model\Entry;
use Bzga\BzgaBeratungsstellensuche\Domain\Model\GeopositionInterface;
use Bzga\BzgaBeratungsstellensuche\Events;
use Bzga\BzgaBeratungsstellensuche\Events\Repository\RemoveEntryEvent;
use Bzga\BzgaBeratungsstellensuche\Service\Geolocation\Decorator\GeolocationServiceCacheDecorator;
use Bzga\BzgaBeratungsstellensuche\Service\Geolocation\GeolocationService;
use RuntimeException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * @author Sebastian Schreiber
 */
class EntryRepository extends AbstractBaseRepository
{
    /**
     * @var GeolocationServiceCacheDecorator
     */
    protected $geolocationService;

    /**
     * @var Typo3DbQueryParser
     */
    protected $queryParser;

    public function injectGeolocationService(GeolocationServiceCacheDecorator $geolocationService): void
    {
        $this->geolocationService = $geolocationService;
    }

    public function injectQueryParser(Typo3DbQueryParser $queryParser): void
    {
        $this->queryParser = $queryParser;
    }

    public function findByQuery(string $q)
    {
        $query = $this->createQuery();
        return $query->matching($query->logicalOr(
            $query->like('zip', $q . '%'),
            $query->like('city', $q . '%'),
        ))->execute();
    }

    public function findDemanded(Demand $demand)
    {
        $query = $this->createQuery();
        $constraints = $this->createCoordsConstraints($demand, $query, $demand->getKilometers());

        if ($keywords = $demand->getKeywords()) {
            $searchFields = GeneralUtility::trimExplode(',', $demand->getSearchFields(), true);
            $searchConstraints = [];

            if (count($searchFields) === 0) {
                throw new \UnexpectedValueException('No search fields defined', 1_318_497_755);
            }

            $keywordsArray = GeneralUtility::trimExplode(' ', $keywords);
            foreach ($keywordsArray as $keyword) {
                foreach ($searchFields as $field) {
                    $searchConstraints[] = $query->like($field, '%' . $keyword . '%');
                }
            }

            if (count($searchConstraints)) {
                $constraints[] = $query->logicalOr(...$searchConstraints);
            }
        }

        if ($demand->getCategories()->count() > 0) {
            $categoryConstraints = [];
            foreach ($demand->getCategories() as $category) {
                $categoryConstraints[] = $query->contains('categories', $category);
            }
            if (! empty($categoryConstraints)) {
                $constraints[] = $query->logicalOr(...$categoryConstraints);
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

        if (! empty($constraints) && is_array($constraints)) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        // Bug. Counting is wrong in TYPO3 Version 8 Doctrine, if we do not use custom statement here. Why?
        if (! method_exists(Typo3DbQueryParser::class, 'preparseQuery')) {
            $queryBuilder = $this->queryParser->convertQueryToDoctrineQueryBuilder($query);
            $queryParameters = $queryBuilder->getParameters();
            $params = [];
            foreach ($queryParameters as $key => $value) {
                // prefix array keys with ':'
                $params[':' . $key] = is_numeric($value) ? $value : "'" . $value . "'"; //all non numeric values have to be quoted
                unset($params[$key]);
            }

            // replace placeholders with real values
            return $query->statement(strtr($queryBuilder->getSQL(), $params))->execute();
        }

        return $query->execute();
    }

    public function truncateAll(): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::ENTRY_TABLE);
        $entries = $queryBuilder->select('uid')->from(self::ENTRY_TABLE)->execute()->fetchAll();
        foreach ($entries as $entry) {
            $this->deleteByUid($entry['uid']);
        }
        $event = new Events\Repository\TruncateAllEvent($this);
        $this->eventDispatcher->dispatch($event);

    }

    private function createCoordsConstraints(
        GeopositionInterface $userLocation,
        QueryInterface $query,
        int $radius = GeolocationService::DEFAULT_RADIUS
    ): array {
        if (! $userLocation->getLatitude() || ! $userLocation->getLongitude()) {
            return [];
        }
        if ($radius === 0) {
            return [];
        }

        $earthRadius = GeolocationService::EARTH_RADIUS;

        $lowestLat = (double)$userLocation->getLatitude() - rad2deg($radius / $earthRadius);
        $highestLat = (double)$userLocation->getLatitude() + rad2deg($radius / $earthRadius);
        $lowestLng = (double)$userLocation->getLongitude() - rad2deg(asin($radius / $earthRadius) / cos(deg2rad($userLocation->getLatitude())));
        $highestLng = (double)$userLocation->getLongitude() + rad2deg(asin($radius / $earthRadius) / cos(deg2rad($userLocation->getLatitude())));

        return [
            $query->greaterThanOrEqual('latitude', $lowestLat),
            $query->lessThanOrEqual('latitude', $highestLat),
            $query->greaterThanOrEqual('longitude', $lowestLng),
            $query->lessThanOrEqual('longitude', $highestLng),
        ];
    }

    public function deleteByUid(int $uid): void
    {
        /** @var FileRepository $fileRepository */
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);

        /** @var FileReference[] $fileReferences */
        $fileReferences = $fileRepository->findByRelation(self::ENTRY_TABLE, 'image', $uid);
        foreach ($fileReferences as $fileReference) {
            try {
                $fileDeleted = $fileReference->getOriginalFile()->delete();
            } catch (RuntimeException $e) {
            }
        }

        // @cascade remove not working the expected way
        $this->getDatabaseConnectionForTable(self::ENTRY_CATEGORY_MM_TABLE)->delete(self::ENTRY_CATEGORY_MM_TABLE, ['uid_local' => (int)$uid]);

        $entry = $this->findByIdentifier($uid);
        if ($entry instanceof Entry) {
            $this->remove($entry);
            $this->persistenceManager->persistAll();
        }
        $event = new RemoveEntryEvent($this, $uid);
        $this->eventDispatcher->dispatch($event);
    }
}
