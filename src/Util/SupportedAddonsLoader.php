<?php

namespace BringYourOwnIdeas\Maintenance\Util;

use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfoTask;
use SilverStripe\Dev\Deprecation;

/**
 * Handles fetching supported addon details from silverstripe/supported-modules
 * @deprecated 3.2.0 Use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfoTask::getSupportedPackages() instead.
 */
class SupportedAddonsLoader extends ApiLoader
{
    public function __construct()
    {
        Deprecation::withSuppressedNotice(
            fn() => Deprecation::notice(
                '3.2.0',
                'Use ' . UpdatePackageInfoTask::class . '::getSupportedPackages() instead.',
                Deprecation::SCOPE_CLASS
            )
        );
    }

    /**
     * Return the list of supported modules
     *
     * @return array
     */
    public function getAddonNames()
    {
        // Check for a cached value and return if one is available
        $endpoint = 'https://raw.githubusercontent.com/silverstripe/supported-modules/main/repositories.json';
        return $this->doRequest($endpoint, function ($responseJson) {
            return array_filter(array_map(
                fn(array $item) => isset($item['majorVersionMapping'][5]) ? $item['packagist'] : null,
                $responseJson['supportedModules']
            ));
        });
    }

    protected function getCacheKey()
    {
        return 'addons';
    }
}
