<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Backend\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class BeratungsstellensucheFlexFormManipulation implements FormDataProviderInterface
{
    /**
     * Fields which are removed in detail view
     */
    private array $removedFieldsInDetailView = [
        'sDEF' => 'startingpoint,recursive',
        'additional' => 'listPid,list.itemsPerPage,formFields',
        'template' => '',
    ];

    /**
     * Fields which are removed in list view
     */
    private array $removedFieldsInListView = [
        'sDEF' => '',
        'additional' => '',
        'template' => '',
    ];

    /**
     * Fields which are remove in form view
     */
    private array $removedFieldsInFormView = [
        'sDEF' => 'startingpoint,recursive',
        'additional' => 'singlePid,backPid,list.itemsPerPage',
        'template' => '',
    ];

    private function updateFlexforms(array $result): array
    {
        $selectedView = '';

        $row = $result['databaseRow'];
        $dataStructure = $result['processedTca']['columns']['pi_flexform']['config']['ds'];

        // Modify the flexform structure depending on the first found plugin
        switch ($row['list_type']) {
            case 'bzgaberatungsstellensuche_pi1':
                $dataStructure = $this->deleteFromStructure($dataStructure, $this->removedFieldsInListView);
                break;
            case 'bzgaberatungsstellensuche_detail':
                $dataStructure = $this->deleteFromStructure($dataStructure, $this->removedFieldsInDetailView);
                break;
            case 'bzgaberatungsstellensuche_form':
                $dataStructure = $this->deleteFromStructure($dataStructure, $this->removedFieldsInFormView);
                break;
            default:
        }

        if ($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche']['Hooks/BackendUtility.php']['updateFlexforms'] ?? false) {
            $params = [
                'selectedView' => $row['list_type'],
                'dataStructure' => &$dataStructure,
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['bzga_beratungsstellensuche']['Hooks/BackendUtility.php']['updateFlexforms'] as $reference) {
                GeneralUtility::callUserFunction($reference, $params, $this);
            }
        }

        $result['processedTca']['columns']['pi_flexform']['config']['ds'] = $dataStructure;

        return $result;
    }

    private function deleteFromStructure(array $dataStructure, array $fieldsToBeRemoved): array
    {
        foreach ($fieldsToBeRemoved as $sheetName => $sheetFields) {
            $fieldsInSheet = GeneralUtility::trimExplode(',', $sheetFields, true);
            foreach ($fieldsInSheet as $fieldName) {
                unset($dataStructure['sheets'][$sheetName]['ROOT']['el']['settings.' . $fieldName]);
            }
        }

        return $dataStructure;
    }

    public function addData(array $result): array
    {
        if ($result['tableName'] === 'tt_content'
            && $result['databaseRow']['CType'] === 'list'
            && str_contains(haystack: $result['databaseRow']['list_type'], needle: 'bzgaberatungsstellensuche_')
            && is_array($result['processedTca']['columns']['pi_flexform']['config']['ds'])
        ) {
            $result = $this->updateFlexForms($result);
        }

        return $result;
    }
}
