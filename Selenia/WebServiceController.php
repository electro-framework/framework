<?php
namespace Selenia;

use ReflectionException;
use ReflectionObject;
use Selenia\Exceptions\BaseException;
use Selenia\Exceptions\Status;
use Selenia\Http\Controllers\Controller;

class WebServiceController extends Controller
{
  public $isWebService = true;
  public $renderOnPOST = true;
  public $responseData = null;

  /**
   * Responds to a REST-compatible POST request.
   * @param mixed $data The body of the request, parsed as JSON or null if not parsed (another content type).
   * @return mixed|void The response content, to be serialized to JSON.
   */
  function handlePost ($data)
  {
    // no op
  }

  /**
   * Responds to a REST-compatible POST request of a Content-Type that is not 'application/json'.
   * @param resource $input       The raw request body data as a stream handle.
   * @param string   $contentType The body's MIME content type.
   * @return mixed|void The response content, to be serialized to JSON.
   */
  function handleRawPost ($input, $contentType)
  {
    // no op
  }

  protected function doFormAction (DataObject $data = null)
  {
    $this->getActionAndParam ($action, $param);
    $class = new ReflectionObject($this);

    // REST-compatible call

    if (!$action) {
      $contentType = $this->getHeader ('Content-Type');
      if (str_begins_with ($contentType, 'application/json')) {
        $input              = file_get_contents ('php://input', FILE_TEXT);
        $data               = json_decode ($input, true);
        $this->responseData = $this->handlePost ($data);
      }
      else {
        $input              = fopen ('php://input', 'rb');
        $this->responseData = $this->handleRawPost ($input, $contentType);
        fclose ($input);
      }
    }

    // Legacy format call

    else {
      try {
        $method = $class->getMethod ('action_' . $action);
      } catch (ReflectionException $e) {
        throw new BaseException('Class <b>' . $class->getName () . "</b> can't handle action <b>$action</b>.",
          Status::ERROR);
      }
      $this->responseData = $method->invoke ($this, $data, $param);
    }
  }

  function render ()
  {
    return $this->responseData;
  }
}
