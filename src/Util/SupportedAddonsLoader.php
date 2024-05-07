<?php

namespace BringYourOwnIdeas\Maintenance\Util;

/**
 * Handles fetching supported addon details from silverstripe/supported-modules
 */
class SupportedAddonsLoader extends ApiLoader
{
    /**
     * Return the list of supported modules
     *
     * @return array
     */
    public function getAddonNames()
    {
        $endpoint = 'https://raw.githubusercontent.com/silverstripe/supported-modules/5/modules.json';
        return $this->doRequest($endpoint, function ($responseJson) {
            return array_map(fn(array $item) => $item['composer'], $responseJson);
        });
    }

    protected function getCacheKey()
    {
        return 'addons';
    }
}
