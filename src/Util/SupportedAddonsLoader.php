<?php

namespace BringYourOwnIdeas\Maintenance\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Convert;
use BringYourOwnIdeas\Maintenance\Model\Package;
use SilverStripe\Core\Injector\Injector;

/**
 * Handles fetching supported addon details from addons.silverstripe.org
 */
class SupportedAddonsLoader
{
    use Extensible;

    private static $dependencies = [
        'GuzzleClient' => '%$GuzzleHttp\Client',
    ];

    /**
     * @var Client
     */
    protected $guzzleClient;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @return Client
     */
    public function getGuzzleClient()
    {
        return $this->guzzleClient;
    }

    /**
     * @param Client $guzzleClient
     * @return $this
     */
    public function setGuzzleClient(Client $guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
        return $this;
    }

    /**
     * @return CacheInterface
     */
    public function getCache()
    {
        if (!$this->cache) {
            $this->cache = Injector::inst()->get(CacheInterface::class . '.supportedAddons');
        }

        return $this->cache;
    }

    /**
     * @param CacheInterface $cache
     * @return $this
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Return the list of supported addons as provided by addons.silverstripe.org
     *
     * @return array
     */
    public function getAddonNames()
    {
        if (($addons = $this->getCache()->get('addons')) !== false) {
            return Convert::json2array($addons) ?: [];
        }

        return $this->doRequest();
    }

    /**
     * Perform an HTTP request for supported addon names
     *
     * @return array
     * @throws RuntimeException When the API responds with something that's not a list of addons
     */
    protected function doRequest()
    {
        $request = new Request('GET', 'addons.silverstripe.org/api/supported-addons');

        $failureMessage = 'Could not obtain information about supported addons. ';

        try {
            $response = $this->getGuzzleClient()->send($request, ['http_errors' => false]);
        } catch (GuzzleException $exception) {
            throw new RuntimeException($failureMessage);
        }

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException($failureMessage . 'Error code ' . $response->getStatusCode());
        }

        if (!in_array('application/json', $response->getHeader('Content-Type'))) {
            throw new RuntimeException($failureMessage . 'Response is not JSON');
        }

        $responseBody = Convert::json2array($response->getBody()->getContents());

        if (empty($responseBody)) {
            throw new RuntimeException($failureMessage . 'Response could not be parsed');
        }

        if (!isset($responseBody['success']) || !$responseBody['success'] || !isset($responseBody['addons'])) {
            throw new RuntimeException($failureMessage . 'Response returned unsuccessfully');
        }

        // Handle caching if requested
        if ($cacheControl = $response->getHeader('Cache-Control')) {
            // Combine separate header rows
            $cacheControl = implode(', ', $cacheControl);

            if (strpos($cacheControl, 'no-store') === false &&
                preg_match('/(?:max-age=)(\d+)/i', $cacheControl, $matches)) {
                $duration = (int) $matches[1];
                $serializedData = Convert::raw2json($responseBody['addons']);
                $this->getCache()->set('addons', $serializedData, $duration);
            }
        }

        return $responseBody['addons'] ?: [];
    }

    /**
     * Create a request with some standard headers
     *
     * @param string $uri
     * @param string $method
     * @return Request
     */
    protected function createRequest($uri, $method = 'GET')
    {
        $headers = [];
        $version = $this->resolveVersion();
        if (!empty($version)) {
            $headers['Silverstripe-Framework-Version'] = $version;
        }

        return new Request($method, $uri, $headers);
    }

    /**
     * Resolve the framework version of SilverStripe.
     *
     * @return string|null
     */
    protected function resolveVersion()
    {
        $frameworkPackage = Package::get()->find('Name', 'silverstripe/framework');
        if (!$frameworkPackage) {
            return null;
        }
        return $frameworkPackage->Version;
    }
}
