<?php
namespace Selenia;

use ReflectionException;
use ReflectionObject;
use Selenia\Exceptions\BaseException;
use Selenia\Exceptions\Status;

class WebServiceController extends Controller
{
  public $isWebService = true;
  public $renderOnPOST = true;
  public $responseData = null;

  function render ()
  {
    return $this->responseData;
  }

  protected function doFormAction (DataObject $data = null)
  {
    $this->getActionAndParam ($action, $param);
    $class = new ReflectionObject($this);
    try {
      $method = $class->getMethod ('action_' . $action);
    } catch (ReflectionException $e) {
      throw new BaseException('Class <b>' . $class->getName () . "</b> can't handle action <b>$action</b>.",
        Status::ERROR);
    }
    $this->responseData = $method->invoke ($this, $data, $param);
  }
}
