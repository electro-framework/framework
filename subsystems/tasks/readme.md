# Core Tasks

##### Selenia framework's core command-line tasks

These are the tasks that come bundled with the framework.

## Installation

This package comes pre-installed as an integrant part of the framework, so you usually don't need to install this manually.

If you are not using the standard `framework` package, type this on the terminal:

```shell
composer require selenia-components/core-tasks
```
## Available tasks

#### Module-related tasks

Command              | Description
---------------------|--------------------------------------------
`module:create`      | Scaffolds a new module for your application.
`module:install`     | Installs a plugin module.
`module:register`    | Registers a module on the application's configuration, therefore enabling it for use.
`module:uninstall`   | Removes a module from the application.
`module:unregister`  | Removes a module from the application's configuration, therefore disabling it.

#### Configuration tasks

Command              | Description
---------------------|--------------------------------------------
`init`               | Initializes the application after installation, or reinitializes it afterwards.
`init:config`        | Initializes the application's configuration (.env file).
`init:storage`       | Scaffolds the storage directory's structure.

#### Project build tasks

Command              | Description
---------------------|--------------------------------------------
`build`              | Builds the whole project, including all modules.
`update`             | Builds the main project, excluding modules.
`clean:app`          | Cleans the application-specific assets from the public_html/dist folder.
`clean:libs`         | Cleans the front-end libraries from the public_html/lib folder.
`clean:modules`      | Cleans the module's assets from the public_html/modules folder.

## License

The Selenia framework is open-source software licensed under the [MIT license](http://opensource.org/licenses/MIT).

**Selenia framework** - Copyright &copy; 2015 Impactwave, Lda.
