<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Report;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * @author Sebastian Schreiber
 */
class StatusAllowUrlFopenOrCurlReport implements StatusProviderInterface
{
    /**
     * @var string
     */
    public const MESSAGE = 'allow_url_fopen must be on or curl must be enabled to allow
				communication between TYPO3 and the remote Server to fetch the XML-Url.';

    public const TITLE = 'Status allow url fopen or curl report';

    public function getStatus(): array
    {
        $reports = [];
        $severity = \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::OK;
        $value = 'On';
        $message = '';

        // @TODO: Do we need extra proxy configuration check too?
        if (!ini_get('allow_url_fopen') && !$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse']) {
            $severity = \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR;
            $value = 'Off';
            $message = self::MESSAGE;
        }

        $reports[] = GeneralUtility::makeInstance(
            Status::class,
            'allow_url_fopen on or curl is enabled',
            $value,
            $message,
            $severity
        );

        return $reports;
    }

    public function getLabel(): string
    {
       return self::TITLE;
    }
}
