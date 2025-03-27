# Silverstripe Maintenance

[![CI](https://github.com/bringyourownideas/silverstripe-maintenance/actions/workflows/ci.yml/badge.svg)](https://github.com/bringyourownideas/silverstripe-maintenance/actions/workflows/ci.yml)
[![Silverstripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)

## Overview

The [Silverstripe Maintenance module](https://github.com/bringyourownideas/silverstripe-maintenance "Assists with the 
maintenance of your Silverstripe application") reduces your maintenance related work.

![UI Preview](docs/en/_img/ui-with-sec-alert.png)

## Requirements

* Requires the `composer.json` and `composer.lock` files to be available and readable in the environment you plan to use this module. All information is based on these files.
* The [queuedjobs module](https://github.com/symbiote/silverstripe-queuedjobs) updates metadata on your installed modules in the background. You need to [configure](https://github.com/symbiote/silverstripe-queuedjobs) it to run those jobs.
* For the optional update checkers, the webserver environment needs to be able to contact external information sources through network requests
* SilverStripe:
  * Maintenance ^2.2: Silverstripe ^4.4
  * Maintenance ~2.1.0: Silverstripe 4.0-4.3
  * Maintenance: ^1.0: Silverstripe 3.x

### Suggested Modules

By default, the module will read your installed modules,
and present them as a report in the CMS under `admin/reports`.

In order to get information about potential updates to these modules,
we recommend the installation of the following additional module:

- [bringyourownideas/silverstripe-composer-update-checker](https://github.com/bringyourownideas/silverstripe-composer-update-checker) checks for available updates of dependencies

The previously recommended silverstripe-composer-security-checker module [can't work anymore](https://github.com/bringyourownideas/silverstripe-composer-security-checker/issues/57) and isn't recommended to be used anymore.

### Installation 
 
Option 1 (recommended): Install the maintenance package and suggested dependency

```
composer require bringyourownideas/silverstripe-maintenance bringyourownideas/silverstripe-composer-update-checker
```

Option 2 (minimal): Install only the maintenance package without any update checks

```
composer require bringyourownideas/silverstripe-maintenance
```

Build schema and queue an initial job to populate the database:

```
sake dev/build
```

If you haven't already, you need to [configure the job queue](https://github.com/symbiote/silverstripe-queuedjobs)
to update module metadata in the background. By default, this happens every day,
but can be configured to run at different intervals through YAML config:

```yaml
BringYourOwnIdeas\Maintenance\Jobs\CheckForUpdatesJob:
  reschedule_delay: '+1 hour'
```

### Manually running tasks

By default, tasks are run through a job queue. You can also choose to manually refresh via the command line.

Run the update task (includes the [update-checker](https://github.com/bringyourownideas/silverstripe-composer-update-checker))
```
sake dev/tasks/UpdatePackageInfoTask
```

## How your composer.json influences the report

The report available through the CMS shows "Available" and "Latest" versions (see [user guide](docs/en/userguide/index.md)).
The version recommendations in those columns depend on your
`composer.json` configuration. When setting tight constraints (e.g. `silverstripe/framework:4.3.2@stable`),
newer releases don't show up as expected. We recommend to have looser constraints by default
(e.g. `silverstripe/framework:^4.3`). When the "Latest" version shows `dev-master`,
it likely means that you have `"minimum-stability": "dev"` in your `composer.json`.

## Private repositories

While this module itself doesn't fetch information about repositories, other modules (such as the [update-checker](https://github.com/bringyourownideas/silverstripe-composer-update-checker)) do. If you have private repositories for which you are unable to provide authentication details to the respective module, you should mark those repositories as inaccessible.

This can be done either per repository:
```yml
BringYourOwnIdeas\Maintenance\Tasks:
  inaccessible_packages:
    - some-org/some-package-name
```

or for situations where you are hosting repositories yourself, per host:
```yml
BringYourOwnIdeas\Maintenance\Tasks:
  inaccessible_repository_hosts:
    - gitea.mycompany.com
```
This catches packages whether they're referenced by https or ssh URLs - for example, for `https://gitea.mycompany.com/some-org/some-package-name.git` and for `git@gitea.mycompany.com:some-org/some-package-name.git`, the value should be `gitea.mycompany.com`.

## Documentation

Please see the [user guide](docs/en/userguide/index.md) section.

## Contributing

Contributions are welcome! Create an issue, explaining a bug or propose development ideas. Find more information on 
[contributing](https://docs.silverstripe.org/en/contributing/) in the Silverstripe developer documentation.

## Reporting Issues

Please [create an issue](https://github.com/bringyourownideas/silverstripe-maintenance/issues) for any bugs you've found, or features you're missing.
