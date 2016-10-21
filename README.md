# SilverStripe Maintenance<br />[![Build Status](https://api.travis-ci.org/FriendsOfSilverStripe/silverstripe-maintenance.svg?branch=master)](https://travis-ci.org/FriendsOfSilverStripe/silverstripe-maintenance) [![Latest Stable Version](https://poser.pugx.org/FriendsOfSilverStripe/silverstripe-maintenance/version.svg)](https://github.com/FriendsOfSilverStripe/silverstripe-maintenance/releases) [![Latest Unstable Version](https://poser.pugx.org/FriendsOfSilverStripe/silverstripe-maintenance/v/unstable.svg)](https://packagist.org/packages/FriendsOfSilverStripe/silverstripe-maintenance) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/FriendsOfSilverStripe/silverstripe-maintenance/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/FriendsOfSilverStripe/silverstripe-maintenance/?branch=master) [![Total Downloads](https://poser.pugx.org/FriendsOfSilverStripe/silverstripe-maintenance/downloads.svg)](https://packagist.org/packages/FriendsOfSilverStripe/silverstripe-maintenance) [![License](https://poser.pugx.org/FriendsOfSilverStripe/silverstripe-maintenance/license.svg)](https://github.com/FriendsOfSilverStripe/silverstripe-maintenance/blob/master/license.md)

### The [SilverStripe Maintenance module](https://github.com/FriendsOfSilverStripe/silverstripe-maintenance "Assists with the maintainence of your SilverStripe application") is reducing your maintenance related work. Currently the module provides you information about available update as well as known security issues. Further enhancements are planned.

* Provides information about
 * available updates for composer packages,
 * known security issues of all installed packages, even dependencies of dependencies and
 * complete list of installed composer packages, including global packages and dependencies.
* All information will be saved to the database as well as displayed in a model admin.
* Scheduling of updates of the information


## Source of the information

The information is based on your composer files. So you need to have them available in the environment you plan to use this module. The modules below process the content of the composer files and check in suitable sources for information regarding your set up.

The main functionality comes from these modules:

* [SilverStripe Composer Security Checker](https://github.com/spekulatius/silverstripe-composer-security-checker "Check your SilverStripe application for security issues")
* [SilverStripe Composer Update Checker](https://github.com/spekulatius/silverstripe-composer-update-checker "Check your SilverStripe application for available updates of dependencies.")
* [SilverStripe Composer Versions](https://github.com/spekulatius/silverstripe-composer-versions "Provides your installed composer versions within your SilverStripe app, for review or other use cases.")


## Requirements and installation

### Requirements

* You require the composer.json and composer.lock files to be available and readible in the environment you plan to use this module. All information is based on these files.
* Install at least one of the modules mentioned under "Source of the information". As a development dependency should be fine in most cases.
* The queuedjob module is a dependency as the checks are scheduled using queuedjobs. This saves you time and work at the end.


### Installation

Run the following commands to install the package including all suggestions and populate the information initially:

```
# install the packages
composer require friendsofsilverstripe/silverstripe-maintenance
composer require spekulatius/silverstripe-composer-security-checker
composer require spekulatius/silverstripe-composer-update-checker
composer require spekulatius/silverstripe-composer-versions

# schedule the population of the data
php ./framework/cli-script.php dev/build

# run the queuedjobs
php ./framework/cli-script.php dev/tasks/ProcessJobQueueTask
php ./framework/cli-script.php dev/tasks/ProcessJobQueueTask
php ./framework/cli-script.php dev/tasks/ProcessJobQueueTask
```

*If you don't want to install all packages adjust the command above.*


## Usage

In the admin section of your SilverStripe website you should see a Maintenance section now. Click on this to view the available information. *You are required to have admin access to view this information.*


### Scheduling of updates

You can schedule updates using the queuedjobs module. Click on either 'Composer Security Vulnerability' or 'Composer Update' and scroll to the bottom of the page. There you find a simple form which allows you to define an interval for your automatic updates. Furthermore the update will automatically scheduled on dev/build.


## MISC: [Future ideas/development, issues](https://github.com/FriendsOfSilverStripe/silverstripe-maintenance/issues), [Contributing](https://github.com/FriendsOfSilverStripe/silverstripe-maintenance/blob/master/CONTRIBUTING.md), [License](https://github.com/FriendsOfSilverStripe/silverstripe-maintenance/blob/master/license.md)
