<?php
/*
 * Created on Wed Nov 03 2021
 *
 * Copyright (c) 2021 Christian Backus (Chrissileinus)
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Chrissileinus\Config;

use ArrayIterator;

class Storage implements \ArrayAccess, \Serializable, \JsonSerializable, \IteratorAggregate, \Traversable
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
  /** Singleton end */

  protected static $static = [];

  /**
   * Set a static values
   *
   * @param  array $static
   * @return void
   */
  public static function setStatic(array $static)
  {
    self::$static = $static;
  }

  protected static $storage = [];

  /**
   * integrate
   *
   * @param  mixed $args
   * * `string` with path to file or a glob. All content get read and parsed by yaml_parse, json_decode or unserialize. The result get integrated into storage.
   * * `string` with yaml_parse, json_decode or unserialize parsable string gat also integrated into storage.
   * * `array` or `object` get integrated into the storage with array_replace_recursive($storage, $arg).
   *
   * @return void
   */
  public static function integrate(...$args)
  {
    foreach ($args as $arg) {
      if (is_array($arg) || is_object($arg)) {
        self::integrateArray((array) $arg);
        continue;
      }

      if (is_file($arg) && $content = file_get_contents($arg)) {
        self::integrateContent($content);
        continue;
      }

      if ($files = glob($arg)) {
        foreach ($files as $file) {
          if ($content = file_get_contents($file)) {
            self::integrateContent($content);
          }
        }
        continue;
      }

      if (is_string($arg)) {
        self::integrateContent($arg);
        continue;
      }
    }
  }

  /**
   * clear
   *
   * @return void
   */
  public static function clear(): void
  {
    self::$storage = [];
  }

  protected static function walk_recursive_remove_static(array $static, array $array)
  {
    foreach ($array as $k => $v) {
      if (is_array($v) && isset($static[$k])) {
        $array[$k] = self::walk_recursive_remove_static($static[$k], $v);
      } else {
        if (isset($static[$k])) {
          unset($array[$k]);
        }
      }
    }

    return $array;
  }

  /**
   * integrateArray
   *
   * @param  array $array
   * * get integrated into the storage with array_replace_recursive($storage, $array).
   *
   * @return void
   */
  private static function integrateArray(array $array): void
  {
    if (!self::$storage) {
      self::$storage = $array;
      return;
    }

    if (self::$storage && self::$static) {
      $array = self::walk_recursive_remove_static(self::$static, $array);
    }

    self::$storage = array_replace_recursive(self::$storage, $array);
  }

  /**
   * integrateContent
   *
   * @param  mixed $content
   * * get parsed with yaml_parse, json_decode or unserialize and the resulting array get processed with `integrateArray`.
   *
   * @return void
   */
  private static function integrateContent(string $content): void
  {
    if ($value = yaml_parse($content, 0, $ndocs, [
      '!php' => function ($value, $tag, $flags) {
        [$class, $const] = \explode('::', $value);
        return $class::getConstant($const);
      }
    ])) {
      if ($value != $content) { // Yea it is posible that the output is the same as te input.
        self::integrateArray((array) $value);
        return;
      }
    }

    if (
      $value = json_decode($content, false, 512, JSON_OBJECT_AS_ARRAY) &&
      json_last_error() == JSON_ERROR_NONE
    ) {
      self::integrateArray((array) $value);
      return;
    }

    if ($value = parse_ini_string($content, true, INI_SCANNER_TYPED)) {
      self::integrateArray((array) $value);
      return;
    }

    if ($value = @unserialize($content) && (is_array($value) || is_object($value))) {
      self::integrateArray((array) $value);
      return;
    }
  }


  public static function export(): array
  {
    return self::$storage;
  }

  /** ArrayAccess */
  public function offsetExists($offset): bool
  {
    return isset(self::$storage[$offset]);
  }

  public function offsetSet($offset, $value): void
  {
  }

  public function offsetGet($offset): mixed
  {
    return self::$storage[$offset];
  }

  public function offsetUnset($offset): void
  {
  }

  /** Serializable  */
  /**
   * serialize
   *
   * @return string|null
   */
  public function serialize(): ?string
  {
    return serialize(self::$storage);
  }
  public function unserialize(mixed $data): void
  {
    self::$storage = unserialize($data);
  }

  /** JsonSerializable */
  public function jsonSerialize(): mixed
  {
    return self::$storage;
  }

  /** */
  public function getIterator(): ArrayIterator
  {
    return new \ArrayIterator(self::$storage);
  }
}
