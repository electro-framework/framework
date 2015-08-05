<?php
namespace Selene\Util;

use AppendIterator;
use ArrayIterator;
use CallbackFilterIterator;
use EmptyIterator;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use LimitIterator;
use MultipleIterator;
use NoRewindIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Selene\Iterators\CachedIterator;
use Selene\Iterators\LoopIterator;
use Selene\Iterators\MacroIterator;
use Selene\Iterators\MapIterator;
use Selene\Iterators\RangeIterator;
use Selene\Iterators\RecursiveIterator;
use Traversable;

/**
 * Provides a fluent interface to assemble chains of iterators and other operations.
 *
 * <p>Inputs to the chain can be any kind of {@see Traversable}, i.e. native arrays, or classes implementing
 * {@see Iterator} or {@see IteratorAggregate}.
 *
 * <p>An iteration chain assembled with this builder can perform multiple transformations over a data flow without
 * storing the resulting data from intermediate steps in memory. When operating over large data sets, this mechanism
 * can be very light on memory consumption.
 *
 * <p>The fluent API makes it **very easy and intuitive** to use the native SPL iterators (together with some custom
 * ones provided by this library) and the expressive syntax allows you to assemble sophisticated processing pipelines
 * in an elegant, terse and readable fashion.
 *
 * <p>Note: Some operations require the iteration data to be "materialized", i.e. fully iterated and stored internally
 * as an array, before being applied. This only happens for operations that require all data to be present (ex:
 * `reverse()` or `sort()`), but the resulting data will be automatically converted back to an iterator whenever it
 * makes sense.
 */
class Flow implements IteratorAggregate
{
  private static $SORT_TYPES = [
    'asort'       => 2,
    'arsort'      => 2,
    'krsort'      => 2,
    'ksort'       => 2,
    'natcasesort' => 1,
    'natsort'     => 1,
    'rsort'       => 2,
    'shuffle'     => 1,
    'sort'        => 2,
    'uasort'      => 3,
    'uksort'      => 3,
    'usort'       => 3,
  ];
  /** @var array */
  private $data;
  /** @var Iterator */
  private $it;

  /**
   * Sets the initial data/iterator.
   * @param array|Traversable $src
   */
  function __construct ($src = [])
  {
    $this->setIterator ($src);
  }

  /**
   * Creates a Flow from a list of Traversable inputs that iterates over all of them in parallel.
   *
   * The generated sequence is comprised of array items where each one contains an iteration step from each one of the
   * inputs. The key for the value from each input corresponds (by default) to the original key for the
   * input on the original set, or it may be fetched from the `$fields` argument, if one is provided.
   *
   * The iteration continues until all inputs are exhausted, returning `null` fields for prematurely finished inputs.
   *
   * You can change these behaviours using the `$flags` argument.
   *
   * @param Traversable|array $inputs A list of Traversable inputs.
   * @param array|null        $fields A list or map of key names for use on the resulting array items. If not
   *                                  specified, the original iterator's keys will be used or numerical autoincremented
   *                                  indexes, depending on the `$flags`.
   * @param int               $flags  One or more of the MultipleIterator::MIT_XXX constants.
   *                                  <p>Default: MIT_NEED_ANY | MIT_KEYS_ASSOC
   * @return static
   */
  static function combine ($inputs, array $fields = null, $flags = 2)
  {
    $mul = new MultipleIterator($flags);
    foreach (self::iteratorFrom ($inputs) as $k => $it)
      $mul->attachIterator (self::iteratorFrom ($it), isset($fields) ? $fields[$k] : $k);
    return new static ($mul);
  }

  /**
   * @param array|Traversable $src
   * @return static
   */
  static function from ($src)
  {
    return new static ($src);
  }

  /**
   * Converts the argument into an iterator.
   * @param Traversable|array $t
   * @return Iterator
   */
  static function iteratorFrom ($t)
  {
    switch (true) {
      case $t instanceof IteratorAggregate:
        return $t->getIterator ();
        break;
      case $t instanceof Iterator:
        return $t;
        break;
      case is_array ($t):
        return new ArrayIterator ($t);
        break;
      default:
        throw new InvalidArgumentException ("Invalid iteration type.");
    }
  }

  /**
   * Creates an iteration over a generated sequence of numbers.
   * @param int|float $from Starting value.
   * @param int|float $to   The (inclusive) limit. May be lower than `$from` if the `$step` is negative.
   * @param int|float $step Can be either positive or negative. If zero, an infinite sequence of constant values is
   *                        generated.
   * @return static
   */
  static function range ($from, $to, $step = 1)
  {
    return new static (new RangeIterator ($from, $to, $step));
  }

