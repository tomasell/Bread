<?php
/**
 * Bread PHP Framework (http://github.com/saiv/Bread)
 * Copyright 2010-2012, SAIV Development Team <development@saiv.it>
 *
 * Licensed under a Creative Commons Attribution 3.0 Unported License.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright  Copyright 2010-2012, SAIV Development Team <development@saiv.it>
 * @link       http://github.com/saiv/Bread Bread PHP Framework
 * @package    Bread
 * @since      Bread PHP Framework
 * @license    http://creativecommons.org/licenses/by/3.0/
 */

namespace Bread\Routing;

use Bread\Configuration\Manager as CM;
use Bread\Dough\ClassLoader;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Networking\HTTP\Client\Exceptions;
use Bread\Promise;
use Bread\L10n\Inflector;

class Router {
  public static $routes = array();

  public function route(Request $request, Response $response) {
    return Route\Model::fetch()->then(function ($routes) use ($request,
      $response) {
      $routes = array_merge($routes, array_map(function ($route) {
        return new Route\Model($route);
      }, (array) CM::get(__CLASS__, 'routes')));
      foreach ($routes as $route) {
        $matches = array();
        if ($this->match($route, $request, $matches)) {
          $action = isset($matches['action']) ? $matches['action']
            : $route->action;
          $arguments = array_intersect_key($matches, array_flip($route->arguments));
          $arguments = array_merge($route->defaults, $arguments);
          if (!ClassLoader::classExists($route->controller)) {
            break;
          }
          $controller = new $route->controller($request, $response);
          $callback = array($controller, $action);
          if (!is_callable($callback)) {
            break;
          }
          return array($callback, $arguments);
        }
      }
      throw new Exceptions\NotFound($request->uri);
    });
  }

  protected function match(Route\Model $route, Request $request, &$matches) {
    foreach ($route->patterns as $attribute => $pattern) {
      if (!preg_match('/'.str_replace('/', '\/', $pattern) . '/', $request->$attribute, $_matches)) {
        return false;
      }
      $matches = array_merge($matches, $_matches);
    }
    return true;
  }
}
