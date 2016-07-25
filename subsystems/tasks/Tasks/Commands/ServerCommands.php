<?php
namespace Electro\Tasks\Commands;

use Electro\Application;
use Electro\Interfaces\ConsoleIOInterface;

/**
 * Implements the Electro's development web server commands.
 *
 * @property Application        $app
 * @property ConsoleIOInterface $io
 */
trait ServerCommands
{
    /**
     * Starts the development web server
     *
     * @param array $options
     * @option $post|p The TCP/IP port the web server will listen on.
     * @option $address|a The IP address the web server will listen on.
     */
    function serverStart ($options = ['port|p' => 8000, 'address|a' => '127.0.0.1'])
    {
    }

    /**
     * Stops the development web server
     */
    function serverStop ()
    {
    }

    /**
     * Checks if the development web server is running
     */
    function serverStatus ()
    {
    }

}
