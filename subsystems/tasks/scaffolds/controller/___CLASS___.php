<?php
namespace ___NAMESPACE___\Controllers;
use Selenia\Controller;

class ___CLASS___ extends Controller
{
  const ref = __CLASS__;

  protected function model ()
  {
    // TODO: Return a model for the 'default' data source
    return  ['some' => 'data'];
  }

  protected function viewModel ()
  {
    // TODO: Return additional view models
    return [
      'dataSource1' => ['some' => 'data']
    ];
  }

  protected function render ()
  { ?>

    <!-- PUT THE VIEW'S HTML HERE -->

    <?php
  }
}
