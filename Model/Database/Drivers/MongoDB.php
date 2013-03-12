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

namespace Bread\Model\Database\Drivers;

use Bread\Dough\ClassLoader as CL;
use Bread\Configuration\Manager as CM;
use Bread\Model\Database;
use Bread\Promise;
use Exception;
use DateTime;
use MongoClient, MongoCollection, MongoCursor, MongoId, MongoCode, MongoDate;
use MongoBinData, MongoRegex, MongoDBRef;

class MongoDB implements Database\Interfaces\Driver {
  const DATETIME_FORMAT = 'U';

  protected $connection;
  protected $database;

  public function __construct($url) {
    $database = ltrim(parse_url($url, PHP_URL_PATH), '/');
    $this->connection = new MongoClient($url);
    $this->database = $this->connection->$database;
  }

  public function __destruct() {
    $this->connection->close();
  }

  public function store($object) {
    $class = get_class($object);
    $keys = $this->keys($object);
    $document = array();
    // TODO protected method to extract object attributes
    foreach ($object as $field => $value) {
      $document[$field] = $value;
    }
    $this->denormalizeDocument($keys);
    $this->denormalizeDocument($document);
    $collection = $this->collection($class);
    try {
      $collection->update($keys, $document, array(
        'upsert' => true,
        'multiple' => false
      ));
    } catch (Exception $e) {
      // FIXME rethrow exception?
      return Promise\When::reject($e);
    }
    $this->ensureIndexes($class, array_keys($keys));
    return Promise\When::resolve($object);
  }

  public function delete($object) {
    $class = get_class($object);
    $keys = $this->keys($object);
    $this->denormalizeDocument($keys);
    $collection = $this->collection($class);
    $collection->remove($keys);
    return Promise\When::resolve($object);
  }

  public function count($class, $search = array(), $options = array()) {
    return $this->cursor($class, $search, $options)->then(function ($documents) {
      return $documents->count(true);
    });
  }

  public function first($class, $search = array(), $options = array()) {
    $options['limit'] = 1;
    return $this->fetch($class, $search, $options)->then('current');
  }

  public function fetch($class, $search = array(), $options = array()) {
    return $this->cursor($class, $search, $options)->then(function ($documents) use (
      $class) {
      $normalized = array();
      foreach ($documents as $document) {
        $normalized[] = $this->normalizeDocument($document)->then(function (
          $document) use ($class) {
          return new $class($document);
        });
      }
      return Promise\When::all($normalized);
    });
  }

  public function purge($class, $search = array(), $options = array()) {
    if (empty($search) && empty($options)) {
      return Promise\When::resolve($this->collection($class)->drop());
    }
    return $this->cursor($class, $search, $options)->then(function ($documents) use (
      $class) {
      foreach ($documents as $document) {
        $this->collection($class)->remove($document);
      }
    });
  }

  protected function cursor($class, $search = array(), $options = array()) {
    return $this->denormalizeSearch($class, $search)->then(function ($search) use (
      $class, $options) {
      $cursor = $this->collection($class)->find($search, array('_id' => false));
      foreach ($options as $key => $option) {
        switch ($key) {
        case 'sort':
        case 'skip':
        case 'limit':
          $option = is_array($option) ? array_map('intval', $option)
            : intval($option);
          $cursor = $cursor->$key($option);
        }
      }
      return $cursor;
    });
  }

  protected function collection($class) {
    $class = is_object($class) ? get_class($class) : $class;
    $collection = $class;
    return $this->database->$collection;
  }

  protected function className($collection) {
    $class = $collection;
    return $class;
  }

  protected function keys($object) {
    $class = get_class($object);
    $keys = array();
    foreach ((array) CM::get($class, 'keys') as $key) {
      $keys[$key] = $object->$key;
    }
    return $keys;
  }

  protected function normalizeDocument($document) {
    $promises = array();
    foreach ($document as $field => &$value) {
      if ($value instanceof MongoId) {
        $value = (string) $value;
      }
      elseif ($value instanceof MongoCode) {
        $value = (string) $value;
      }
      elseif ($value instanceof MongoDate) {
        $value = new DateTime('@' . $value->sec);
      }
      elseif ($value instanceof MongoRegex) {
        $value = (string) $value;
      }
      elseif ($value instanceof MongoBinData) {
        $value = (string) $value;
      }
      elseif (MongoDBRef::isRef($value)) {
        $value = MongoDBRef::get($this->database, $value);
        $promises[$field] = $this->normalizeDocument($value);
      }
      elseif (Database\Reference::is($value)) {
        $promises[$field] = Database\Reference::fetch($value);
      }
      elseif (is_array($value)) {
        $promises[$field] = $this->normalizeDocument($value);
      }
    }
    return Promise\When::all($promises, function ($values) use ($document) {
      foreach ($values as $field => $value) {
        $document[$field] = $value;
      }
      return $document;
    });
  }

  protected function denormalizeDocument(&$document) {
    foreach ($document as $field => &$value) {
      if ($value instanceof DateTime) {
        $value = new MongoDate($value->format(self::DATETIME_FORMAT));
      }
      elseif (is_object($value)) {
        Database::driver(get_class($value))->store($value);
        $value = new Database\Reference($value);
      }
      elseif (is_array($value)) {
        $this->denormalizeDocument($value);
      }
    }
  }

  protected function denormalizeSearch($class, $search) {
    $recursion = array();
    $promises = array();
    foreach ($search as $field => &$condition) {
      switch ($field) {
      case '$and':
      case '$or':
      case '$not':
        $recursion[$field] = array();
        foreach ($condition as $c) {
          $recursion[$field][] = $this->denormalizeSearch($class, $c);
        }
        break;
      default:
        $key = $field;
        $explode = explode('.', $field);
        $field = array_shift($explode);
        if ($explode) {
          $type = CM::get($class, "attributes.$field.type");
          if (CL::classExists($type)) {
            unset($search[$key]);
            $promises[$field] = Database::driver($type)->fetch($type, array(
              implode('.', $explode) => $condition
            ));
          }
        }
      }
    }
    return Promise\When::all($recursion, function ($s) use ($search) {
      foreach ($s as $logic => $conditions) {
        $search[$logic] = $conditions;
      }
      return $search;
    })->then(function ($search) use ($promises) {
      return Promise\When::all($promises, function ($conditions) use ($search) {
        foreach ($conditions as $field => $value) {
          $search[$field] = array('$in' => (array) $value);
        }
        $this->denormalizeDocument($search);
        return $search;
      });
    });
  }

  protected function ensureIndexes($class, $keys) {
    $collection = $this->collection($class);
    $collection->ensureIndex(array_fill_keys($keys, 1), array('unique' => true));
    $indexInfo = $collection->getIndexInfo();
    foreach ((array) CM::get($class, 'attributes') as $field => $description) {
      if (!isset($description['type'])) {
        continue;
      }
      switch ($description['type']) {
      case 'point':
      case 'polygon':
        foreach ($indexInfo as $index) {
          if (!isset($index['key'][$field])) {
            $collection->ensureIndex(array($field => '2d'));
          }
        }
      }
    }
  }
}
