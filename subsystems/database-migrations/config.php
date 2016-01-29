<?php
use Selenia\Migrations\Commands\MigrationCommands;

return [
  "paths"        => [
    "migrations" => MigrationCommands::$migrationsPath,
  ],
  "environments" => [
    "default_migration_table" => MigrationCommands::$migrationsTable,
    "default_database"        => "main",
    "main"                    => [
      "adapter"     => env ('DB_DRIVER'),
      "host"        => env ('DB_HOST'),
      "name"        => env ('DB_DATABASE'),
      "user"        => env ('DB_USERNAME'),
      "pass"        => env ('DB_PASSWORD'),
      "charset"     => env ('DB_CHARSET'),
      "collation"   => env ('DB_COLLATION'),
      "port"        => env ('DB_PORT'),
      "unix_socket" => env ('DB_UNIX_SOCKET'),
    ],
  ],
];
