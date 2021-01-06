<?php

declare(strict_types=1);

namespace Bzga\BzgaBeratungsstellensuche\Tests\Functional\Domain\Repository;

/*
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
use Bzga\BzgaBeratungsstellensuche\Domain\Model\Entry;
use Bzga\BzgaBeratungsstellensuche\Domain\Repository\EntryRepository;
use Bzga\BzgaBeratungsstellensuche\Tests\Functional\DatabaseTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class EntryRepositoryTest extends FunctionalTestCase
{
    use DatabaseTrait;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var EntryRepository
     */
    protected $entryRepository;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/bzga_beratungsstellensuche', 'typo3conf/ext/static_info_tables'];

    /**
     * @var array
     */
    protected $pathsToLinkInTestInstance = [
        'typo3conf/ext/bzga_beratungsstellensuche/Tests/Functional/Fixtures/Files/fileadmin/user_upload' => 'fileadmin/user_upload',
    ];

    private const ENTRY_DEFAULT_FIXTURE_UID = 1;

    public function setUp(): void
    {
        parent::setUp();
        GeneralUtility::writeFile(__DIR__ . '/../../Fixtures/Files/fileadmin/user_upload/claim.png', '');
        $this->objectManager   = GeneralUtility::makeInstance(ObjectManager::class);
        $this->entryRepository = $this->objectManager->get(EntryRepository::class);

        $this->importDataSet(__DIR__ . '/../../Fixtures/tx_bzgaberatungsstellensuche_domain_model_category.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/tx_bzgaberatungsstellensuche_domain_model_entry.xml');
    }

    /**
     * @test
     */
    public function findDemanded(): void
    {
        /** @var Demand $demand */
        $demand = $this->objectManager->get(Demand::class);
        $demand->setKeywords('Keyword');
        $entries = $this->entryRepository->findDemanded($demand);
        self::assertEquals(self::ENTRY_DEFAULT_FIXTURE_UID, $this->getIdListOfItems($entries));
    }

    /**
     * @test
     */
    public function countByExternalIdAndHash(): void
    {
        self::assertEquals(1, $this->entryRepository->countByExternalIdAndHash(1, '32dwwes8'));
    }

    /**
     * @test
     */
    public function findOneByExternalId(): void
    {
        /** @var Entry $entry */
        $entry = $this->entryRepository->findOneByExternalId(1);
        self::assertEquals($entry->getUid(), self::ENTRY_DEFAULT_FIXTURE_UID);
    }

    /**
     * @test
     */
    public function deleteByUid(): void
    {
        $this->importDataSet(__DIR__ . '/../../Fixtures/sys_file_storage.xml');

        $this->setUpBackendUserFromFixture(1);
        $this->entryRepository->deleteByUid(self::ENTRY_DEFAULT_FIXTURE_UID);
        self::assertEquals(0, $this->entryRepository->countByUid(self::ENTRY_DEFAULT_FIXTURE_UID));
        self::assertEquals(
            0,
            $this->selectCount(
                '*',
                'tx_bzgaberatungsstellensuche_entry_category_mm',
                'uid_local = ' . self::ENTRY_DEFAULT_FIXTURE_UID
            )
        );
        self::assertEquals(
            0,
            $this->selectCount(
                '*',
                'sys_file_reference',
                'deleted = 0 AND fieldname = "image" AND tablenames = "tx_bzgaberatungsstellensuche_domain_model_entry" AND uid_foreign = ' . self::ENTRY_DEFAULT_FIXTURE_UID
            )
        );
        self::assertEquals(
            0,
            $this->selectCount(
                '*',
                'sys_file_metadata',
                'file = 10014'
            )
        );
        self::assertEquals(
            0,
            $this->selectCount(
                '*',
                'sys_file',
                'uid = 10014'
            )
        );
    }

    /**
     * @test
     */
    public function findOldEntriesByExternalUidsDiffForTable(): void
    {
        $oldEntries      = $this->entryRepository->findOldEntriesByExternalUidsDiffForTable(
            'tx_bzgaberatungsstellensuche_domain_model_entry',
            [1]
        );
        $expectedEntries = [
            [
                'uid' => 2,
            ],
        ];
        self::assertEquals($expectedEntries, $oldEntries);
    }

    protected function getIdListOfItems(QueryResultInterface $items): string
    {
        $idList = [];
        foreach ($items as $item) {
            $idList[] = $item->getUid();
        }

        return implode(',', $idList);
    }

    public function tearDown(): void
    {
        unset($this->entryRepository, $this->objectManager);
    }
}
