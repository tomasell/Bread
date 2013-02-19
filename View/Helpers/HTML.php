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

namespace Bread\View\Helpers;

use Bread\View\Helpers\HTML;

class HTML extends DOM\Node implements HTML\Interfaces\Node {
  public function __construct(HTML\Page $page, $name) {
    parent::__construct($page, $name);
  }

  /**
   * Adds the specified class(es) to each of the set of matched elements.
   */
  public function addClass($class) {
    foreach ($this->nodes as $node) {
      $classes = $this->getClasses($node);
      $classes[] = $class;
      $this->setClasses($node, $classes);
    }
  }

  /**
   * Determine whether any of the matched elements are assigned the given class.
   */
  public function hasClass($class) {
    foreach ($this->nodes as $node) {
      if (in_array($class, $this->getClasses($node))) {
        return true;
      }
    }
    return false;
  }

  /**
   * Remove a single class, multiple classes, or all classes from each element
   * in the set of matched elements.
   */
  public function removeClass($class) {
    foreach ($this->nodes as $node) {
      $this->setClasses($node, array_diff($this->getClasses($node), explode(' ', $class)));
    }
  }

  /**
   * Add or remove one or more classes from each element in the set of matched
   * elements, depending on either the class’s presence or the value of the
   * switch argument.
   */
  public function toggleClass($class) {
    $classes = explode(' ', $class);
    foreach ($classes as $class) {
      if ($this->hasClass($class)) {
        $this->removeClass($class);
      }
      else {
        $this->addClass($class);
      }
    }
  }

  /**
   * Get the HTML contents of the first element in the set of matched elements
   * or set the HTML contents of every matched element.
   */
  public function html() {
    return;
  }

  protected function getClasses($node) {
    $classes = $node->getAttribute('class');
    return explode(' ', $classes);
  }

  protected function setClasses($node, $array) {
    $class = implode(' ', $array);
    $node->setAttribute('class', $class);
  }
}
