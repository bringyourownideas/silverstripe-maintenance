# SilverStripe maintenance

The SilverStripe maintenance module is aiming to reduce your maintenance related work. Currently the module provides you information about available update as well as known security issues.

## Features

* Provides information about available updates for composer packages
* Information about known security issues of all installed packages, even dependencies of dependencies.
* All information will be saved to the database as well as displayed in a model admin.
* Scheduling of updates of the information

## Source of the information

The information is based on your composer files. So you need to have them available in the environment you plan to use this module. The modules below process the content of the composer files and check in suitable sources for information regarding your set up.

The main functionality comes from these two modules:

* [SilverStripe Composer Security Checker](https://github.com/spekulatius/silverstripe-composer-security-checker)
* [SilverStripe Composer Update Checker](https://github.com/spekulatius/silverstripe-composer-update-checker)

## Requirements and installation

### Requirements

* You require the composer.json and composer.lock files to be available and readible in the environment you plan to use this module. All information is based on these files.
* Install at least one of the two modules mentioned under "Source of the information"

### Recommendation

* The queuedjob module is highly recommendated as this allows you to schedule your checks. This saves you time and work at the end.

### Installation

Run the following commands to install the package including both suggestions and queuedjobs:

   ```
   composer require friendsofsilverstripe/silverstripe-maintenance
   composer require silverstripe/queuedjobs
   composer require spekulatius/silverstripe-composer-security-checker
   composer require spekulatius/silverstripe-composer-update-checker
   ```

and run dev/build. *If you don't want to install all packages adjust the command above.*

## Usage

First you need to run the tasks to update the information. To do this run the following tasks:

* www.mysite.com/dev/tasks/CheckComposerSecurityTask
* www.mysite.com/dev/tasks/CheckComposerUpdatesTask

In the admin section of your SilverStripe website you should see a Maintenance section now. Click on this to view the available information. *You are required to have admin access to view this information.*

### Scheduling of updates

You can schedule updates using the queuedjobs module. Click on either 'Composer Security Vulnerability' or 'Composer Update' and scroll to the bottom of the page. There you find a simple form which allows you to define an interval for your automatic updates.

## Future ideas/development

* notifications of security issues/updates
* integration into CD tools and/or deploynaut
