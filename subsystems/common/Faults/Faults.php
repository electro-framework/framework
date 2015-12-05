<?php
namespace Selenia\Faults;

const N = Faults::class . '\\';

interface Faults
{
  const ARG_NOT_ITERABLE  = N . "ARG_NOT_ITERABLE";
  const DUPLICATE_LINK_ID = N . "DUPLICATE_LINK_ID";
  const LINK_NOT_FOUND    = N . "LINK_NOT_FOUND";
}
