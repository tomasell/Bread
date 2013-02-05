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

namespace Bread\Networking\HTTP\Server;

class Connector {
  public static $connectors = array(
    'cli-server' => 'Bread\Networking\HTTP\Server\Connectors\Apache2',
    'apache2handler' => 'Bread\Networking\HTTP\Server\Connectors\Apache2'
  );

  public static function factory($sapi = PHP_SAPI) {
    if (!isset(static::$connectors[$sapi])) {
      throw new Exceptions\InternalServerError("No connector for SAPI $sapi");
    }
    return new static::$connectors[$sapi]();
  }
}
