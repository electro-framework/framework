<?php
namespace Selenia\Http\Services;

use Psr\Http\Message\ResponseInterface;
use Selenia\Interfaces\Http\ResponseSenderInterface;

class ResponseSender implements ResponseSenderInterface
{
  protected $bufferSize;
  protected $outputStream;

  public function __construct ($outputStream = 'php://stdout', $bufferSize = 65536)
  {
    $this->outputStream = $outputStream;
    $this->bufferSize   = $bufferSize;
  }

  public function send (ResponseInterface $response)
  {
    $this->sendHeaders ($response);
    $this->sendBody ($response);
  }

  public function sendHeaders (ResponseInterface $response)
  {
    header (
      sprintf ('HTTP/%s %s %s',
        $response->getProtocolVersion (),
        $response->getStatusCode (),
        $response->getReasonPhrase ()
      ),
      true, $response->getStatusCode ()
    );
    foreach ($response->getHeaders () as $header => $values) {
      $name  = $this->filterHeader ($header);
      $first = true;
      foreach ($values as $value) {
        header (sprintf ('%s: %s', $name, $value), $first);
        $first = false;
      }
    }
  }

  public function sendBody (ResponseInterface $response)
  {
    $body = $response->getBody ();
    try {
      $body->rewind ();
    }
    catch (\Exception $e) {}

    // If the stream interface holds a real PHP stream within, use the native stream to optimize the output.

    $resource = $body->detach ();
    if ($resource) {
      stream_copy_to_stream ($resource, fopen('php://output', 'w'));
      return;
    }

    // Otherwise, it must be a synthetic stream, so read from the interface and write to the output incrementally.

    if ($body->getSize () < $this->bufferSize)
      echo $body->getContents ();
    else while (!$body->eof ())
      echo $body->read ($this->bufferSize);
  }

  /**
   * Filter a header name to wordcase,
   *
   * @param string $header
   * @return string
   */
  private function filterHeader ($header)
  {
    $filtered = str_replace ('-', ' ', $header);
    $filtered = ucwords ($filtered);
    return str_replace (' ', '-', $filtered);
  }

}
