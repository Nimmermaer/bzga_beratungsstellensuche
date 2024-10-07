<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Functional\Domain\Repository;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\Dto\Demand;
use Bzga\BzgaBeratungsstellensuche\Domain\Model\Entry;
use Bzga\BzgaBeratungsstellensuche\Domain\Repository\EntryRepository;
use Bzga\BzgaBeratungsstellensuche\Tests\Functional\DatabaseTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class EntryRepositoryTest extends FunctionalTestCase
{
    use DatabaseTrait;

    protected EntryRepository $entryRepository;

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
        $this->entryRepository = GeneralUtility::makeInstance(EntryRepository::class);

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/tx_bzgaberatungsstellensuche_domain_model_category.csv');
        // For some reason this data does not get loaded as csv. Try to fix this when updating for v12
        $this->importDataSet(__DIR__ . '/../../Fixtures/tx_bzgaberatungsstellensuche_domain_model_entry.xml');
    }

    /**
     * @test
     */
    public function findDemanded(): void
    {
        $demand = GeneralUtility::makeInstance(Demand::class);
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
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/sys_file_storage.csv');

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $this->entryRepository->deleteByUid(self::ENTRY_DEFAULT_FIXTURE_UID);
        self::assertEquals(0, $this->entryRepository->count(['uid' => self::ENTRY_DEFAULT_FIXTURE_UID]));
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
        parent::tearDown();
        unset($this->entryRepository);
    }
}
