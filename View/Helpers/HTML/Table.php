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

namespace Bread\View\Helpers\HTML;

class Table extends Node {
  public $caption;
  public $thead;
  public $tbody;
  public $tfoot;

  public function __construct(Page $page, $columns = array()) {
    parent::__construct($page, 'table');
    $this->addClass('table');
    $this->thead = $this->append('thead')->append('tr');
    $this->tbody = $this->append('tbody');
    $this->tfoot = $this->append('tfoot');
    foreach ($columns as $column) {
      $this->thead->append('th')->text($column);
    }
  }

  public function row() {
    return $this->tbody->append('tr');
  }

  public function cell(Node $row, $content = null) {
    return $row->append('td')->text($content);
  }
}