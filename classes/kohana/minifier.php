<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Minifier
{
  private static $_data = array(
    'css' => array(),
    'js' => array(),
  );

  public static function add_css($css, $section = '*')
  {
    self::_add_data('css', $css, $section);
  }

  public static function add_js($js, $section = '*')
  {
    self::_add_data('js', $js, $section);
  }

  private static function _add_data($type, $data, $section = '*')
  {
    if ( ! array_key_exists($type, self::$_data))
    {
      return FALSE;
    }

    if ( ! is_array($data))
    {
      $data = array($data);
    }

    // Ensure the section exists
    if ( ! array_key_exists($section, self::$_data[$type]))
    {
      self::$_data[$type][$section] = array();
    }

    foreach ($data as $datum)
    {
      self::$_data[$type][$section][$datum] = $datum;
    }

    return TRUE;
  }

  public static function get_css($section = '*')
  {
    $url = self::_get_url('css', $section);
    return empty($url) ? '' : HTML::style($url);
  }

  public static function get_js($section = '*')
  {
    $url = self::_get_url('js', $section);
    return empty($url) ? '' : HTML::script($url);
  }

  private static function _get_url($type, $section = '*')
  {
    if ( ! empty(self::$_data[$type][$section]))
    {
      return $type.'/'.implode(',', self::$_data[$type][$section]);
    }
  }

  private static function get_cache_filename($type, $files)
  {
    $config = Kohana::$config->load('minifier');
    return $config->get($type.'_cache_path').DIRECTORY_SEPARATOR.md5(serialize($files)).'.cache';
  }

  public static function get_cache($type, $files)
  {
    // First just check if the cached file exists
    $cache_filename = self::get_cache_filename($type, $files);
    if ( ! is_readable($cache_filename))
    {
      return FALSE;
    }

    // The $files array contains files that exist and are readable so we can just check filemtime easily
    $cache_mtime = filemtime($cache_filename);
    foreach ($files as $file)
    {
      if (filemtime($file) > $cache_mtime)
      {
        return FALSE;
      }
    }

    // If we get to here, then the cache exists and nothing in it is newer
    return file_get_contents($cache_filename);
  }

  public static function set_cache($type, $files, $data)
  {
    $cache_filename = self::get_cache_filename($type, $files);

    // Attempt to make sure the cache dir exists
    $cache_dir = dirname($cache_filename);
    if ( ! is_dir($cache_dir))
    {
      mkdir($cache_dir);
    }

    // Should be writable now...
    return is_int(file_put_contents($cache_filename, $data));
  }
}
