<?php

return [
  "paths"        => [
    "migrations" => \Selenia\Migrations\Commands\Commands::$migrationsPath,
  ],
  "environments" => [
    "default_migration_table" => \Selenia\Migrations\Commands\Commands::$migrationsTable,
    "default_database"        => "main",
    "main"                    => [
      "adapter"     => $_ENV['DB_DRIVER'],
      "host"        => $_ENV['DB_HOST'],
      "name"        => $_ENV['DB_DATABASE'],
      "user"        => $_ENV['DB_USERNAME'],
      "pass"        => $_ENV['DB_PASSWORD'],
      "charset"     => $_ENV['DB_CHARSET'],
      "collation"   => $_ENV['DB_COLLATION'],
      "port"        => $_ENV['DB_PORT'],
      "unix_socket" => $_ENV['DB_UNIX_SOCKET'],
    ],
  ],
];
