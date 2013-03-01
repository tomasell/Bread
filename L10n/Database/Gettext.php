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

namespace Bread\L10n\Database;

use Bread;
use Bread\Model\Interfaces;
use Bread\Promise;

class Gettext implements Interfaces\Database {
  protected $directory;

  protected $categories = array(
    LC_CTYPE => 'LC_CTYPE',
    LC_NUMERIC => 'LC_NUMERIC',
    LC_TIME => 'LC_TIME',
    LC_COLLATE => 'LC_COLLATE',
    LC_MONETARY => 'LC_MONETARY',
    LC_MESSAGES => 'LC_MESSAGES',
    LC_ALL => 'LC_ALL'
  );

  public function __construct($url) {
    $this->directory = parse_url($url, PHP_URL_PATH) ? : BREAD_PRIVATE . DS . 'locale';
  }

  public function store(Bread\Model &$model) {
    $po = $this->directory . DS . $model->locale->code . DS . $this->categories[$model->category] . DS . $model->domain . ".po";
    if (!file_put_contents($po, (string) $model, FILE_APPEND)) {
      return Promise\When::reject();
    }
    return Promise\When::resolve($model);
  }

  public function delete(Bread\Model $model) {
    return Promise\When::reject();
  }

  public function count($class, $search = array(), $options = array()) {
    return Promise\When::reject();
  }

  public function first($class, $search = array(), $options = array()) {
    $search = array_merge(array(
      'domain' => 'default',
      'category' => LC_MESSAGES,
      'locale' => setlocale(LC_MESSAGES, 0),
      'msgid' => null,
      'msgid_plural' => null,
      ''
    ), $search);
    extract($search);
    return Promise\When::resolve(dcgettext($domain, $msgid, $category));
  }

  public function fetch($class, $search = array(), $options = array()) {
    return Promise\When::reject();
  }

  public function purge($class) {
    return Promise\When::reject();
  }
}
