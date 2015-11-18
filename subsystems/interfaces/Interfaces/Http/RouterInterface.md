# RouterInterface

A service that assists in routing an HTTP request to one or more request handlers.
<p>A handler (also called a *routable*) may generate an HTTP response and/or route to other handlers.
<p>The request will traverse a tree of interconnected handlers, until a full HTTP response is generated or the
tree traversal is completed.

> Note that not all nodes on the tree will be visited, as most routes will not match the request's URL.

Selenia's router has some unique features that set it apart from most other routers out there.  

For instance, it is a **hierarchical** router, while most other routers are linear ones.

### Middleware

HTTP middleware is a central concept on Selenia's routing.

All routable elements on a routing graph are either route mappings, middleware or factories that produce middleware.

#### What is middleware?

On most frameworks, middleware is used for implementing any logic you might stick between the request/response life cycle that's not necessarily part of your application logic.

For example:

- Adding session support
- Parsing query strings
- Implementing rate-limiting
- Sniffing for bot traffic
- Adding logging
- Parsing JSON sent on the request
- Compressing the response
- Anything else related to the request/response lifecycle

Selenia expands the traditional middleware concept by making your application logic (Controllers or Components) also be
implemented as middleware.

So, **on Selenia, middleware is a broad concept the applies to anything that processes HTTP requests and generates/modifies
HTTP responses**.

You can assemble middlewares into a **middleware stack**, which is a set of concentric middleware layers.

**A middleware stack is not a queue**; each middleware is implemented as a decorator pattern: it wraps around the next
middleware on the stack and interceps HTTP requests and responses that flow in and out of it.

If a middleware does not specifically invoke the next one, that and all subsequent middlewares will never be invoked; the current middleware's
response will start immediately moving backwards to the outer layers.
When the first layer is reached, the response will be sent to the HTTP client (usually the web browser).

On Selenia, middlewares are responsible for providing all the application logic and all HTTP-related logic.

#### The router middleware

Selenia has a main, application-level, middleware stack. At a specific point on that stack, there is a **router middleware**.

The router middleware makes the request/response flow into a parallel tree-like structure of routes, comprised of patterns
and routable elements (where most of them are also middleware).

If the request is not handled on that routing tree, it proceeds to the next middleware on the main stack, which usually just sends
back a `404 Not Found` response.

Now, let's talk about routing.

### Routes

A route is defined by calling the router's `route()` method and passing it a routable as argument.

A routable can handle a single route or multiple routes.

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

###### Routing example

    return $router
      ->for ($request, $response, $next)
      ->route (
      [
        MyMiddleware::class,
        'users' => UsersPage::class,
        SomeOtherMiddleware1::class,
        SomeOtherMiddleware2::class,
        'user' =>
          [
            'GET: @id' => function () { ... },
            'POST:' => function () { ... }
          ]
      ]
    );

##### Route iteration order

Routes (with or without keys) are executed in the same order on which they are defined.

Keyless routes, in reality, have auto-assigned, self-incrementing, integer keys.

Therefore, the example above is equivalent to:

    return $router
      ->for ($request, $response, $next)
      ->route (
      [
        0       => MyMiddleware::class,
        'users' => UsersPage::class,
        1       => SomeOtherMiddleware1::class,
        2       => SomeOtherMiddleware2::class,
        'user'  =>
          [
            'GET: @id' => function () { ... },
            'POST:'    => function () { ... }
          ]
      ]
    );

So, the iteration order for the keys is:

- `0`
- `'users'`
- `1`
- `2`
- `'user'`
- `'GET: @id'`
- `'POST:'`

**Important:** integer keys are never matched against the request's URL; their corresponding routables
**always run**.


##### Middleware

The example above highlights a major feature of Selenia's router: **you can mix routables with keys and routables without
keys**, and they'll be executed in order.

As keyless routables always run, they are ideal for implementing traditional **middleware** (like filters, loggers, etc.).

A middleware must implement `MiddlewareInterface` or a compatible call signature.

#### Routables implementing `MiddlewareInterface` or a compatible callable

If a name of a class is given, the router will instantiate that class via dependency-injection
and then invoke it as middleware.

Otherwise, it will invoke the callable directly, supplying the 3 standard middleware arguments:

- the request,
- the response,
- a "next" callable.

The function may declare less parameters, as long as they are declared in that order. The extra arguments will still
be supplied, but they'll be ignored by the called function.

