# SilverStripe Maintenance

[![Build Status](https://api.travis-ci.org/bringyourownideas/silverstripe-maintenance.svg?branch=master)](https://travis-ci.org/bringyourownideas/silverstripe-maintenance)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bringyourownideas/silverstripe-maintenance/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bringyourownideas/silverstripe-maintenance/?branch=master)
[![codecov](https://codecov.io/gh/bringyourownideas/silverstripe-maintenance/branch/master/graph/badge.svg)](https://codecov.io/gh/bringyourownideas/silverstripe-maintenance)
[![SilverStripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)

## Overview

The [SilverStripe Maintenance module](https://github.com/bringyourownideas/silverstripe-maintenance "Assists with the 
maintenance of your SilverStripe application") reduces your maintenance related work.

## Requirements

* You require the composer.json and composer.lock files to be available and readible in the environment you plan to use this module. All information is based on these files.
* Install at least one of the modules mentioned under "Source of the information".
* The queuedjob module is a dependency as the checks are scheduled using queuedjobs. This saves you time and work at the end.

Note: Release line 1 is compatible with SilverStripe 3. For SilverStripe 4, please see the 2.x release line.

### Suggested Modules

While the installation of the following modules is optional, it is recommended:
- [bringyourownideas/silverstripe-composer-security-checker](https://github.com/bringyourownideas/silverstripe-composer-security-checker) checks for known security vulnerabilities
- [bringyourownideas/silverstripe-composer-update-checker](https://github.com/bringyourownideas/silverstripe-composer-update-checker) checks for available updates of dependencies
     

### Installation 
 
Install the maintenance package.
```
composer require bringyourownideas/silverstripe-maintenance
```

Build schema and queue a job to populate the database:
```
sake dev/build
```
 
Run the update task to gather update information if the update-checker module is installed:
```
sake dev/tasks/UpdatePackageInfoTask
```
 
Run the security task if that module is installed:
```
sake dev/tasks/SecurityAlertCheckTask
```   

## Source of the Information

The information is based on your composer files. These need to be available in the environment the module is used in. 
If installed, the modules below process the content of the composer files and check suitable sources for information 
regarding your set up.

The main functionality comes from these modules:

* [SilverStripe Composer Security Checker](https://github.com/bringyourownideas/silverstripe-composer-security-checker "Check your SilverStripe application for security issues")
* [SilverStripe Composer Update Checker](https://github.com/bringyourownideas/silverstripe-composer-update-checker "Check your SilverStripe application for available updates of dependencies.")

## Documentation

Please see the [user guide](docs/en/userguide/index.md) section.

## Contributing

Contributions are welcome! Create an issue, explaining a bug or propose development ideas. Find more information on 
[contributing](https://docs.silverstripe.org/en/contributing/) in the SilverStripe developer documentation.

## Reporting Issues

Please [create an issue](https://github.com/bringyourownideas/silverstripe-maintenance/issues) for any bugs you've found, or features you're missing.
