<?php
namespace Selenia\Matisse\Interfaces;

/**
 * A compiler extension factory is an injectable class that provides a map of patterns to compiler extensions.
 * It can request any services it needs via its constructor function arguments.
 *
 * <p>Compiler extensions are callables (functions, methods or callable class instances) that are called by Matisse
 * when compiling the view, to transform tags into components or other tags, change component properties, apply mixins,
 * etc.
 *
 * <p>Each factory will be called only once on each application lifecycle; the result will be cached.
 */
interface CompilerExtensionFactoryInterface
{
  /**
   * Returns an associative array of `pattern => callable`, where the callable is a compiler extension.
   *
   * <p>A compiler extension is a callable with this signature:
   *         function (array & $node):void
   *
   * <p>A pattern is a string that can be either a markup tag name or a fully qualified PHP class name.
   *
   * <p>An extension is invoked before a component is instantiated, and it receives (by reference) an AST node
   * containing information about the component that will be created.
   *
   * <p>The extension can modify that node to influence the way the component will be created.<br>
   * It can:
   *
   * - set default values for any missing properties;
   * - modify any property in any way;
   * - perform properties validation and throw an exception if it fails;
   * - decide which component PHP class will be instantiated;
   * - change the tag name, either to:
   *   - prevent other extensions to further modify the node, or conversely, to
   *   - delegate the next step of transformation to other extensions.
   *
   * >**Important:** the node's component class can only be set **once**; after that, other changes to it will revert
   * to the original value upon exiting the extension.
   *
   * <p>For each node being processed, Matisse will call registered extensions (that match the pattern) sequentially in
   * the reverse order that they were registered.
   *
   * <p>For each registered pattern map, Matisse will try to match extensions in two steps:
   *
   * - match the tag name;
   * - match the PHP class name.
   *
   * Only one match can occur per step. After the final step, Matisse proceeds to the next registered pattern map.
   *
   * <p>After all maps are processed, Matisse will instantiate the component if a class name was set, otherwhise it aborts
   * the compilation with a "unknow tag" error.
   *
   * @return callable[]
   */
  function getMap ();

}
