<?php
/*
 * Created on Fri Apr 01 2022
 *
 * Copyright (c) 2022 Christian Backus (Chrissileinus)
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Chrissileinus\Config;

trait singleton
{
  /** Singleton start */
  private static $instance = null;

  function __construct()
  {
  }

  /**
   * _
   * same as getInstance
   *
   * @return self
   */
  public static function _(): self
  {
    return self::getInstance();
  }

  /**
   * getInstance
   *
   * @return self
   */
  public static function getInstance(): self
  {
    if (null === self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }
}
