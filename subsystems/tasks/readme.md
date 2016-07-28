# Core Tasks

##### Electro framework's core command-line tasks

## Introduction

These are the tasks that come bundled with the framework.

These tasks allow you to run automation scripts that perform repetitive tasks like minification, compilation, unit testing, linting, etc.

They can also assist you during the development process by:

* Scaffolding Modules, Controllers, Views and many other framework-related constructs.
* Installing / removing plugin / template modules.

## Available tasks

#### Module-related tasks

Command                   | Description
--------------------------|--------------------------------------------
`make:module`             | Scaffolds a new project module.
`module:install-plugin`   | Installs a plugin module.
`module:install-template` | Installs a module from a template.
`module:enable`           | Enables a module.
`module:disable`          | Disables a module.
`module:uninstall`        | Removes a module from the application.
`module:cleanup`          | Runs a module's post-uninstallation tasks.

#### Registry-related tasks

Command                   | Description
--------------------------|--------------------------------------------
`module:refresh`        | Forces an update of the module registry.
`module:status`         | Displays information about the currently registered modules.

#### Configuration tasks

Command              | Description
---------------------|--------------------------------------------
`init`               | Initializes the application after installation, or reinitializes it afterwards.
`init:config`        | Initializes the application's configuration (.env file).
`init:storage`       | Scaffolds the storage directory's structure.

## License

The Electro framework is open-source software licensed under the [MIT license](http://opensource.org/licenses/MIT).

**Electro framework** - Copyright &copy; Cláudio Silva and Impactwave, Lda.
