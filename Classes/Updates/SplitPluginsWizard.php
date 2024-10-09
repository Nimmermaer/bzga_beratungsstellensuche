<?php

namespace Bzga\BzgaBeratungsstellensuche\Updates;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('MigrateBeratungstellensuchePlugin')]
class SplitPluginsWizard extends AbstractMigrationWizard implements UpgradeWizardInterface, ChattyInterface
{
    private const MIGRATION_SETTINGS = [
        [
            'switchableControllerActions' => 'Entry->list;Entry->show;Entry->autocomplete',
            'targetListType' => 'bzgaberatungsstellensuche_pi1',
        ],
        [
            'switchableControllerActions' => 'Entry->show',
            'targetListType' => 'bzgaberatungsstellensuche_detail',
        ],
        [
            'switchableControllerActions' =>  'Entry->form;Entry->autocomplete',
            'targetListType' => 'bzgaberatungsstellensuche_form',
        ],
    ];
    protected OutputInterface $output;
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }
    public function __construct(
        protected FlexFormService $flexFormService,
        protected FlexFormTools $flexFormTools,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return 'Beratungsstellensuche Plugin migration';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Migrate switchable controller Action plugins';
    }

    /**
     * @inheritDoc
     */
    public function executeUpdate(): bool
    {
        $records = $this->getPlugins();

        foreach ($records as $record) {
            $flexForm = $this->flexFormService->convertFlexFormContentToArray($record['pi_flexform']);
            $targetListType = $this->getTargetListType($flexForm['switchableControllerActions'] ?? '');

            if ($targetListType === '') {
                continue;
            }
            // Update record with migrated types (this is needed because FlexFormTools
            // looks up those values in the given record and assumes they're up-to-date)
            $record['list_type'] = $targetListType;

            // Clean up flexform
            $newFlexform = $this->flexFormTools->cleanFlexFormXML('tt_content', 'pi_flexform', $record);
            $flexFormData = GeneralUtility::xml2array($newFlexform);

            // Remove flexform data which do not exist in flexform of new plugin
            foreach ($flexFormData['data'] as $sheetKey => $sheetData) {
                // Remove empty sheets
                if (!count($flexFormData['data'][$sheetKey]['lDEF']) > 0) {
                    unset($flexFormData['data'][$sheetKey]);
                }
            }

            if (count($flexFormData['data']) > 0) {
                $newFlexform = $this->array2xml($flexFormData);
            } else {
                $newFlexform = '';
            }
            $this->updateContentElement($record['uid'], $targetListType, $newFlexform);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function updateNecessary(): bool
    {
        return true;
    }

    protected function getPlugins(): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('uid', 'pid', 'CType', 'list_type', 'pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter('list')
                ),
                $queryBuilder->expr()->eq(
                    'list_type',
                    $queryBuilder->createNamedParameter('bzgaberatungsstellensuche_pi1')
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }
    protected function getTargetListType(string $switchableControllerActions): string
    {
        foreach (self::MIGRATION_SETTINGS as $setting) {
            if ($setting['switchableControllerActions'] === $switchableControllerActions
            ) {
                return $setting['targetListType'];
            }
        }

        return '';
    }
    protected function array2xml(array $input = []): string
    {
        $options = [
            'parentTagMap' => [
                'data' => 'sheet',
                'sheet' => 'language',
                'language' => 'field',
                'el' => 'field',
                'field' => 'value',
                'field:el' => 'el',
                'el:_IS_NUM' => 'section',
                'section' => 'itemType',
            ],
            'disableTypeAttrib' => 2,
        ];
        $spaceInd = 4;
        $output = GeneralUtility::array2xml($input, '', 0, 'T3FlexForms', $spaceInd, $options);
        $output = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . LF . $output;
        return $output;
    }

    private function updateContentElement(mixed $uid, string $targetListType, string $newFlexform): void
    {
        $this->initializeUpdate();
        $data['tt_content'][$uid]['pi_flexform'] = $newFlexform;
        $data['tt_content'][$uid]['list_type'] = $targetListType;
        $this->dataHandler->start($data, []);
        $this->dataHandler->process_datamap();
        $this->dataHandler->clear_cacheCmd('all');
        $this->output->writeln('update plugin with uid ' . $uid);
    }
}