  /**
   * Concatenates the specified Traversables and iterates them in sequence.
   * @param Traversable[] $list
   * @return static
   */
  static function sequence (array $list)
  {
    $a = new AppendIterator;
    foreach ($list as $it)
      $a->append (self::iteratorFrom ($it));
    return new static ($a);
  }

  /**
   * Creates an empty iteration.
   * @return static
   */
  static function void ()
  {
    return new static (new EmptyIterator);
  }

  /**
   * Materializes the current iterator chain into an array.
   *
   * It preserves the original keys (unlike {@see Flow::pack()}).
   * @return array
   */
  function all ()
  {
    if (!isset ($this->data))
      $this->data = iterator_to_array ($this->it);
    return $this->data;
  }

  /**
   * Appends one or more iterators to the current one and sets a new iterator that iterates over all of them.
   * @param Traversable[] $list Iterators to append.
   * @return $this
   */
  function append ($list)
  {
    $a = new AppendIterator;
    $a->append ($this->getIterator ());
    foreach ($list as $it)
      $a->append (self::iteratorFrom ($it));
    $this->setIterator ($a);
    return $this;
  }

  /**
   * Instantiates an outer iterator and chains it to the current iterator.
   *
   * Ex:
   * ```
   *   Query::range (1,10)->apply ('RegexIterator', '/^1/')->all ()
   * ```
   * @param string $iteratorClass The name of an iterator or iterator aggregate class whose constructor receives an
   *                              iterator as a first argument.
   * @param mixed  ...$args       Additional arguments for the external iterator's constructor.
   * @return $this
   */
  function apply ($iteratorClass)
  {
    $args    = func_get_args ();
    $args[0] = $this->getIterator ();
    $c       = new \ReflectionClass ($iteratorClass);
    $this->setIterator ($c->newInstanceArgs ($args));
    return $this;
  }

  /**
   * Memoize the current iterator's values so that subsequent iterations will not need to iterate it again.
   * @return $this
   */
  function cache ()
  {
    $this->setIterator (new CachedIterator ($this->getIterator ()));
    return $this;
  }

  /**
   * Assumes the current iterator is composed of arrays or iterators and sets a new iterator that iterates over all of
   * them.
   * @return $this
   */
  function concat ()
  {
    $a = new AppendIterator;
    foreach ($this->getIterator () as $it)
      $a->append (self::iteratorFrom ($it));
    $this->setIterator ($a);
    return $this;
  }

  /**
   * Drops the last `$n` elements from the iteration.
   *
   * Note: this also materializes the data and reindexes it.
   * @param int $n
   * @return $this
   */
  function drop ($n = 1)
  {
    $this->pack ();
    $this->data = array_slice ($this->data, 0, -$n);
    return $this;
  }

  /**
   * Calls a function for every element in the iterator.
   * @param callable $fn A callback that receives the current value and key; it can, optionally, return `false` to break
   *                     the loop.
   * @return $this
   */
  function each (callable $fn)
  {
    foreach ($this->getIterator () as $k => $v)
      if ($fn ($v, $k) === false) break;
    return $this;
  }

  /**
   * Replaces each value on the current iterator by the values generated by a new iterator.
   * @param callable $fn A callback that receives the current outer iterator item's value and key and returns the
   *                     corresponding inner Traversable or array.
   * @return $this
   */
  function expand (callable $fn)
  {
    $this->setIterator (new MacroIterator ($this->getIterator (), $fn));
    return $this;
  }

