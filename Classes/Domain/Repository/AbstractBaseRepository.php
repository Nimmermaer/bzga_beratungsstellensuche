<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Repository;

use Doctrine\DBAL\Connection;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @author Sebastian Schreiber
 */
abstract class AbstractBaseRepository extends Repository
{
     protected EventDispatcherInterface $eventDispatcher;

    /**
     * @var array<non-empty-string, QueryInterface::ORDER_*>
     */
    protected $defaultOrderings = ['title' => QueryInterface::ORDER_ASCENDING];

    /**
     * @var string
     */
    public const ENTRY_TABLE = 'tx_bzgaberatungsstellensuche_domain_model_entry';

    /**
     * @var string
     */
    public const CATEGORY_TABLE = 'tx_bzgaberatungsstellensuche_domain_model_category';

    /**
     * @var string
     */
    public const ENTRY_CATEGORY_MM_TABLE = 'tx_bzgaberatungsstellensuche_entry_category_mm';

    /**
     * @var string
     */
    public const SYS_FILE_REFERENCE = 'sys_file_reference';

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function findOldEntriesByExternalUidsDiffForTable(string $table, array $entries): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);

        return $queryBuilder
            ->select('uid')
            ->from(self::ENTRY_TABLE)
            ->where($queryBuilder->expr()->notIn('external_id', $queryBuilder->createNamedParameter($entries, Connection::PARAM_INT_ARRAY)))
            ->execute()
            ->fetchAll();
    }

    public function countByExternalIdAndHash($externalId, string $hash): int
    {
        $query = $this->createQuery();
        $constraints = [];
        $constraints[] = $query->equals('externalId', $externalId);
        $constraints[] = $query->equals('hash', $hash);

        return $query->matching($query->logicalAnd(...$constraints))->execute()->count();
    }

    /**
     * @param mixed $externalId
     *
     * @return object|null
     */
    public function findOneByExternalId($externalId): ?object
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $object = $query->matching($query->equals('externalId', $externalId))->execute()->getFirst();

        return $object;
    }

    public function createQuery(): QueryInterface
    {
        $query = parent::createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query;
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    protected function getDatabaseConnectionForTable(string $table): \TYPO3\CMS\Core\Database\Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
                      ->getConnectionForTable($table);
    }
}
