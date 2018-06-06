<?php

namespace BringYourOwnIdeas\Maintenance\Util;

/**
 * Handles fetching module health information from addons.silverstripe.org
 */
class ModuleHealthLoader extends ApiLoader
{
    /**
     * @var string[]
     */
    protected $moduleNames = [];

    /**
     * Return the list of supported addons as provided by addons.silverstripe.org
     *
     * @return array
     */
    public function getModuleHealthInfo()
    {
        $addons = $this->getModuleNames();
        $endpoint = 'addons.silverstripe.org/api/ratings?addons=' . implode(',', $addons);
        return $this->doRequest($endpoint, function ($responseBody) {
            return isset($responseBody) ? $responseBody['ratings'] : [];
        });
    }

    /**
     * @return string[]
     */
    public function getModuleNames()
    {
        return $this->moduleNames;
    }

    /**
     * @param string[] $moduleNames
     * @return $this
     */
    public function setModuleNames(array $moduleNames)
    {
        $this->moduleNames = $moduleNames;
        return $this;
    }

    protected function getCacheKey()
    {
        return sha1(json_encode($this->getModuleNames()));
    }
}
