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

use Bread\View\Helpers\DOM;
use DOMXPath;

class Page extends DOM\Document {
  protected $composed = false;
  
  public function __construct($html = null) {
    parent::__construct('html');
    if ($html) {
      $this->load($html);
    }
    else {
      $this->root = new Node($this, $this->root);
      $this->head = $this->root->append('head');
      $this->charset = $this->head->append('meta')->charset = 'utf-8';
      $this->title = $this->head->append('title');
      $this->body = $this->root->append('body');
    }
  }
  
  public function __invoke($name) {
    return new Node($this, parent::__invoke($name));
  }

  public function create($name, $value = null, $attributes = array()) {
    $classes = explode('.', $name);
    $name = array_shift($classes);
    if (is_array($value)) {
      $attributes = $value;
      $value = null;
    }
    if (!empty($classes)) {
      if (isset($attributes['class'])) {
        $classes[] = $attributes['class'];
      }
      $attributes = array_merge($attributes, array(
          'class' => implode(" ", array_unique($classes))
      ));
    }
    list($name, $id) = explode('#', $name)
    + array(
        null, null
    );
    if ($id) {
      $attributes['id'] = $id;
    }
    $element = $this->document->createElement($name, $value);
    foreach ($attributes as $name => $value) {
      $element->setAttribute($name, $value);
    }
    return new Node($this, $element);
  }
  
  public function load($filename) {
    libxml_use_internal_errors(true);
    $this->document->loadHTMLFile($filename);
    $this->xpath = new DOMXPath($this->document);
    $this->root = new Node($this, $this->document->documentElement);
    libxml_clear_errors();
    libxml_use_internal_errors(false);
  }
  
  public function save($node = null, $options = LIBXML_NOXMLDECL) {
    $this->compose();
    return $this->document->saveHTML($node);
  }
  
  public function compose() {
    if ($this->composed) {
      return;
    }
    foreach ($this('[data-bread-block]') as $block) {
      $name = $block->attr('data-bread-block');
    }
    $this->composed = true;
  }
}