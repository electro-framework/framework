<?php
namespace Selenia\Interfaces\Faults\en_US;

use Selenia\Faults\Faults as F;

interface Faults
{
  const MESSAGES = [
    F::ARG_NOT_ITERABLE => 'The argument must be iterable',
    F::LINK_NOT_FOUND   => 'Navigation link \'%s\' was not found',
  ];
}
