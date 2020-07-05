<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Windwalker\Core\Package\PackageResolver;
use Windwalker\Core\Router\MainRouter;
use Windwalker\Middleware\MiddlewareInterface;
use Windwalker\Router\Exception\RouteNotFoundException;
use Windwalker\Router\Route;
use Windwalker\String\StringHelper;
use Windwalker\String\StringNormalise;
use Windwalker\Utilities\Arr;

/**
 * The RoutingMiddleware class.
 *
 * @since  3.0
 */
class RoutingMiddleware extends AbstractWebMiddleware
{
    /**
     * Middleware logic to be invoked.
     *
     * @param   Request                      $request  The request.
     * @param   Response                     $response The response.
     * @param   callable|MiddlewareInterface $next     The next middleware.
     *
     * @return  Response
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    public function __invoke(Request $request, Response $response, $next = null)
    {
        $router = $this->app->getRouter();

        $this->app->triggerEvent('onBeforeRouting', [
            'app' => $this->app,
            'router' => $router,
            'request' => $request,
            'response' => $response,
        ]);

        $route = $this->match($router);

        $this->app->triggerEvent('onAfterRouteMatched', [
            'app' => $this->app,
            'router' => $router,
            'matched' => $route,
            'request' => $request,
            'response' => $response,
        ]);

        $request = $this->handleMatched($route, $request);

        $this->app->triggerEvent('onAfterRouting', [
            'app' => $this->app,
            'router' => $router,
            'matched' => $route,
            'request' => $request,
            'response' => $response,
        ]);

        return $next($request, $response);
    }

    /**
     * match
     *
     * @param MainRouter $router
     * @param string     $route
     *
     * @return  Route
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    public function match(MainRouter $router, $route = null)
    {
        $route = $route ?: $this->app->uri->route;
        $route = $route ?: '/';

        $input   = $this->app->input;
        $request = $this->app->request;

        if ($request->hasHeader('X-HTTP-Method-Override')) {
            $method = $request->getHeaderLine('X-HTTP-Method-Override');
        } else {
            $method = $input->get('_method') ?: $input->getMethod();
        }

        // Pass variables to custom method
        if ($input->$method) {
            $httpMethod = $input->getMethod();

            $input->$method->setData($input->$httpMethod->toArray());
        }

        // Prepare option data
        $uri = $request->getUri();

        $options = [
            'scheme' => $uri->getScheme(),
            'host' => $uri->getHost(),
            'port' => $uri->getPort(),
        ];

        try {
            return $router->match($route, $method, $options);
        } catch (RouteNotFoundException $e) {
            // If simple_route is disabled, just continue throw the exception.
            if (!$this->app->get('routing.simple_route', false)) {
                throw $e;
            }

            // Simple routing
            $matched = $this->matchSimpleRouting($route, $method, $router);

            if ($matched === false) {
                throw new RouteNotFoundException($e->getMessage(), $e->getCode(), $e);
            }

            return $matched;
        }
    }

    /**
     * matchSimpleRouting
     *
     * @param string     $route
     * @param string     $method
     * @param MainRouter $router
     *
     * @return  bool|Route
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    protected function matchSimpleRouting($route, $method, MainRouter $router)
    {
        $route      = explode('/', $route);
        $controller = array_pop($route);
        $class      = StringNormalise::toClassNamespace(
            sprintf(
                '%s\Controller\%s\%s',
                implode($route, '\\'),
                ucfirst($controller),
                $router->fetchControllerSuffix($method)
            )
        );

        // Find package
        $ns = implode('\\', array_map('ucfirst', $route)) . '\\' . ucfirst(end($route)) . 'Package';

        $resolver = $this->getPackageResolver();

        // If package not found but class exists, try create one
        if (!$resolver->getAlias($ns) && class_exists($ns)) {
            $resolver->addPackage(end($route), new $ns());
        }

        // Get package, if not exists, return DefaultPackage
        $package = $resolver->resolvePackage($resolver->getAlias($ns));

        $packageName = $package ? $package->getName() : implode('.', $route);

        if (!class_exists($class)) {
            return false;
        }

        $matched = new Route($packageName . '@' . $controller, implode($route, '/'));

        $matched->setExtraValues(
            [
                'controller' => $class,
            ]
        );

        return $matched;
    }

    /**
     * getRouter
     *
     * @return  MainRouter
     */
    protected function getRouter()
    {
        return $this->app->container->get('router');
    }

    /**
     * getPackageResolver
     *
     * @return  PackageResolver
     */
    protected function getPackageResolver()
    {
        return $this->app->container->get('package.resolver');
    }

    /**
     * handleMatched
     *
     * @param Route   $route
     * @param Request $request
     *
     * @return Request
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    protected function handleMatched(Route $route, Request $request)
    {
        $name = $route->getName();

        [$packageName, $routeName] = StringHelper::explode('@', $name, 2, 'array_unshift');

        $variables = $route->getVariables();
        $extra     = $route->getExtraValues();
        $input     = $this->app->input;

        // Save to input & ServerRequest
        $input->merge($variables, true);
        // Don't forget to do an explicit set on the GET superglobal.
        $input->get->merge($variables, true);
        $request = $request->withQueryParams($input->get->getRawData());

        $this->app->server->setRequest($request);

        // Store to config
        $this->app->set('route', [
            'matched' => $route->getName(),
            'package' => $packageName,
            'short_name' => $routeName,
            'extra' => $extra,
        ]);

        // Package
        $package = $this->getPackageResolver()->resolvePackage($packageName);

        $this->app->container->share('current.package', $package);
        $this->app->container->share('current.route', $route);

        // Set middlewares
        $middlewares = $route->getExtra('middlewares') ?: [];

        foreach ($middlewares as $middleware) {
            $package->addMiddleware($middleware);
        }

        return $request->withAttribute('_controller', Arr::get($extra, 'controller'));
    }
}