###### Allowable call signatures

    function ($request, $response, $next) {}
    function ($request, $response) {}
    function ($request) {}
    function () {}

You can also type hint the parameters:

    function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {}

###### Example of routes with middleware routables

    $router->route (
      [
        'users' => UsersPage::class,
        
        'user/@id' => function (ServerRequestInteface $request, ResponseInterface $response, callable $next) {
          $id = $request->getAttribute('@id');
          // do something
          return $next ();  // jumps to the next route ('other')
        },
        
        'other' => function () {
          // You can ommit unneeded parameters
        }
      ]
    );

##### Middleware execution flow

Middlewares are executed sequentally, but only if each middleware calls the provided "next" argument.

When a middleare does not call the "next" argument, the router immediately returns the response to the previous
route/middleware (even if the curent middleware returns nothing).

Inside a middlware function, the router instance is mutated to reflect the current request and response at the point.
So, you don't nned to call 

#### Factory routables

Factories can have any number of arguments and they are dependency-injected when invoked.

You can use them for:

- instantiating an object that implements `MiddlewareInterface` but that needs
additional configuration to be performed on it before it is invoked;
- instantiating a factory that needs configuration for creating the actual routable instance.

To make the router recognize a function as being a factory, you must wrap it in a `factory()` decorator.

###### Example

    $router->route (
      [
        'user/@id' => factory (function (UserPage $page) {
          $page->someProperty = value;
          return $page;
        })
      ]
    );

Factories **MUST** return a routable instance (usually the same provided argument, but not always).

If the returned routable is, again, a configurable router, the process is repeated recursively.
 
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

###### Example

    function ($request, $response, $next) {
      return $router
        ->for ($request, $response, $next)
        ->route (...);
    }
    
> **Note:** most other examples on this documentation ommit this setup code, and begin with `$router->route(...)`.
 This is just for the sake of brevity.

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

    $router->route (
      [
        // You are not required to use a callable for a sub-route
        
        'user'   => [
          '@id' => UserPage::class,
        ],
        
        // But you can use one, if you whish
        
        'user' => function () {         // $request, $response and $next can be ommited
          // it may do something here...
          
          return $router->route ([      // sub-routing
            '@id' => UserPage::class,
          ]);
        },
        
        // A more complex and contrived example
        
        'author' => function ($request, $response, $next) {
          $path = $request->getUri ()->getPath ();        // $path == '37/records'
          if (!is_numeric ($path[0])) return $next ();    // give up and proceed to the next route
          return $router->route (
            [
              '@id' => function ($request) {              // you can ommit the remaining params
                $path = $request->getUri ()->getPath ();  // $path == 'records'
                $id = $request->getAttribute ('@id');     // $id == 37
                return "Hello World!";
              }
            ]
          );
        }
      ]
    );

### Route patterns

Any iterable that exposes a set of keys and values is interpreted as a routing table.  
Each key/value pair is a route.  
Each key is a pattern written in a DSL that instructs the router on how to mach one or more URL path segments.

> There are no patterns for matching other parts of the URL (like the protocol, domain, etc.). You don't need patterns
for that; you can simply define a routable that checks those elements using plain PHP and the request object.

#### DSL syntax

A route pattern has the following syntax:

`[methods:][*|literal|@param]...`

> **Grammar**

> `[]` encloses optional elements

> `|` separates alternatives

> `...` indicates a repetition of the last element

> all other characters are themselves or define a label for a character sequence (ex: `param`).

- `method` can be one or more of `GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS` or any other HTTP method.
  If not specified, it maches any method. To specify more than one, separate them with `|`.

- `*` matches any path. If not specified, it only macthes an empty path, which means either the path is the root path
`/` or the path segment matched by the previous pattern is the final one on the URL.

- `literal` is any literal text. You can use any character excluding the ones reserved for pattern matching.
  You may also use `/` for matching multiple segments.
  > The matcher assumes there is an implicit `/` at the end of any pattern, but it also matches if the URL
  does not end with `/`.  
  > Ex: `'user/37'` matches `'user/37/records'` and `'user/37'`, but not `'user/371'`

- `@param` matches any character sequence until `/` and saves it as a route parameter with the given name.
  You can retrieve it later trought he request object, calling `getAttribute('@param')`.
  > Ex: when `'user/:id'` matches `'users/3'`, the router sets the route parameter `@id` to the value 3. You can read
    it later by calling `$request->getAttribute('@id')`.

