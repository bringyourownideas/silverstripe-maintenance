<?php

namespace BringYourOwnIdeas\Maintenance\Util;

use Convert;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Object;
use RuntimeException;

/**
 * Handles fetching supported addon details from addons.silverstripe.org
 */
class SupportedAddonsLoader extends Object
{
    private static $dependencies = [
        'GuzzleClient' => '%$GuzzleHttp\Client',
    ];

    /**
     * @var Client
     */
    protected $guzzleClient;

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
     * Return the list of supported addons as provided by addons.silverstripe.org
     *
     * @return array
     */
    public function getAddonNames()
    {
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

        return $responseBody['addons'] ?: [];
    }
}
