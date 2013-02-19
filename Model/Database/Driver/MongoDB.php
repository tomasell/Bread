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
use Bread\Model;
use Bread\Promise;
use DateTime;

use MongoClient, MongoId, MongoDate, MongoRegex, MongoBinData, MongoDBRef;

class MongoDB implements Model\Interfaces\Database {
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
    return $this->fetch($class, $search, $options)->then(function($fetch) {
      return Promise\When::resolve(array_shift($fetch));
    });
  }

  public function fetch($class, $search = array(), $options = array()) {
    $models = array();
    $documents = $this->cursor($class, $search, $options);
    foreach ($documents as $document) {
      $this->normalizeDocument($document);
      $model = new $class($document);
      $models[] = $model;
    }
    return Promise\When::resolve($models);
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

  protected function normalizeDocument(&$document) {
    foreach ($document as &$field) {
      if ($field instanceof MongoId) {
        $field = (string) $field;
      }
      elseif ($field instanceof MongoDate) {
        $field = new DateTime('@' . $field->sec);
      }
      elseif ($field instanceof MongoBinData) {
        $field = (string) $field;
      }
      elseif (MongoDBRef::isRef($field)) {
        $field = MongoDBRef::get($this->link, $field);
        $this->normalizeDocument($field);
      }
      elseif (Model\Database\Reference::is($field)) {
        Model\Database\Reference::fetch($field)->then(function($model) use ($field) {
          $field = $model;
        });
      }
      elseif (is_array($field)) {
        $this->normalizeDocument($field);
        if ((bool) count(array_filter(array_keys($field), 'is_string'))) {
          $field = (object) $field;
        }
      }
    }
  }
}
