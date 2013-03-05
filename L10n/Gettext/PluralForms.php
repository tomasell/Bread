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

namespace Bread\L10n\Gettext;

use Bread\Exception;
use Bread\L10n\Gettext\Parser;

class PluralForms {
  protected $parser;

  public function __construct() {
    $this->parser = new Parser();
  }

  public function parse($p) {
    $plural_str = $this->extractPluralExpr($p);
    return $this->parser->parse($plural_str);
  }

  public function compile($p) {
    // Handle trues and falses as 0 and 1
    $imply = function ($val) {
      return ($val === true ? 1 : ($val ? $val : 0));
    };

    $ast = $this->parse($p);
    return function ($n) use ($ast, $imply) {
      $interpreter = $this->interpreter($ast);
      return $imply($interpreter($n));
    };
  }

  protected function interpreter($ast) {
    return function ($n) use ($ast) {
      switch ($ast['type']) {
      case 'GROUP':
        $interpreter = $this->interpreter($ast['expr']);
        return $interpreter($n);
      case 'TERNARY':
        $interpreter = $this->interpreter($ast['expr']);
        if ($interpreter($n)) {
          $interpreter = $this->interpreter($ast['truthy']);
          return $interpreter($n);
        }
        $interpreter = $this->interpreter($ast['falsey']);
        return $interpreter($n);
      case 'OR':
        $left = $this->interpreter($ast['left']);
        $right = $this->interpreter($ast['right']);
        return $left($n) || $right($n);
      case 'AND':
        $left = $this->interpreter($ast['left']);
        $right = $this->interpreter($ast['right']);
        return $left($n) && $right($n);
      case 'LT':
        $left = $this->interpreter($ast['left']);
        $right = $this->interpreter($ast['right']);
        return $left($n) < $right($n);
      case 'GT':
        $left = $this->interpreter($ast['left']);
        $right = $this->interpreter($ast['right']);
        return $left($n) > $right($n);
      case 'LTE':
        $left = $this->interpreter($ast['left']);
        $right = $this->interpreter($ast['right']);
        return $left($n) <= $right($n);
      case 'GTE':
        $left = $this->interpreter($ast['left']);
        $right = $this->interpreter($ast['right']);
        return $left($n) >= $right($n);
      case 'EQ':
        $left = $this->interpreter($ast['left']);
        $right = $this->interpreter($ast['right']);
        return $left($n) == $right($n);
      case 'NEQ':
        $left = $this->interpreter($ast['left']);
        $right = $this->interpreter($ast['right']);
        return $left($n) != $right($n);
      case 'MOD':
        $left = $this->interpreter($ast['left']);
        $right = $this->interpreter($ast['right']);
        return $left($n) % $right($n);
      case 'VAR':
        return $n;
      case 'NUM':
        return $ast['val'];
      default:
        throw new Exception("Invalid Token found.");
      }
    };
  }

  protected function extractPluralExpr($p) {
    $p = trim($p);
    if (!preg_match('/;$/', $p)) {
      $p .= ';';
    }
    $nplurals_re = '/nplurals\=(\d+);/';
    $plural_re = '/plural\=(.*);/';
    preg_match($nplurals_re, $p, $nplurals_matches);
    $res = [];
    $plural_matches = null;
    if (count($nplurals_matches) > 1) {
      $res['nplurals'] = $nplurals_matches[1];
    }
    else {
      throw new Exception('nplurals not found in plural_forms string: ' . $p);
    }
    $p = preg_replace($nplurals_re, '', $p);
    preg_match($plural_re, $p, $plural_matches);
    if (!($plural_matches && count($plural_matches) > 1)) {
      throw new Exception('`plural` expression not found: ' . $p);
    }
    return $plural_matches[1];
  }
}
