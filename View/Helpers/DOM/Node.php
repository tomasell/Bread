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

namespace Bread\View\Helpers\DOM;

use DOMNodeList, DOMNode, DOMText;

class Node implements Interfaces\Node {
  public $nodes = array();
  protected $document;
  protected $position;

  public function __construct(Document $document, $name, $value = null,
    $attributes = array()) {
    $this->document = $document;
    if (is_string($name)) {
      $this->nodes = $this->document->create($name, $value, $attributes)->nodes;
    }
    elseif ($name instanceof DOMNode) {
      $this->nodes = array(
        $name
      );
    }
    elseif ($name instanceof DOMNodeList) {
      foreach ($name as $node) {
        $this->nodes[] = $node;
      }
    }
    elseif ($name instanceof Node) {
      $this->nodes = $name->nodes;
    }
    elseif (is_array($name)) {
      $this->nodes = $name;
    }
    $this->position = 0;
  }

  public function __clone() {
    foreach ($this->nodes as &$node) {
      $node = $node->cloneNode(true);
    }
  }

  public function __call($method, $args = array()) {
    switch ($method) {
    /**
     * Create a deep copy of the set of matched elements.
     */
    case 'clone':
      return clone $this;
    /**
     * Remove all child nodes of the set of matched elements from the DOM.
     */
    case 'empty':
      foreach ($this->nodes as $node) {
        foreach ($node->childNodes as $child) {
          $node->removeChild($child);
        }
      }
    }
  }

  public function __invoke($name) {
    return $this->document->__invoke($name, $this);
  }

  public function current() {
    return new static($this->document, $this->nodes[$this->position]);
  }

  public function key() {
    return $this->position;
  }

  public function next() {
    ++$this->position;
  }

  public function rewind() {
    $this->position = 0;
  }

  public function valid() {
    return isset($this->nodes[$this->position]);
  }

  public function count() {
    return count($this->nodes);
  }

  /**
   * Insert content, specified by the parameter, after each element in the set
   * of matched elements.
   */
  public function after($content) {
    if (!($content instanceof Node)) {
      $content = call_user_func_array(array(
        $this->document, 'create'
      ), func_get_args());
    }
    foreach ($this->nodes as $i => $node) {
      foreach ($content->nodes as $n) {
        if ($i) {
          $n = $n->cloneNode(true);
        }
        $node->parentNode->insertBefore($n, $node->nextSibling);
      }
    }
    return $this;
  }

  /**
   * Insert content, specified by the parameter, to the end of each element in
   * the set of matched elements.
   */
  public function append($content) {
    if (!($content instanceof Node)) {
      $content = call_user_func_array(array(
        $this->document, 'create'
      ), func_get_args());
    }
    foreach ($this->nodes as $i => $node) {
      foreach ($content->nodes as $n) {
        if ($i) {
          $n = $n->cloneNode(true);
        }
        $node->appendChild($n);
      }
    }
    return $content;
  }

  /**
   * Insert every element in the set of matched elements to the end of the
   * target.
   */
  public function appendTo($target) {
    foreach ($target->nodes as $t) {
      foreach ($this->nodes as $node) {
        $t->appendChild($node);
      }
    }
    return $this;
  }

  /**
   * Get the value of an attribute for the first element in the set of matched
   * elements or set one or more attributes for every matched element.
   */
  public function attr($attributes) {
    $args = func_get_args();
    if (!is_array($attributes) && !isset($args[1])) {
      return $this->nodes[0]->getAttribute($attributes);
    }
    if (!is_array($attributes)) {
      $attributes = array(
        $args[0] => $args[1]
      );
    }
    foreach ($this->nodes as $node) {
      foreach ($attributes as $attribute => $value) {
        if (false === $value) {
          $node->removeAttribute($attribute);
        }
        else {
          if (true === $value) {
            $value = $attribute;
          }
          $node->setAttribute($attribute, $value);
        }
      }
    }
    return $this;
  }

  /**
   * Insert content, specified by the parameter, before each element in the set
   * of matched elements.
   */
  public function before($content) {
    if (!($content instanceof Node)) {
      $content = call_user_func_array(array(
        $this->document, 'create'
      ), func_get_args());
    }
    foreach ($this->nodes as $i => $node) {
      foreach ($content->nodes as $n) {
        if ($i) {
          $n = $n->cloneNode(true);
        }
        $node->parentNode->insertBefore($n, $node);
      }
    }
    return $this;
  }

