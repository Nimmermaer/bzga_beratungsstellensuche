<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Hooks;

use Bzga\BzgaBeratungsstellensuche\Utility\FormFields;
use Bzga\BzgaBeratungsstellensuche\Utility\TemplateLayout;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @author Sebastian Schreiber
 */
class ItemsProcFunc
{
    public function user_templateLayout(array &$config): void
    {
        $templateLayoutsUtility = GeneralUtility::makeInstance(TemplateLayout::class);
        /** @var TemplateLayout $templateLayoutsUtility */
        $templateLayouts = $templateLayoutsUtility->getAvailableTemplateLayouts($config['effectivePid']);
        foreach ($templateLayouts as $layout) {
            $additionalLayout  = [
                htmlspecialchars($GLOBALS['LANG']->sL($layout[0])),
                $layout[1],
            ];
            $config['items'][] = $additionalLayout;
        }
    }

    public function user_formFields(array &$config): void
    {
        $formFieldsUtility = GeneralUtility::makeInstance(FormFields::class);
        /** @var FormFields $formFieldsUtility */
        $formFields = $formFieldsUtility->getAvailableFormFields();
        foreach ($formFields as $formField) {
            $additionalFormField = [
                htmlspecialchars($GLOBALS['LANG']->sL($formField[0])),
                $formField[1],
            ];
            $config['items'][]   = $additionalFormField;
        }
    }
}