  /**
   * Gets the current value from the composite iterator and iterates to the next.
   *
   * This is a shortcut way of reading one single data item from the iteration.
   *
   * @return mixed|false `false` when the iteration is finished.
   */
  function get ()
  {
    $it = $this->getIterator ();
    if ($it->valid ()) {
      $v = $it->current ();
      $it->next ();
      return $v;
    }
    return false;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Retrieves an external iterator.
   * <p>This allows instances of this class to be iterated (ex. on foreach loops).
   * <p>This is also used internally to make sure any array data stored internally is converted to an iterator before
   * being used.
   * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
   * @return Iterator
   */
  public function getIterator ()
  {
    if (isset ($this->data)) {
      $this->setIterator (new ArrayIterator ($this->data));
      unset ($this->data);
    }
    return $this->it;
  }

  /**
   * Replaces values by the corresponding keys.
   * @return $this
   */
  function keys ()
  {
    $this->setIterator (new MapIterator ($this->getIterator (), function ($v, $k) { return $k; }));
    return $this;
  }

  /**
   * Repeats the iteration `$n` items.
   * <p>If `$n` > iterator count, this will have no effect.
   * @param int $n
   * @return $this
   */
  function loop ($n)
  {
    $this->setIterator (new LoopIterator ($this->getIterator ()));
    $this->it->loop ($n);
    return $this;
  }

  /**
   * Transforms each input data item.
   * @param callable $fn A callback that receives a value and a key and returns the new value.<br>
   *                     It can also receive the key by reference and change it.
   *                     <p>Ex:<code>  ->map (function ($v, &$k) { $k = $k * 10; return $v * 100; })</code>
   * @return $this
   */
  function map (callable $fn)
  {
    $this->setIterator (new MapIterator ($this->getIterator (), $fn));
    return $this;
  }

  /**
   * Transforms each input data item, optionally filtering it out.
   * @param callable $fn A callback that receives a value and key and returns a new value or `null` to discard it.<br>
   *                     It can also receive the key by reference and change it.
   *                     <p>Ex:<code>  ->mapAndFilter (function ($v, &$k) { $k = $k * 10; return $v > 5 ? $v * 100 :
   *                     null; })</code>
   * @return $this
   */
  function mapAndFilter (callable $fn)
  {
    $this->setIterator (new CallbackFilterIterator (new MapIterator ($this->getIterator (), $fn), function ($v) {
      return isset ($v);
    }));
    return $this;
  }

  /**
   * Makes the iteration non-rewindable.
   * @return $this
   */
  function noRewind ()
  {
    $this->setIterator (new NoRewindIterator($this->getIterator ()));
    return $this;
  }

  /**
   * Iterates only the first `$n` items.
   * <p>If `$n >` iterator count or `$n < 0`, this will have no effect.
   *
   * Note: this method is not equivalent to `limit (0, $n)`, for it can handle any kind of keys.
   * @param int $n How many iterations, at most.
   * @return $this
   */
  function only ($n)
  {
    $this->setIterator (new LoopIterator ($this->getIterator ()));
    $this->it->limit ($n);
    return $this;
  }

  /**
   * Materializes and reindexes the current data into a series of sequential integer keys.
   *
   * This is useful to extract the data as a linear array with no discontinuous keys.
   *
   * This is faster than {@see reindex()} bit it materializes the data. This should usually be the last
   * operation to perform before retrieving the results.
   * @return $this
   */
  function pack ()
  {
    $this->data = isset ($this->data) ? array_values ($this->data) : iterator_to_array ($this->it, false);
    return $this;
  }

  /**
   * Wraps a recursive iterator over the current iterator.
   * @param callable $fn A callback that receives the current node's value, key and nesting depth, and returns an
   *                     array or {@see Traversable} for the node's children or `null` if the node has no
   *                     children.
   * @return $this
   */
  function recursive (callable $fn)
  {
    $this->setIterator (new RecursiveIteratorIterator (new RecursiveIterator ($this->getIterator (), $fn)));
    return $this;
  }

  /**
   * Transforms data into arrays of regular expression matches for each item.
   * @param string $regexp     The regular expression to match.
   * @param int    $preg_flags The regular expression flags. Can be a combination of: PREG_PATTERN_ORDER,
   *                           PREG_SET_ORDER, PREG_OFFSET_CAPTURE.
   * @param bool   $useKeys    When `true`, the iterated keys will be used instead of the corresponding values.
   * @return $this
   */
  function regex ($regexp, $preg_flags = 0, $useKeys = false)
  {
    $this->setIterator (
      new RegexIterator ($this->getIterator (), $regexp, RegexIterator::ALL_MATCHES,
        $useKeys ? RegexIterator::USE_KEY : 0,
        $preg_flags)
    );
    return $this;
  }

  /**
   * Transforms data by extracting the first regular expression match for each item.
   * @param string $regexp     The regular expression to match.
   * @param int    $preg_flags The regular expression flags. Can be 0 or PREG_OFFSET_CAPTURE.
   * @param bool   $useKeys    When `true`, the iterated keys will be used instead of the corresponding values.
   * @return $this
   */
  function regexExtract ($regexp, $preg_flags = 0, $useKeys = false)
  {
    $this->setIterator (
      new RegexIterator ($this->getIterator (), $regexp, RegexIterator::GET_MATCH,
        $useKeys ? RegexIterator::USE_KEY : 0,
        $preg_flags)
    );
    return $this;
  }

  /**
   * Transforms each string data item into another using a regular expression.
   * @param string $regexp      The regular expression to match.
   * @param string $replaceWith Literal content with $N placeholders, where N is the capture group index.
   * @param bool   $useKeys     When `true`, the iterated keys will be used instead of the corresponding values.
   * @return $this
   */
  function regexMap ($regexp, $replaceWith, $useKeys = false)
  {
    $this->setIterator (
      new RegexIterator ($this->getIterator (), $regexp, RegexIterator::REPLACE, $useKeys ? RegexIterator::USE_KEY : 0)
    );
    $this->it->replacement = $replaceWith;
    return $this;
  }

  /**
   * Splits each data item into arrays of strings using a regular expression.
   * @param string $regexp     The regular expression to match.
   * @param int    $preg_flags The regular expression flags. Can be a combination of: PREG_SPLIT_NO_EMPTY,
   *                           PREG_SPLIT_DELIM_CAPTURE, PREG_SPLIT_OFFSET_CAPTURE.
   * @param bool   $useKeys    When `true`, the iterated keys will be used instead of the corresponding values.
   * @return $this
   */
  function regexSplit ($regexp, $preg_flags = 0, $useKeys = false)
  {
    $this->setIterator (
      new RegexIterator ($this->getIterator (), $regexp, RegexIterator::SPLIT, $useKeys ? RegexIterator::USE_KEY : 0,
        $preg_flags)
    );
    return $this;
  }

  /**
   * Reindexes the current data into a series of sequential integer values, starting from the specified value,
   * @param int $i  The new starting value for the keys sequence.
   * @param int $st The incremental step.
   * @return $this
   */
  function reindex ($i = 0, $st = 1)
  {
    $this->setIterator (
      new MapIterator ($this->getIterator (), function ($v, &$k) use (&$i, $st) {
        $k = $i;
        $i += $st;
        return $v;
      })
    );
    return $this;
  }

  /**
   * Reverses the order of iteration.
   *
   * Note: this method materializes the data.
   * @param bool $preserveKeys If set to `true` numeric keys are preserved. Non-numeric keys are not affected by this
   *                           setting and will always be preserved.
   * @return $this
   */
  function reverse ($preserveKeys = false)
  {
    $this->pack ();
    $this->data = array_reverse ($this->data, $preserveKeys);
    return $this;
  }

  /**
   * Sets the internal iterator.
   *
   * Not recommended for external use. This is used internally to update the first iterator on the chain.
   * @param mixed $it Should be a Traversable or an array.
   * @return $this
   */
  function setIterator ($it)
  {
    $this->it = self::iteratorFrom ($it);
    return $this;
  }

  /**
   * Skips the first `$n` elements from the iteration.
   * @param int $n
   * @return $this
   */
  function skip ($n = 1)
  {
    return $this->slice ($n);
  }

  /**
   * Limits iteration to the specified range.
   * @param int $offset Starts at 0.
   * @param int $count  -1 = all.
   * @return $this
   */
  function slice ($offset = 0, $count = -1)
  {
    $this->setIterator (new LimitIterator ($this->getIterator (), $offset, $count));
    return $this;
  }

  /**
   * Sorts the data by its keys.
   *
   * Note: this method materializes the data.
   * @param string   $type  The type of sort to perform.<br>
   *                        One of:
   *                        'asort' | 'arsort' | 'krsort' | 'ksort' | 'natcasesort' | 'natsort' | 'rsort' | 'shuffle' |
   *                        'sort' | 'uasort' | 'uksort' | 'usort'
   * @param int      $flags One or more of the SORT_XXX constants.
   * @param callable $fn    Can only be specified for sort types beginning with letter `u` (ex: `usort`).
   * @return $this
   */
  function sort ($type = 'sort', $flags = SORT_REGULAR, callable $fn = null)
  {
    if (!isset (self::$SORT_TYPES[$type]))
      throw new InvalidArgumentException ("Bad sort type: $type");
    $n = self::$SORT_TYPES[$type];
    $this->all ();  // force materialization of data.
    switch ($n) {
      case 1:
        $type ($this->data);
        break;
      case 2:
        $type ($this->data, $flags);
        break;
      case 3:
        $type ($this->data, $fn);
        break;
    }
    return $this;
  }

  /**
   * Replaces the current data set by another.
   * @param callable $fn A callback that receives as argument an array of the current data and returns the
   *                     new data array.
   * @return $this
   */
  function swap (callable $fn)
  {
    $this->pack ();
    $this->data = $fn ($this->data);
    return $this;
  }

  /**
   * Filters data by a condition.
   * @param callable $fn A callback that receives the element and its key and returns `true` for the elements that
   *                     should be kept.
   * @return $this
   */
  function where (callable $fn)
  {
    $this->setIterator (new CallbackFilterIterator ($this->getIterator (), $fn));
    return $this;
  }

  /**
   * Filters data using a regular expression test.
   * @param string $regexp     The regular expression to match.
   * @param int    $preg_flags The regular expression flags. Can be 0 or PREG_OFFSET_CAPTURE.
   * @param bool   $useKeys    When `true`, the iterated keys will be used instead of the corresponding values.
   * @return $this
   */
  function whereMatch ($regexp, $preg_flags = 0, $useKeys = false)
  {
    $this->setIterator (new RegexIterator ($this->getIterator (), $regexp, RegexIterator::MATCH,
      $useKeys ? RegexIterator::USE_KEY : 0, $preg_flags));
    return $this;
  }

}
