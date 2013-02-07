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

use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Client\Exceptions;
use Bread\Promise;

class Router {
  public static $routes = array();

  public function route(Request $request) {
    $routes = static::$routes;
    foreach ($routes as $route) {
      $route = new Route\Model($route);
      if (preg_match($route->pattern, $request->uri, $matches)) {
        $matches = array_merge(array(
          'controller' => null, 'action' => null
        ), $route->defaults, $matches);
        if (!is_subclass_of($matches['controller'], 'Bread\Controller')) {
          return Promise\When::reject(new Exceptions\NotFound(
            $matches['controller']));
        }
        $arguments = array_intersect_key($matches, array_flip((array) $route->arguments));
        return Promise\When::resolve(array(
          $matches['controller'], $matches['action'], $arguments
        ));
      }
    }
    return Promise\When::reject(new Exceptions\NotFound($request->uri));
  }
}
