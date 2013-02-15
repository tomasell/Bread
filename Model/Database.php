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

namespace Bread\Model;

use Bread, Exception;

class Database {
  protected static $configuration = array(
    'drivers' => array(
      'mongodb' => 'Bread\Model\Database\MongoDB'
    )
  );

  public function __construct($url) {
    $scheme = parse_url($url, PHP_URL_SCHEME);
    if (!isset(static::$configuration['drivers'][$scheme])) {
      throw new Exception('Driver for {$scheme} not found.');
    }
    $Driver = static::$configuration['drivers'][$scheme];
    $this->driver = new $Driver($url);
  }

  public function count($class, $search = array(), $options = array()) {
    return $this->driver->count($class, $search, $options);
  }

  public function store(Bread\Model $model) {
    return $this->driver->store($model);
  }
}