# SilverStripe maintenance

This modules provides you with a task containing information about available updates as well as known security issues for all installed dependencies. All information will be displayed in a model admin.

## Source of the information

* [SilverStripe Composer Security Checker](https://github.com/spekulatius/silverstripe-composer-security-checker)
* [SilverStripe Composer Update Checker](https://github.com/spekulatius/silverstripe-composer-update-checker)

## Installation

Run the following command to install this package:

   ```
   composer require FriendsOfSilverStripe/silverstripe-maintenance
   ```

and run dev/build.

## Usage

In the admin section of your SilverStripe website you should see a Maintenance section now. Click on this to view the items. *You are required to have admin access to view this information.*

## Future ideas/development

* notifications of security issues/updates
* integration into CD tools and/or deploynaut
