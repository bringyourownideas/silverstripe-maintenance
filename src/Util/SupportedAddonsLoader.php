<?php

namespace BringYourOwnIdeas\Maintenance\Util;

/**
 * Handles fetching supported addon details from addons.silverstripe.org
 */
class SupportedAddonsLoader extends ApiLoader
{
    /**
     * Return the list of supported addons as provided by addons.silverstripe.org
     *
     * @return array
     */
    public function getAddonNames()
    {
        $endpoint = 'addons.silverstripe.org/api/supported-addons';
        return $this->doRequest($endpoint, function ($responseBody) {
            return isset($responseBody['addons']) ? $responseBody['addons'] : [];
        });
    }

    protected function getCacheKey()
    {
        return 'addons';
    }
}
