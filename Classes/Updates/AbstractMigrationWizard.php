<?php

declare(strict_types=1);

namespace Bzga\BzgaBeratungsstellensuche\Updates;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;

abstract class AbstractMigrationWizard
{
    protected BackendUserAuthentication $backendUser;

    protected ?DataHandler $dataHandler = null;

    protected ?FlexFormService $flexformService = null;

    public function getPrerequisites(): array
    {
        return [DatabaseUpdatedPrerequisite::class];
    }

    protected function initializeUpdate(): void
    {
        if ($GLOBALS['BE_USER'] === null) {
            $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
            $serverRequest = ServerRequestFactory::fromGlobals();
            $normalizedParams = GeneralUtility::makeInstance(
                NormalizedParams::class,
                $serverRequest->getServerParams(),
                $GLOBALS['TYPO3_CONF_VARS']['SYS'],
                Environment::getCurrentScript(),
                Environment::getPublicPath()
            );
            $serverRequest = $serverRequest->withAttribute('normalizedParams', $normalizedParams);
            $GLOBALS['BE_USER']->start($serverRequest);
        }

        if ($GLOBALS['TYPO3_REQUEST'] ?? true) {
            $GLOBALS['TYPO3_REQUEST'] = self::createFakeWebRequest(GeneralUtility::getIndpEnv('DDEV_PRIMARY_URL'));
        }

        Bootstrap::initializeBackendAuthentication();
        Bootstrap::initializeLanguageObject();

        $this->backendUser = $GLOBALS['BE_USER'];

        $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        $this->flexformService = GeneralUtility::makeInstance(FlexFormService::class);
    }

    protected function createFakeWebRequest(string $backendUrl): ServerRequestInterface
    {
        $uri = new \TYPO3\CMS\Core\Http\Uri($backendUrl);
        $request = new ServerRequest(
            $uri,
            'GET',
            'php://input',
            [],
            [
                'HTTP_HOST' => $uri->getHost(),
                'SERVER_NAME' => $uri->getHost(),
                'HTTPS' => $uri->getScheme() === 'https',
                'SCRIPT_FILENAME' => __FILE__,
                'SCRIPT_NAME' => rtrim($uri->getPath(), '/') . '/',
            ]
        );
        $backedUpEnvironment = $this->simulateEnvironmentForBackendEntryPoint();
        $normalizedParams = NormalizedParams::createFromRequest($request);

        // Restore the environment
        Environment::initialize(
            Environment::getContext(),
            Environment::isCli(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            $backedUpEnvironment['currentScript'],
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );

        return $request
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    protected function simulateEnvironmentForBackendEntryPoint(): array
    {
        $currentEnvironment = Environment::toArray();
        Environment::initialize(
            Environment::getContext(),
            Environment::isCli(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            // This is ugly, as this change fakes the directory
            dirname(Environment::getCurrentScript(), 4) . DIRECTORY_SEPARATOR . 'index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        return $currentEnvironment;
    }
}
