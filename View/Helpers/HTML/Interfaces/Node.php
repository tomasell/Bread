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

namespace Bread\View\Helpers\HTML\Interfaces;

use Bread\View\Helpers\DOM;

interface Node extends DOM\Interfaces\Node {
  /**
   * Adds the specified class(es) to each of the set of matched elements.
   */
  public function addClass();

  /**
   * Determine whether any of the matched elements are assigned the given class.
   */
  public function hasClass();

  /**
   * Remove a single class, multiple classes, or all classes from each element
   * in the set of matched elements.
   */
  public function removeClass();

  /**
   * Add or remove one or more classes from each element in the set of matched
   * elements, depending on either the class’s presence or the value of the
   * switch argument.
   */
  public function toggleClass();

  /**
   * Get the HTML contents of the first element in the set of matched elements
   * or set the HTML contents of every matched element.
   */
  public function html();
}