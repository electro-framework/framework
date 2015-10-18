# Migrations

##### Selenia Database Migrations support

This plugin integrates the [Phinx](https://phinx.org) migration tool with Selenia.

## Features

1. Automatically uses your application's database configuration.
2. Supports multi-module migrations.
3. Runs from the `selenia` console, with enhanced commands for multi-module support.
4. Independent from any ORM. You can use any ORM you wish on your application, or none at all.

## Installation

This package comes pre-installed as an integrant part of the framework, so you usually don't need to install this manually.

If you are not using the standard `framework` package, type this on the terminal:

```shell
composer require selenia-components/migrations
```
## Available commands

Command              | Description
---------------------|--------------------------------------------------------------------------
`migration:create`   | Create a new migration for the specified module.
`migration:up`       | Migrate the database for the specified module,
`migration:down`     | Rollback the last migration or a specific one, for the specified module.
`migration:status`   | Show migration status for the specified module.

You can also type `selenia` on the terminal to get a list of available commands.

Type `selenia help migration:xxx` (where `xxx` is the command name) to know which arguments and options each command supports.

## License

The Selenia framework is open-source software licensed under the [MIT license](http://opensource.org/licenses/MIT).

**Selenia framework** - Copyright &copy; 2015 Impactwave, Lda.
