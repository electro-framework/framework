# RouterInterface

A service that assists in routing an HTTP request to one or more request handlers.
<p>A handler (also called a *routable*) may generate an HTTP response and/or route to other handlers.
<p>The request will traverse a graph of interconnected handlers, until a full HTTP response is generated or the
graph is exhausted.

### Routes

A route is defined by calling the `route()` method and passing a routable as parameter.

A routable can handle a single route or handle multiple routes.

### Routables

A routable is a term that designates a set of types that are allowed to be used as routing destinations and that are
interpreted in a type-specific way by the router.

A given routable can be one of these concrete types:

- a `Traversable` object
- an `array`
- an invokable class name
- a `callable` (object, method or function)
- a *generator* function

Each of these types are interpreted by the router as explained below.

#### Iterable routables

If the routable is of an iterable type (i.e. an array, a `Traversable` or a *generator*), it is a
sequence of key/value pairs that defines routes.

A route is defined by a set of keys and their corresponding values:
- Keys define route matching patterns.
- Values define routables that will be invoked if the corresponding match succeeds.

The matching patterns are a DSL that will be explained further below.

###### Example

    $router->route ([
      MyMiddleware::class,
      'users' => UsersPage::class,
      SomeOtherMiddleware1::class,
      SomeOtherMiddleware2::class,
      'user' => [
        'GET :id' => function () { ... },
        'POST' => function () { ... }
      ]
    ]);

##### Route iteration order

Routes (with or without keys) are executed in the same order that they are defined.

Keyless routes (which, in reality, have auto-incrementing integer keys) are always run (they always match).  
Therefore, the example above is equivalent to:

    $router->route ([
      0 => MyMiddleware::class,
      'users' => UsersPage::class,
      1 => SomeOtherMiddleware1::class,
      2 => SomeOtherMiddleware2::class,
      'user' => [
        'GET :id' => function () { ... },
        'POST' => function () { ... }
      ]
    ]);

and the keys iteration order is: 

- `0`
- `'users'`
- `1`
- `2`
- `'user'`
- `'GET :id'`
- `'POST'`

##### Traditional middleware

The example above highlights a major feature: **you may interleave *keyless* routables with keyed routables.**

They are ideal for implementing traditional middleware (filters, loggers, etc.).

A middleware must implement `MiddlewareInterface` or a compatible call signature.
They are executed sequentally, but only if each middleware calls the provided "next" argument.

When a middleare does not call the "next" argument, the router immediately returns the response to the previous
route/middlware (even if the middleware returns nothing).

#### Routables implementing `MiddlewareInterface`

These should have 3 arguments: the request, the response and a "next" callable.

If a name of a class is given, the router will instantiate that class via dependency-injection
and then invoke it as middleware.

Otherwise, it will invoke the callable directly, supplying the 3 standard middleware arguments.

###### Example

    $router->route ([
      'users' => UsersPage::class,
      'user/:id' => function (ServerRequestInteface $request, ResponseInterface $response, callable $next) {
        $id = $request->getAttribute(':id');
        // do something
      },
      'other' => function () {
        // You can ommit unneeded parameters
      }
    ]);

#### Factory routables

They can have any number of arguments and they are dependency-injected when invoked.

You can use this kind of routable for:
- instantiating an object that implements `MiddlewareInterface` but that needs
additional configuration to be performed on it before it is invoked;
- instantiating a factory that needs configuration for creating the actual routable instance.

So make the router recognize a function as being a factory, you must **tag** it with `@make`.
> You'll find the explanation for tags further below.

###### Example

    $router->route ([
      'user/:id' => [
        '@make' => function (UserPage $page) {
          $page->someProperty = value;
          return $page; // MUST return a routable
        })
      ]
    ]);

Your factory **MUST** return a routable instance (usually the same provided argument, but not always).

If the returned routable is again a configurable router, the process is repeated recursively.
 
##### Returning nothing or `null`

If the factory returns nothing (or null), execution proceeds to the next route.

This is different from the behaviour of middleware, which when it returns nothing, it's the same as returning
the current response. You can't have that here, as factories are not middleare and they do not receive a response instance.

This behaviour also allows you to simply run a factory to setup something before matching some additional routes.


### Handling requests

On a middleware where you whish to perform routing, before using the router you must call the `for()` method,
passing it the current request, the current response and the "next" handler.

`for()` will return a new `RouterInterface` instance configured to the given paramenters.  
You may then call `route()` on it to perform the routing.

A middleware or routable should do one of the following:

- return a new Response object
- add to the content of the current Response object and return it
- return nothing (it's the same as returning the current Response)
- return the result of calling the "next" argument

A routable should call "next" if it decides to NOT handle the request or if it wants to delegate that handling to
another routable **at the same depth or above** but still capture the generated response and, eventually, modify it. It
operates in the same fashion as standard middleware.

#### Handling sub-routes

On the other hand, if a routable is not the final routing destination and it needs to delegate processing to sub-routes,
it should return the result of invoking the router again for the desired sub-routes.

###### Example

For the path `user/37/records`:

    return $router->route ([
    
      // You are not required to use a callable for a sub-route
      
      'user'   => [
        ':id' => UserPage::class,
      ],
      
      // But you can use one, if you whish
      
      'user' => function () {         // $request, $response and $next can be ommited
        // it may do something here...
        
        return $router->route ([      // sub-routing
          ':id' => UserPage::class,
        ]);
      },
      
      // A more complex and contrived example
      
      'author' => function ($request, $response, $next) {
        $path = $request->getUri ()->getPath ();          // $path == '37/records'
        if (!is_numeric ($path[0])) return $next ();      // give up and proceed to the next route
        return $router->route ([
          ':id' => function ($request) {                  // you can ommit the remaining params
            $path = $request->getUri ()->getPath ();      // $path == 'records'
            $id = $request->getAttribute (':id');         // $id == 37
            return "Hello World!";
          }
        ]);
      }
    ]);

### Route patterns

Any iterable that exposes a set of keys and values is interpreted as a routing table.  
Each key/value pair is a route.  
Each key is a pattern written in a DSL that instructs the router on how to mach one or more URL path segments.

> There are no patterns for matching other parts of the URL (like the protocol, domain, etc.). You don't need them.
You can simple define a routable that checks those elements using plain PHP.

#### DSL syntax

A route pattern can have one of the following forms:

- `@tag`
  - This is a **tag**. It always matches, but it changes the way the associated routable is interpreted.  
    Currently, these are the supported tags:
    
    - `@make` indicates the routable is a factory with dependency-injectable arguments.

- `[method ][*|literal|:param]...`
  - `method` can be `GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS` or any other HTTP method. If not specified, it maches any
  method.
  
  - `*` matches any path. If not specified, it only macthes an empty path, which means either the path is the root path
  (/) or the path segment matched by the previous pattern is the final one on the URL.
  
  - `literal` is any literal text. You can use any character excluding the ones reserved for pattern matching.
    You may also use `/` for matching multiple segments.
    > The matcher assumes there is an implicit `/` at the end of any expression, but it also matches if the URL
    does not end with `/`.  
    > Ex: `'user/37'` matches `'user/37/records'` and `'user/37'`, but not `'user/371'`
  
  - `:param` matches any character sequence until `/` and saves it as a route parameter with the given name.
    > Ex: when `'user/:id'` matches `'users/3'`, the router sets the route parameter `id` to 3.

> **Legend**

> `[]` encloses optional elements

> `|` separates alternatives

> `...` indicates a repetition of the last element

> all other characters are themselves or a label for a character sequence (ex: `param`).

