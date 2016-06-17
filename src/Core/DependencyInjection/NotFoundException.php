<?php
namespace Electro\Core\DependencyInjection;

use Interop\Container\Exception\NotFoundException as NotFoundInterface;

class NotFoundException extends \Exception implements NotFoundInterface
{
}
