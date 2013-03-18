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

namespace Bread\View\Helpers\UI\Menu;

use Bread;
use Bread\View\Helpers\HTML;

class View extends Bread\View {
  public function table($models = []) {
    $page = new HTML\Page(static::get('page.theme.path'));
    $table = new HTML\Table($page, 
      ['Menu name',
      'Title',
      'Hyperref']);
    foreach ($models as $model) {
      $row = $table->row();
      $table->cell($row, $model->name);
      $table->cell($row, $model->title);
      $table->cell($row, $model->href);
    }
    $page('article')->append($table);
    return $this->compose($page);
  }

  public function form() {
    $page = new HTML\Page(static::get('page.theme.path'));
    $form = new HTML\Form($page);
    $form->text('name', 'Menu name');
    $form->text('title', 'Title');
    $form->text('href', 'Hyperref');
    $form->actions(['submit' => 'Submit']);
    $page('article')->append($form);
    return $this->compose($page);
  }
}
