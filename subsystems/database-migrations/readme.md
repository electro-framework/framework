# Migrations

##### Electro Database Migrations support

This plugin integrates the [Phinx](https://phinx.org) migration tool with Electro.

## Features

1. Automatically uses your application's database configuration.
2. Supports multi-module migrations.
3. Runs from the `workman` console, with enhanced commands for multi-module support.
4. Independent from any ORM. You can use any ORM you wish on your application, or none at all.

## Available commands

Command              | Description
---------------------|--------------------------------------------------------------------------
`make:migration`     | Create a new migration for the specified module.
`migration:up`       | Migrate the database for the specified module,
`migration:down`     | Rollback the last migration or a specific one, for the specified module.
`migration:status`   | Show migration status for the specified module.

You can also type `workman` on the terminal to get a list of available commands.

Type `worman help migration:xxx` (where `xxx` is the command name) to know which arguments and options each command supports.

## License

The Electro framework is open-source software licensed under the [MIT license](http://opensource.org/licenses/MIT).

**Electro framework** - Copyright &copy; Cláudio Silva and Impactwave, Lda.