  /**
   * Remove the set of matched elements from the DOM.
   */
  public function detach() {
    foreach ($this->nodes as $node) {
      $node->parentNode->removeChild($node);
    }
    return $this;
  }

  /**
   * Insert every element in the set of matched elements after the target.
   */
  public function insertAfter($target) {
    foreach ($target->nodes as $t) {
      foreach ($this->nodes as $node) {
        $t->ownerDocument->insertBefore($node, $t->nextSibling);
      }
    }
    return $this;
  }

  /**
   * Insert every element in the set of matched elements before the target.
   */
  public function insertBefore($target) {
    foreach ($target->nodes as $t) {
      foreach ($this->nodes as $node) {
        $t->ownerDocument->insertBefore($node, $t);
      }
    }
    return $this;
  }

  /**
   * Insert content, specified by the parameter, to the beginning of each
   * element in the set of matched elements.
   */
  public function prepend($content) {
    if (!($content instanceof Node)) {
      $content = call_user_func_array(array(
        $this->document, 'create'
      ), func_get_args());
    }
    foreach ($this->nodes as $i => $node) {
      foreach ($content->nodes as $n) {
        if ($i) {
          $n = $n->cloneNode(true);
        }
        $node->insertBefore($n, $node->firstChild);
      }
    }
    return $content;
  }

  /**
   * Insert every element in the set of matched elements to the beginning of the
   * target.
   */
  public function prependTo($target) {
    foreach ($target->nodes as $t) {
      foreach ($this->nodes as $i => $node) {
        $t->insertBefore($node, $t->firstChild);
      }
    }
    return $this;
  }

  /**
   * Get the value of a property for the first element in the set of matched
   * elements or set one or more properties for every matched element.
   */
  public function prop($properties) {
    return call_user_func_array(array(
      $this, 'attr'
    ), func_get_args());
  }

  /**
   * Remove the set of matched elements from the DOM.
   */
  public function remove() {
    return $this->detach();
  }

  /**
   * Remove an attribute from each element in the set of matched elements.
   */
  public function removeAttr($attribute) {
    foreach ($this->nodes as $node) {
      $node->removeAttribute($attribute);
    }
    return $this;
  }

  /**
   * Remove a property for the set of matched elements.
   */
  public function removeProp($property) {
    $this->removeAttr($property);
    return $this;
  }

  /**
   * Replace each target element with the set of matched elements.
   */
  public function replaceAll(Interfaces\Node $targets) {
    // TODO replaceAll
    return $this;
  }

  /**
   * Replace each element in the set of matched elements with the provided new
   * content and return the set of elements that was removed.
   */
  public function replaceWith($content) {
    // TODO replaceWith
    return $content;
  }

  /**
   * Get the combined text contents of each element in the set of matched
   * elements, including their descendants, or set the text contents of the
   * matched elements.
   */
  public function text($text) {
    $text = func_get_args();
    if (empty($text)) {
      $text = '';
      foreach ($this->nodes as $node) {
        $text .= $node->textContent;
      }
      return $text;
    }
    $this->empty();
    foreach ($this->nodes as $node) {
      $node->appendChild(new DOMText(array_shift($text)));
    }
    return $this;
  }

  /**
   * Remove the parents of the set of matched elements from the DOM, leaving the
   * matched elements in their place.
   */
  public function unwrap() {
    // TODO unwrap
    return $this;
  }

  /**
   * Get the current value of the first element in the set of matched elements
   * or set the value of every matched element.
   */
  public function val() {
    $args = func_get_args();
    if (empty($args)) {
      return $this->nodes[0]->textValue;
    }
    $this->text(array_shift($args));
    return $this;
  }

  /**
   * Wrap an HTML structure around each element in the set of matched elements.
   */
  public function wrap() {
    // TODO wrap
    return $this;
  }

  /**
   * Wrap an HTML structure around all elements in the set of matched elements.
   */
  public function wrapAll() {
    // TODO wrapAll
    return $this;
  }

  /**
   * Wrap an HTML structure around the content of each element in the set of
   * matched elements.
   */
  public function wrapInner() {
    // TODO wrapInner
    return $this;
  }
}
