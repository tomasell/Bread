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
use Bread\Networking\HTTP\Response;
use Bread\Networking\HTTP\Exception;
use Bread\Networking\HTTP\Client\Exceptions;
use Bread\Promise;

class Dispatcher {
  public function dispatch(Request $request, Response $response) {
    $router = new Router();
    $router->route($request)->then(function ($result) use ($request, $response) {
      list($Controller, $action, $arguments) = $result;
      if (!is_subclass_of($Controller, 'Bread\Controller')) {
        return Promise\When::reject(new Exceptions\NotFound($request->uri));
      }
      $controller = new $Controller($request, $response);
      $callback = array(
        $controller, $action
      );
      if (!is_callable($callback)) {
        return Promise\When::reject(new Exceptions\NotFound($request->uri));
      }
      return Promise\When::resolve(call_user_func_array($callback, $arguments));
    })->then(function ($output) use ($response) {
      $response->end($output);
    }, function (\Exception $exception) use ($response) {
      $response->status($exception->getCode());
      $response->end($exception->getMessage());
    });
  }
}
