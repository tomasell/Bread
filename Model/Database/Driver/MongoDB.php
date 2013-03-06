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
use Bread\Model\Database;
use Bread\Model\Interfaces;
use Bread\Promise;
use DateTime;

use MongoClient, MongoId, MongoDate, MongoRegex, MongoBinData, MongoDBRef;

class MongoDB implements Interfaces\Database {
  protected $client;
  protected $link;

  public function __construct($url) {
    $database = ltrim(parse_url($url, PHP_URL_PATH), '/');
    $this->client = new MongoClient($url);
    $this->link = $this->client->$database;
  }

  public function store(Bread\Model &$model) {
    $class = get_class($model);
    $collection = $this->collection($class);
    $document = $model->attributes();
    $this->denormalize($class, $document);
    $key = $model->key();
    $this->denormalize($class, $key);
    $this->link->$collection->update($key, $document, array(
      'upsert' => true, 'multiple' => false
    ));
    $this->link->$collection->ensureIndex(array_fill_keys($class::$key, 1), array(
      'unique' => true
    ));
    return $this->promise($model);
  }

  public function delete(Bread\Model $model) {
    $collection = $this->collection($model);
    return $this->promise($this->link->$collection->remove($model->key()));
  }

  public function count($class, $search = array(), $options = array()) {
    return $this->promise($this->cursor($class, $search, $options)->count(true));
  }

  public function first($class, $search = array(), $options = array()) {
    $options['limit'] = 1;
    return $this->fetch($class, $search, $options)->then('current');
  }

  public function fetch($class, $search = array(), $options = array()) {
    $models = array();
    $documents = $this->cursor($class, $search, $options);
    foreach ($documents as $document) {
      $this->normalize($class, $document);
      $model = new $class($document);
      $models[] = $model;
    }
    return empty($models) ? Promise\When::reject() : $this->promise($models);
  }

  public function purge($class) {
    $collection = $this->collection($class);
    $this->link->$collection->drop();
    return $this->promise();
  }

  protected function promise($result = true) {
    return Promise\When::resolve($result);
  }

  protected function collection($class) {
    $class = is_object($class) ? get_class($class) : $class;
    return $class;
  }

  protected function className($collection) {
    return $collection;
  }

  protected function cursor($class, $search = array(), $options = array()) {
    $collection = $this->collection($class);
    $this->denormalize($class, $search);
    $cursor = $this->link->$collection->find($search, array(
      '_id' => false
    ));
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

  protected function normalize($class, &$document) {
    foreach ($document as $field => &$value) {
      if ($value instanceof MongoId) {
        $value = (string) $value;
      }
      elseif ($value instanceof MongoDate) {
        $value = new DateTime('@' . $value->sec);
      }
      elseif ($value instanceof MongoBinData) {
        $value = (string) $value;
      }
      elseif ($class::get("attributes.$field.multiple")) {
        $value = (array) $value;
      }
      elseif (Database\Reference::is($value)) {
        Database\Reference::fetch($value)->then(function ($model) use (&$value) {
          $value = $model;
        });
      }
      elseif (MongoDBRef::isRef($value)) {
        $value = MongoDBRef::get($this->link, $value);
        $this->normalize($class, $value);
      }
      elseif (is_array($value)) {
        $this->normalize($class, $value);
      }
    }
  }

  protected function denormalize($class, &$document) {
    foreach ($document as $field => &$value) {
      $attribute = $field;
      $explode = explode('.', $attribute);
      $field = array_shift($explode);
      if ($explode) {
        unset($document[$attribute]);
        $type = $class::get("attributes.$field.type");
        $type::fetch(array(
          implode('.', $explode) => $value
        ))->then(function ($models) use (&$value) {
          $value = array(
            '$in' => $models
          );
        });
        $document[$field] = &$value;
      }
      if ($value instanceof Bread\Model) {
        $value->store();
        $reference = new Database\Reference($value);
        $value = (array) $reference;
      }
      elseif ($value instanceof DateTime) {
        $value = new MongoDate($value->format('U'));
      }
      elseif (is_array($value)) {
        $this->denormalize($class, $value);
      }
    }
  }
}
