<?php

namespace BringYourOwnIdeas\Maintenance\Util;

use SilverStripe\Dev\Deprecation;

/**
 * Handles fetching module health information from addons.silverstripe.org
 * @deprecated 3.2.0 Will be removed without equivalent functionality
 */
class ModuleHealthLoader extends ApiLoader
{
    /**
     * @var string[]
     */
    protected $moduleNames = [];

    public function __construct()
    {
        Deprecation::withSuppressedNotice(
            fn() => Deprecation::notice(
                '3.2.0',
                'Will be removed without equivalent functionality',
                Deprecation::SCOPE_CLASS
            )
        );
    }

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
        return sha1(json_encode($this->getModuleNames()) ?? '');
    }
}
