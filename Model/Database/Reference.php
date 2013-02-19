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
  public $key;

  public function __construct(Bread\Model $model) {
    $this->ref = get_class($model);
    $this->key = $model->key();
  }

  public static function is($array) {
    if ($array instanceof Reference) {
      return true;
    }
    if (isset($array['ref']) && isset($array['key'])) {
      return true;
    }
    return false;
  }

  public static function fetch($array) {
    if (!static::is($array)) {
      throw new Bread\Exception("Not a valid reference");
    }
    $class = $array['ref'];
    return $class::first($array['key']);
  }
}