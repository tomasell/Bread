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

namespace Bread\Model\Database\Driver;

use Bread\Model\Database\Interfaces;
use Bread\Networking\HTTP;
use Bread;
use DateTime;

class CouchDB implements Interfaces\Driver {
  protected $client;
  protected $url;

  public function __construct($url) {
    $this->url = $url;
    $this->client = new HTTP\Client();
  }

  public function store(Bread\Model $model) {
  }

  public function delete(Bread\Model $model) {
  }

  public function purge($class) {
  }

  public function count($class, $search = array(), $options = array()) {
  }

  public function first($class, $search = array(), $options = array()) {
    $this->design($class)->then(array($this, 'view'));
  }

  public function fetch($class, $search = array(), $options = array()) {
  }

  protected function design($class) {
    $name = md5($class);
    return $this->client->get("/_design/{$name}")->then(null, function() use ($name) {
      $design = new Design\Document\Model($name);
      return $this->store($design);
    });
  }

  protected function createView($design, $search = array()) {
    $view = md5(json_encode($search));
  }
}
