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

namespace Bread\Model\Database;

use Bread;

class Reference {
  public $ref;
  public $id;

  public function __construct(Bread\Model $model) {
    $this->{'$ref'} = get_class($model);
    $this->{'$id'} = $model->key();
  }

  public static function is($array) {
    if ($array instanceof Reference) {
      return true;
    }
    $array = (array) $array;
    if (isset($array['$ref']) && isset($array['$id'])) {
      return true;
    }
    return false;
  }

  public static function fetch($array) {
    if (!static::is($array)) {
      throw new Bread\Exception("Not a valid reference");
    }
    $array = (array) $array;
    $class = $array['$ref'];
    return $class::first($array['$id']);
  }
}