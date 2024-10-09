<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Tests\Functional\Service\Importer;

use Bzga\BzgaBeratungsstellensuche\Service\Importer\XmlImporter;
use Bzga\BzgaBeratungsstellensuche\Tests\Functional\DatabaseTrait;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class XmlImporterTest extends FunctionalTestCase
{
    use DatabaseTrait;

    /**
     * @var string
     */
    private const SYS_FOLDER_FOR_ENTRIES = 10001;

    /**
     * @var XmlImporter
     */
    protected $subject;

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'fluid'];

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['packages/bzga_beratungsstellensuche', 'vendor/sjbr/static-info-tables', 'vendor/jambagecom/static-info-tables-de'];

    /**
     * @var array
     */
    protected $additionalFoldersToCreate = [
        'fileadmin/user_upload/tx_bzgaberatungsstellensuche',
    ];

    /**
     * To prevent some false/positive sql failures
     * @var array
     */
    protected $configurationToUseInTestInstance = [
        'SYS' => [
            'setDBinit' => 'SET SESSION sql_mode = \'\';',
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();
        $this->subject = $this->get(XmlImporter::class);

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/sys_file_storage.csv');
    }

    /**
     * @test
     */
    public function importFromFile(): void
    {
        $this->subject->importFromFile(
            'EXT:bzga_beratungsstellensuche/Tests/Functional/Fixtures/Import/beratungsstellen.xml',
            self::SYS_FOLDER_FOR_ENTRIES
        );

        foreach ($this->subject as $value) {
            $this->subject->importEntry($value);
        }
        $this->subject->persist();

        self::assertEquals(3, $this->selectCount('*', 'tx_bzgaberatungsstellensuche_domain_model_category'));
        self::assertEquals(2, $this->selectCount('*', 'tx_bzgaberatungsstellensuche_domain_model_entry'));
        self::assertEquals(4, $this->selectCount('*', 'tx_bzgaberatungsstellensuche_entry_category_mm'));

        self::assertEquals('Fachstelle für Sucht und Suchtprävention', $this->select('title', 'tx_bzgaberatungsstellensuche_domain_model_entry', 1));
        self::assertEquals('Frauen & Mädchen Beratungsstelle', $this->select('title', 'tx_bzgaberatungsstellensuche_domain_model_entry', 2));
        self::assertEquals('8.12345', $this->select('longitude', 'tx_bzgaberatungsstellensuche_domain_model_entry', 1));
        self::assertEquals('Online-Beratung', $this->select('title', 'tx_bzgaberatungsstellensuche_domain_model_category', 3));
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->subject);
    }
}
