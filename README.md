# SilverStripe maintenance

This modules provides you with a task containing information about available updates as well as known security issues for all installed dependencies. All information will be displayed in a model admin.

## Source of the information

You need to install at least one of the following packages to take advantage of this module:

* [SilverStripe Composer Security Checker](https://github.com/spekulatius/silverstripe-composer-security-checker)
* [SilverStripe Composer Update Checker](https://github.com/spekulatius/silverstripe-composer-update-checker)

## Installation

Run the following command to install this package *including* both suggestions:

   ```
   composer require FriendsOfSilverStripe/silverstripe-maintenance
   composer require spekulatius/silverstripe-composer-security-checker
   composer require spekulatius/silverstripe-composer-update-checker
   ```

and run dev/build. *If you don't want to install both packages remove this from the command above.*

## Usage

First you need to run the tasks to update the information. To do this run the following tasks:

www.mysite.com/dev/tasks/SecurityCheckerTask
www.mysite.com/dev/tasks/CheckComposerUpdatesTask

In the admin section of your SilverStripe website you should see a Maintenance section now. Click on this to view the available information. *You are required to have admin access to view this information.*

## Future ideas/development

* notifications of security issues/updates
* integration into CD tools and/or deploynaut
