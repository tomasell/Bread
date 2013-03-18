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

namespace Bread;

use Bread\Configuration;
use Bread\Networking\HTTP\Request;
use Bread\Networking\HTTP\Response;
use Bread\Networking\HTTP\Client\Exceptions;

/**
 * Application controller class for organization of business logic.
 */
abstract class Controller {
  /**
   * The HTTP request to control
   *
   * @var Request $request
   */
  protected $request;

  /**
   * The HTTP response to generate
   *
   * @var Response $response
   */
  protected $response;

  protected $data;

  public function __construct(Request $request, Response $response) {
    $this->request = $request;
    $this->response = $response;
    switch ($this->request->method) {
    case 'OPTIONS':
      break;
    case 'HEAD':
      $this->response->on('headers', array($this->response, 'end'));
    case 'GET':
      break;
    case 'POST':
    case 'PUT':
      $this->data = new Promise\Deferred();
      $this->request->on('data', function ($data) use ($buffer) {
        $this->data->progress($data);
      })->on('end', function () {
        $data = stream_get_contents($this->request->body->stream, $this->request->length, 0);
        switch ($this->request->type) {
        case 'application/json':
          $json = json_decode($data);
          $this->data->resolve($json);
          break;
        case 'application/x-www-form-urlencoded':
          parse_str($data, $form);
          $this->data->resolve($form);
          break;
        }
      });
      break;
    case 'DELETE':
      break;
    case 'TRACE':
    case 'CONNECT':
    default:
      throw new Exceptions\MethodNotAllowed($this->request->method);
    }
  }

  public function browse() {
    $search = (array) $this->request->query['search'];
    $options = (array) $this->request->query['options'];
    return Model::fetch($search, $options)->then(function ($models) {
      $view = new View($this->request, $this->response);
      return $this->response->end($view->table($models));
    });
  }

  public function read($id) {
    return Model::first(['id' => $id])->then(function ($model) {
      $view = new View($this->request, $this->response);
      return $this->response->end($view->read($model));
    });
  }

  public function edit($id) {
    return Model::first(['id' => $id])->then(function ($model) {
      $view = new View($this->request, $this->response);
      return $this->response->end($view->form($model));
    });
  }

  public function add() {
    switch ($this->request->method) {
    case 'POST':
      return $this->data->then(function ($attributes) {
        $view = new View($this->request, $this->response);
        $model = new Model($attributes);
        $model->store();
        $this->response->status(201);
        return $this->response->end($view->form());
      });
    case 'GET':
      $view = new View($this->request, $this->response);
      return $this->response->end($view->form());
    }
  }

  public function delete($id) {
    return Model::first(['id' => $id])->then(function ($model) {
      return $model->delete()->then(function () {
        $this->response->message("Post successfully deleted.");
        return $this->response->end();
      });
    });
  }

  public static function get($key = null) {
    $class = get_called_class();
    return Configuration\Manager::get($class, $key);
  }

  public static function Model($attributes) {
    $class = get_called_class();
    $model = str_replace('Controller', 'Model', $class);
    return new $model($attributes);
  }

  public static function View(Request $request, Response $response) {
    $class = get_called_class();
    $view = str_replace('Controller', 'View', $class);
    return new $view($request, $response);
  }
}
