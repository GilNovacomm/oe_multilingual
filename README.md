# OpenEuropa Multilingual

[![Build Status](https://drone.fpfis.eu/api/badges/openeuropa/oe_multilingual/status.svg?branch=master)](https://drone.fpfis.eu/openeuropa/oe_multilingual)

The OpenEuropa Multilingual module offers default multilingual features for the OpenEuropa project, like:

- Enable all 24 official EU languages
- Provide an optional language switcher block on the [OpenEuropa Theme][1] site header region
- Make sure that the administrative interface is always set to English
- Allow English to be translated so that the default English copy may be fixed or improved, if necessary
- Configure the site to follow [IPG rules for Language Negotiation](http://ec.europa.eu/ipg/print/print_all_content/index_en.htm#3.0), using the path suffix. _(optional)_

**Table of contents:**

- [Installation](#installation)
- [Development](#development)
  - [Project setup](#project-setup)
  - [Using Docker Compose](#using-docker-compose)
  - [Disable Drupal 8 caching](#disable-drupal-8-caching)
- [Demo module](#demo-module)
- [Contributing](#contributing)
- [Versioning](#versioning)

## Installation

The recommended way of installing the OpenEuropa Multilingual module is via [Composer][2].

```bash
composer require openeuropa/oe_multilingual
```

### Enable the module

In order to enable the module in your project run:

```bash
./vendor/bin/drush en oe_multilingual
```

## Development

The OpenEuropa Multilingual project contains all the necessary code and tools for an effective development process,
such as:

- All PHP development dependencies (Drupal core included) are required by [composer.json](composer.json)
- Project setup and installation can be easily handled thanks to the integration with the [Task Runner][3] project.
- All system requirements are containerized using [Docker Composer][4]

### Project setup

Download all required PHP code by running:

```bash
composer install
```

This will build a fully functional Drupal test site in the `./build` directory that can be used to develop and showcase
the module's functionality.

Before setting up and installing the site make sure to customize default configuration values by copying [runner.yml.dist](runner.yml.dist)
to `./runner.yml` and overriding relevant properties.

This will also:

- Symlink the module in  `./build/modules/custom/oe_multilingual` so that it's available for the test site
- Setup Drush and Drupal's settings using values from `./runner.yml.dist`
- Setup PHPUnit and Behat configuration files using values from `./runner.yml.dist`

**Please note:** project files and directories are symlinked within the test site by using the
[OpenEuropa Task Runner's Drupal project symlink](https://github.com/openeuropa/task-runner-drupal-project-symlink) command.

If you add a new file or directory in the root of the project, you need to re-run `drupal:site-setup` in order to make
sure they are be correctly symlinked.

If you don't want to re-run a full site setup for that, you can simply run:

```
$ ./vendor/bin/run drupal:symlink-project
```

After a successful setup install the site by running:

```bash
./vendor/bin/run drupal:site-install
```

This will:

- Install the test site
- Enable the OpenEuropa Multilingual module
- Enable the OpenEuropa Multilingual Demo module and [Configuration development][5] modules
- Enable and set the OpenEuropa Theme as default

### Using Docker Compose

Alternatively, you can build a development site using [Docker](https://www.docker.com/get-docker) and
[Docker Compose](https://docs.docker.com/compose/) with the provided configuration.

Docker provides the necessary services and tools such as a web server and a database server to get the site running,
regardless of your local host configuration.

#### Requirements:

- [Docker](https://www.docker.com/get-docker)
- [Docker Compose](https://docs.docker.com/compose/)

#### Configuration

By default, Docker Compose reads two files, a `docker-compose.yml` and an optional `docker-compose.override.yml` file.
By convention, the `docker-compose.yml` contains your base configuration and it's provided by default.
The override file, as its name implies, can contain configuration overrides for existing services or entirely new
services.
If a service is defined in both files, Docker Compose merges the configurations.

Find more information on Docker Compose extension mechanism on [the official Docker Compose documentation](https://docs.docker.com/compose/extends/).

#### Usage

To start, run:

```bash
docker-compose up
```

It's advised to not daemonize `docker-compose` so you can turn it off (`CTRL+C`) quickly when you're done working.
However, if you'd like to daemonize it, you have to add the flag `-d`:

```bash
docker-compose up -d
```

Then:

```bash
docker-compose exec web composer install
docker-compose exec web ./vendor/bin/run drupal:site-install
```

Using default configuration, the development site files should be available in the `build` directory and the development site
should be available at: [http://127.0.0.1:8080/build](http://127.0.0.1:8080/build).

#### Running the tests

To run the grumphp checks:

```bash
docker-compose exec web ./vendor/bin/grumphp run
```

To run the phpunit tests:

```bash
docker-compose exec web ./vendor/bin/phpunit
```

To run the behat tests:

```bash
docker-compose exec web ./vendor/bin/behat
```

### Step debugging

To enable step debugging from the command line, pass the `XDEBUG_SESSION` environment variable with any value to
the container:

```bash
docker-compose exec -e XDEBUG_SESSION=1 web <your command>
```

Please note that, starting from XDebug 3, a connection error message will be outputted in the console if the variable is
set but your client is not listening for debugging connections. The error message will cause false negatives for PHPUnit
tests.

To initiate step debugging from the browser, set the correct cookie using a browser extension or a bookmarklet
like the ones generated at https://www.jetbrains.com/phpstorm/marklets/.

### Disable Drupal 8 caching

Manually disabling Drupal 8 caching is a laborious process that is well described [here][8].

Alternatively you can use the following Drupal Console commands to disable/enable Drupal 8 caching:

```bash
./vendor/bin/drupal site:mode dev  # Disable all caches.
./vendor/bin/drupal site:mode prod # Enable all caches.
```

Note: to fully disable Twig caching the following additional manual steps are required:

1. Open `./build/sites/default/services.yml`
2. Set `cache: false` in `twig.config:` property. E.g.:

```yaml
parameters:
 twig.config:
   cache: false
```
3. Rebuild Drupal cache: `./vendor/bin/drush cr`

This is due to the following [Drupal Console issue][9].

## Demo module

The OpenEuropa Multilingual module ships with a demo module which provides all the necessary configuration and code needed to showcase the modules' most important features.

The demo module includes a translatable content type with automatic URL path generation.

In order to install the OpenEuropa Multilingual demo module follow [the instructions][10] or enable it via [Drush][11] by running:

```bash
./vendor/bin/drush en oe_multilingual_demo -y
```

## Known Issues

##### Enabling string English string translation

Enabling English string translation can have unintended consequences when making changes to translatable configuration entities.
If these strings are available in locale, the change would get saved as EN translations.
=======
## Enabling URL suffix

This is optional and should be done only if your website should follow the [IPG rules](http://ec.europa.eu/ipg/print/print_all_content/index_en.htm#3.0) for language negotiation.

In order to use this feature you need to enable the OpenEuropa Multilingual URL Suffix module by following [these instructions][10] or via [Drush][11] by running:
```bash
./vendor/bin/drush en oe_multilingual_url_suffix -y
```

After enabling the module:

1. Go to `admin/config/regional/language/detection`
2. Disable the `URL` method in `Interface text language detection` and `Content language detection`;
3. Enable the `URL suffix` method in `Interface text language detection` and `Content language detection`;
4. Make sure that the `URL suffix` method is located on top of list but after the `Administration pages` negotiation method, if you use it
5. Save new order
6. If necessary configure the `URL suffix` method by clicking on `Configure`
7. Rebuild cache

### URL suffix usage

- It is strongly recommended to avoid an usage of the language suffix separator (`_`) in their pathauto patterns/aliases as it might lead to unexpected behaviors.

- Drush user:login(uli) implementation does not currently work with oe_multilingual_url_suffix module. The following [patch](https://patch-diff.githubusercontent.com/raw/drush-ops/drush/pull/4209.diff) is required to correctly generate the url.

## Contributing
Please read [the full documentation](https://github.com/openeuropa/openeuropa) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the available versions, see the [tags on this repository](https://github.com/openeuropa/oe_multilingual/tags).

[1]: https://github.com/openeuropa/oe_theme
[2]: https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed
[3]: https://github.com/openeuropa/task-runner
[4]: https://docs.docker.com/compose
[5]: https://github.com/openeuropa/oe_theme#project-setup
[7]: https://www.drupal.org/project/config_devel
[8]: https://www.drupal.org/node/2598914
[9]: https://github.com/hechoendrupal/drupal-console/issues/3854
[10]: https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
[11]: https://www.drush.org/
