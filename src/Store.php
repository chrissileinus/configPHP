<?php

namespace Chrissileinus\Config;

class Store implements \ArrayAccess, \Serializable, \JsonSerializable, \IteratorAggregate, \Traversable
{
  protected static $current = [];

  /**
   * set
   *
   * @param  mixed $args
   * Array or Abject will be merged in storage with array_replace_recursive($storage, $arg).
   *
   * A path to a file or a glob will be get read and parsed by yaml_parse, json_decode or unserialize. The result will also be merged into storage.
   *
   * A with yaml_parse, json_decode or unserialize parsable string will also be merged into storage.
   *
   *
   * @return void
   */
  public static function set(...$args)
  {
    foreach ($args as $arg) {
      if (is_array($arg) || is_object($arg)) {
        self::$current = array_replace_recursive(self::$current, (array) $arg);
        continue;
      }

      if (is_file($arg) && $content = file_get_contents($arg)) {
        self::importContent($content);
        continue;
      }

      if ($files = glob($arg)) {
        foreach ($files as $file) {
          if ($content = file_get_contents($file)) {
            self::importContent($content);
          }
        }
        continue;
      }

      if (is_string($arg)) {
        self::importContent($arg);
        continue;
      }
    }
  }

  private static function importContent(string $content)
  {
    if ($value = \yaml_parse($content, 0, $_, [
      '!php' => function ($value, $tag, $flags) {
        [$class, $const] = explode('::', $value);
        return $class::getConstant($const);
      }
    ])) {
      self::$current = array_replace_recursive(self::$current, (array) $value);
      return;
    }

    if ($value = json_decode($content, false, 512, JSON_OBJECT_AS_ARRAY)) {
      self::$current = array_replace_recursive(self::$current, (array) $value);
      return;
    }

    if ($value = unserialize($content, false) && (is_array($value) || is_object($value))) {
      self::$current = array_replace_recursive(self::$current, (array) $value);
      return;
    }
  }

  public static function get()
  {
    return (object) self::$current;
  }

  /** ArrayAccess */
  public function offsetExists($offset): bool
  {
    return isset(self::$current[$offset]);
  }

  public function offsetSet($offset, $value)
  {
  }

  public function offsetGet($offset): mixed
  {
    return self::$current[$offset];
  }

  public function offsetUnset($offset)
  {
  }

  /** Serializable  */
  public function serialize(): string
  {
    return serialize(self::$current);
  }

  public function unserialize($data)
  {
    self::$current = unserialize($data);
  }

  /** JsonSerializable */
  public function jsonSerialize()
  {
    return self::$current;
  }

  /** */
  public function getIterator()
  {
    return new \ArrayIterator(self::$current);
  }
}
