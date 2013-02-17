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

use Bread;
use Bread\Model\Database\Interfaces;
use DateTime;
use MongoClient, MongoId, MongoDate, MongoRegex, MongoBinData, MongoDBRef;

class MongoDB implements Interfaces\Driver {
  protected $client;
  protected $link;

  public function __construct($url) {
    $database = ltrim(parse_url($url, PHP_URL_PATH), '/');
    $this->client = new MongoClient($url);
    $this->link = $this->client->$database;
  }

  public function store(Bread\Model $model) {
    $collection = $this->collection(get_class($model));
    $this->link->$collection->save($model);
    return $model;
  }

  public function delete(Bread\Model $model) {
  }

  public function purge($class) {
  }

  public function count($class, $search = array(), $options = array()) {
    return $this->cursor($class, $search, $options)->count(true);
  }

  public function first($class, $search = array(), $options = array()) {
    $options['limit'] = 1;
    $fetch = $this->fetch($class, $search, $options);
    return array_shift($fetch);
  }

  public function fetch($class, $search = array(), $options = array()) {
    $models = array();
    $documents = $this->cursor($class, $search, $options);
    foreach ($documents as $document) {
      $this->normalizeDocument($class, $document);
      //$model = new $class($document);
      $models[] = $document;//$model;
    }
    return $models;
  }

  protected function cursor($class, $search = array(), $options = array()) {
    $collection = $this->collection($class);
    $cursor = $this->link->$collection->find($search);
    foreach ($options as $key => $option) {
      switch ($key) {
      case 'skip':
      case 'limit':
      case 'sort':
        if ($option) {
          $cursor = $cursor->$key($option);
        }
      }
    }
    return $cursor;
  }

  protected function collection($class) {
    $class = is_object($class) ? get_class($class) : $class;
    return str_replace(NS, '.', $class);
  }

  protected function className($collection) {
    return str_replace('.', NS, $collection);
  }

  protected function normalizeDocument($class, &$document) {
    foreach ($document as $attribute => &$value) {
      if ($value instanceof MongoId) {
        $value = (string) $value;
      }
      elseif ($value instanceof MongoDate) {
        $value = new DateTime('@' . $value->sec);
      }
      elseif ($value instanceof MongoBinData) {
        $value = (string) $value;
      }
      elseif (MongoDBRef::isRef($value)) {
        $this->normalizeReference($value);
      }
      elseif (is_array($value)) {
        $this->normalizeDocument($class, $value);
      }
    }
  }

  protected function normalizeReference(&$reference) {
    $document = MongoDBRef::get($this->link, $reference);
    $class = $this->className($reference['$ref']);
    $reference = $class::first($document);
  }

  protected function denormalizeReference(&$model) {
    $class = get_class($model);
    if (!$first = $this->first($class, $model->attributes())) {
      $first = $this->store($model);
    }
    MongoDBRef::create($this->collection($class), new MongoId($first->_id));
  }
}
