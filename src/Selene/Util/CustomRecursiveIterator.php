<?php
namespace Selene\Util;
use RecursiveIterator;
use Traversable;

class CustomRecursiveIterator extends \IteratorIterator implements \RecursiveIterator
{
  private $children;
  private $fn;

  /**
   * (PHP 5 &gt;= 5.1.0)<br/>
   * Create an iterator from anything that is traversable
   * @link http://php.net/manual/en/iteratoriterator.construct.php
   * @param Traversable $iterator
   * @param callable    $fn A callback that receives the current node's value and key and returns an iterator for the
   *                        node's children or `null` if the node has no children.
   */
  public function __construct (Traversable $iterator, callable $fn)
  {
    parent::__construct ($iterator);
    $this->fn = $fn;
  }

  /**
   * (PHP 5 &gt;= 5.1.0)<br/>
   * Returns an iterator for the current entry.
   * @link http://php.net/manual/en/recursiveiterator.getchildren.php
   * @return RecursiveIterator An iterator for the current entry.
   */
  public function getChildren ()
  {
    return $this->children;
  }

  /**
   * (PHP 5 &gt;= 5.1.0)<br/>
   * Returns if an iterator can be created for the current entry.
   * @link http://php.net/manual/en/recursiveiterator.haschildren.php
   * @return bool true if the current entry can be iterated over, otherwise returns false.
   */
  public function hasChildren ()
  {
    $fn = $this->fn;
    return !is_null ($this->children = $fn ($this->current (), $this->key ()));
  }
}
