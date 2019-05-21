<?php

namespace Bzga\BzgaBeratungsstellensuche\Service\Geolocation\HttpAdapter;

/**
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
use Ivory\HttpAdapter\AbstractCurlHttpAdapter;
use Ivory\HttpAdapter\ConfigurationInterface;
use Ivory\HttpAdapter\Extractor\ProtocolVersionExtractor;
use Ivory\HttpAdapter\Extractor\StatusCodeExtractor;
use Ivory\HttpAdapter\HttpAdapterException;
use Ivory\HttpAdapter\Message\InternalRequestInterface;
use Ivory\HttpAdapter\Message\RequestInterface;
use Ivory\HttpAdapter\Normalizer\BodyNormalizer;
use Ivory\HttpAdapter\Normalizer\HeadersNormalizer;

/**
 * @author Sebastian Schreiber
 */
class CurlHttpAdapter extends AbstractCurlHttpAdapter
{
    /**
     * Creates a curl http adapter.
     *
     * @param \Ivory\HttpAdapter\ConfigurationInterface|null $configuration The configuration.
     */
    public function __construct(ConfigurationInterface $configuration = null)
    {
        parent::__construct($configuration);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'curl';
    }

    /**
     * {@inheritdoc}
     */
    protected function sendInternalRequest(InternalRequestInterface $internalRequest)
    {
        $curl = $this->createCurl($internalRequest);

        try {
            $response = $this->createResponse($curl, curl_exec($curl), $internalRequest);
        } catch (HttpAdapterException $e) {
            curl_close($curl);

            throw $e;
        }

        curl_close($curl);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function sendInternalRequests(array $internalRequests, $success, $error)
    {
        $curlMulti = curl_multi_init();

        $contexts = [];
        foreach ($internalRequests as $internalRequest) {
            $contexts[] = [
                'curl' => $curl = $this->createCurl($internalRequest),
                'request' => $internalRequest,
            ];

            curl_multi_add_handle($curlMulti, $curl);
        }

        do {
            do {
                $exec = curl_multi_exec($curlMulti, $running);
            } while ($exec === CURLM_CALL_MULTI_PERFORM);

            while ($done = curl_multi_info_read($curlMulti)) {
                $curl = $done['handle'];
                $internalRequest = $this->resolveInternalRequest($curl, $contexts);

                try {
                    $response = $this->createResponse($curl, curl_multi_getcontent($curl), $internalRequest);
                    $response = $response->withParameter('request', $internalRequest);
                    call_user_func($success, $response);
                } catch (HttpAdapterException $e) {
                    $e->setRequest($internalRequest);
                    call_user_func($error, $e);
                }

                curl_multi_remove_handle($curlMulti, $curl);
                curl_close($curl);
            }
        } while ($running);

        curl_multi_close($curlMulti);
    }

    /**
     * Creates a curl resource.
     *
     * @param \Ivory\HttpAdapter\Message\InternalRequestInterface $internalRequest The internal request.
     *
     * @return resource The curl resource.
     */
    private function createCurl(InternalRequestInterface $internalRequest)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, (string)$internalRequest->getUri());
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, $this->prepareProtocolVersion($internalRequest));
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->prepareHeaders($internalRequest, false, false));

        if ($GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_host'] && $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_port']) {
            $curlProxyHost = sprintf(
                '%s:%s',
                $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_host'],
                $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_port']
            );
            curl_setopt($curl, CURLOPT_PROXY, $curlProxyHost);
            if ($GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_password'] && $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_user']) {
                $curlProxyUserPass = sprintf(
                    '%s:%s',
                    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_password'],
                    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_user']
                );
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, $curlProxyUserPass);
            }
            if ($GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme']) {
                $curlProxyAuthScheme = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'] == 'basic' ? CURLAUTH_BASIC : CURLAUTH_DIGEST;
                curl_setopt($curl, CURLOPT_PROXYAUTH, $curlProxyAuthScheme);
            }
        }

        $this->configureTimeout($curl, 'CURLOPT_TIMEOUT');
        $this->configureTimeout($curl, 'CURLOPT_CONNECTTIMEOUT');

        $files = $internalRequest->getFiles();

        if (!empty($files) && $this->isSafeUpload()) {
            curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
        }

        switch ($internalRequest->getMethod()) {
            case RequestInterface::METHOD_HEAD:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $internalRequest->getMethod());
                curl_setopt($curl, CURLOPT_NOBODY, true);
                break;

            case RequestInterface::METHOD_TRACE:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $internalRequest->getMethod());
                break;

            case RequestInterface::METHOD_POST:
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->prepareContent($internalRequest));
                break;

            case RequestInterface::METHOD_PUT:
            case RequestInterface::METHOD_PATCH:
            case RequestInterface::METHOD_DELETE:
            case RequestInterface::METHOD_OPTIONS:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $internalRequest->getMethod());
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->prepareContent($internalRequest));
                break;
        }

        return $curl;
    }

    /**
     * Configures a timeout.
     *
     * @param resource $curl The curl resource.
     * @param string $type The timeout type.
     */
    private function configureTimeout($curl, $type)
    {
        if (defined($type . '_MS')) {
            curl_setopt($curl, constant($type . '_MS'), $this->getConfiguration()->getTimeout() * 1000);
        } else { // @codeCoverageIgnoreStart
            curl_setopt($curl, constant($type), $this->getConfiguration()->getTimeout());
        } // @codeCoverageIgnoreEnd
    }

    /**
     * Creates a response.
     *
     * @param resource $curl The curl resource.
     * @param bool|string|null $data The data.
     * @param \Ivory\HttpAdapter\Message\InternalRequestInterface $internalRequest The internal request.
     *
     * @throws \Ivory\HttpAdapter\HttpAdapterException If an error occurred.
     *
     * @return \Ivory\HttpAdapter\Message\ResponseInterface The response.
     */
    private function createResponse($curl, $data, InternalRequestInterface $internalRequest)
    {
        if (empty($data)) {
            throw HttpAdapterException::cannotFetchUri(
                (string)$internalRequest->getUri(),
                $this->getName(),
                curl_error($curl)
            );
        }

        $headers = substr($data, 0, $headersSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE));

        return $this->getConfiguration()->getMessageFactory()->createResponse(
            StatusCodeExtractor::extract($headers),
            ProtocolVersionExtractor::extract($headers),
            HeadersNormalizer::normalize($headers),
            BodyNormalizer::normalize(substr($data, $headersSize), $internalRequest->getMethod())
        );
    }

    /**
     * Resolves the internal request.
     *
     * @param resource $curl The curl resource.
     * @param array $contexts The contexts.
     *
     * @return \Ivory\HttpAdapter\Message\InternalRequestInterface The internal request.
     */
    private function resolveInternalRequest($curl, array $contexts)
    {
        foreach ($contexts as $context) {
            if ($context['curl'] === $curl) {
                break;
            }
        }
        if (!isset($context['request'])) {
            throw new \UnexpectedValueException('No context with request could be found');
        }

        return $context['request'];
    }
}
