title: Installed Modules Report
summary: Assists with the maintenance of your SilverStripe application

# Installed Modules Report
## Usage 

SilverStripe websites and the CMS can be highly customised using SilverStripe modules. The 'Installed modules' report 
provides an overview of the modules installed for your SilverStripe website. 

In the admin section of your SilverStripe website navigate to the Reports section. Open up the 'Installed modules' 
report to view the available information. 

Please note that you are required to have admin access to view this information.

## Check for updates

Click on the 'Check for Updates' button to refresh the list with the latest available information. Updating this list 
may take some time, since it's waiting for the system to run a scheduled job in the background. You can continue to use 
the CMS while the update is run.

## Check for additional modules

Click on the 'Explore Addons' button to access SilverStripe's add-on repository. Use this site to find modules and 
themes to add to your SilverStripe website.

## What do "Version, Available, Latest" mean?

SilverStripe follows Semantic Versioning. If you would like to learn more see [https://semver.org/](https://semver.org/).

### Version

The information in this column shows the current version of each of the modules you've got installed.

### Available

The information in this column shows the latest version of the modules available within the version constraints of your 
installation. If no version is displayed, you are either already on the latest version available for your constraint, 
or your constraint might be very restrictive.

### Latest

The information in this column shows the latest version of the module available. If this varies from the available version, it means that this latest version is outside of your version constraint of your installation.

## What do the security alerts mean?

If you've got the [SilverStripe Composer Security Checker module](https://addons.silverstripe.org/add-ons/bringyourownideas/silverstripe-composer-security-checker) 
installed you may see security alerts. To find out more about these alerts see 
[the corresponding documentation](https://github.com/bringyourownideas/silverstripe-composer-security-checker/tree/master/docs/en/userguide). 
