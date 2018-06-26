<?php

namespace BringYourOwnIdeas\Maintenance\Util;

use BringYourOwnIdeas\Maintenance\Model\Package;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;

/**
 * Handles fetching supported addon details from addons.silverstripe.org
 */
abstract class ApiLoader
{
    use Extensible;

    private static $dependencies = [
        'GuzzleClient' => '%$' . Client::class,
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
     * Define a unique cache key for results to be saved for each request (subclass)
     *
     * @return string
     */
    abstract protected function getCacheKey();

    /**
     * Perform an HTTP request for module health information
     *
     * @param string $endpoint API endpoint to check for results
     * @param callable $callback Function to return the result of after loading the API data
     * @return array
     * @throws RuntimeException When the API responds with something that's not module health information
     */
    public function doRequest($endpoint, callable $callback)
    {
        // Check for a cached value and return if one is available
        if ($result = $this->getFromCache()) {
            return $result;
        }

        // Otherwise go and request data from the API
        $request = new Request('GET', $endpoint);
        $failureMessage = 'Could not obtain information about module. ';

        try {
            /** @var Response $response */
            $response = $this->getGuzzleClient()->send($request, $this->getClientOptions());
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

        if (!isset($responseBody['success']) || !$responseBody['success']) {
            throw new RuntimeException($failureMessage . 'Response returned unsuccessfully');
        }

        // Allow callback to handle processing of the response body
        $result = $callback($responseBody);

        // Setting the value to the cache for subsequent requests
        $this->handleCacheFromResponse($response, $result);

        return $result;
    }

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
     * Get Guzzle client options
     *
     * @return array
     */
    public function getClientOptions()
    {
        $options = [
            'http_errors' => false,
        ];
        $this->extend('updateClientOptions', $options);
        return $options;
    }

    /**
     * Attempts to load something from the cache and deserializes from JSON if successful
     *
     * @return array|bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function getFromCache()
    {
        $cacheKey = $this->getCacheKey();
        $result = $this->getCache()->get($cacheKey, false);
        if ($result === false) {
            return false;
        }

        // Deserialize JSON object and return as an array
        return Convert::json2array($result);
    }

    /**
     * Given a value, set it to the cache with the given key after serializing the value as JSON
     *
     * @param string $cacheKey
     * @param mixed $value
     * @param int $ttl
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function setToCache($cacheKey, $value, $ttl = null)
    {
        // Seralize as JSON to ensure array etc can be stored
        $value = Convert::raw2json($value);

        return $this->getCache()->set($cacheKey, $value, $ttl);
    }

    /**
     * Check the API response for cache control headers and respect them internally in the SilverStripe
     * cache if found
     *
     * @param Response $response
     * @param array|string $result
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function handleCacheFromResponse(Response $response, $result)
    {
        // Handle caching if requested
        if ($cacheControl = $response->getHeader('Cache-Control')) {
            // Combine separate header rows
            $cacheControl = implode(', ', $cacheControl);

            if (strpos($cacheControl, 'no-store') === false
                && preg_match('/(?:max-age=)(\d+)/i', $cacheControl, $matches)
            ) {
                $duration = (int) $matches[1];

                $cacheKey = $this->getCacheKey();
                $this->setToCache($cacheKey, $result, $duration);
            }
        }
    }

    /**
     * @return CacheInterface
     */
    public function getCache()
    {
        if (!$this->cache) {
            $this->cache = Injector::inst()->get(CacheInterface::class . '.silverstripeMaintenance');
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
