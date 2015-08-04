<?php
namespace Selene\Iterators;
use IteratorIterator;
use RecursiveIterator;
use Traversable;

class CustomRecursiveIterator extends \IteratorIterator implements \RecursiveIterator
{
  private $children;
  private $depth;
  private $fn;
  private $p;

  /**
   * (PHP 5 &gt;= 5.1.0)<br/>
   * Create an iterator from anything that is traversable
   * @link http://php.net/manual/en/iteratoriterator.construct.php
   * @param Traversable $iterator
   * @param callable    $fn    A callback that receives the current node's value, key and nesting depth, and returns an
   *                           array or {@see Traversable} for the node's children or `null` if the node has no
   *                           children.
   * @param int         $depth The current nesting depth. You don't need to manually specify this argument.
   */
  public function __construct (Traversable $iterator, callable $fn, $depth = 0)
  {
    parent::__construct ($iterator);
    $this->fn    = $fn;
    $this->depth = $depth;
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
    $r  = $fn ($this->current (), $this->key (), $this->depth);
    if (is_null ($r)) {
      $this->children = null;
      return false;
    }
    if (is_array ($r))
      $r = new \ArrayIterator ($r);
    else if ($r instanceof \IteratorAggregate)
      $r = $r->getIterator ();
    else if (!$r instanceof \Iterator)
      throw new \InvalidArgumentException("Return value from a " . get_class () .
                                          " callback must be an array or an instance of Traversable");
    $this->children = new CustomRecursiveIterator($r, $fn, $this->depth + 1);
    return true;
  }
}
