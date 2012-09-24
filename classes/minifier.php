<?php defined('SYSPATH') or die('No direct script access.');

class Minifier {
  private static $_data = array(
    'css' => array(),
    'js' => array(),
  );

  public static function add_css($css)
  {
    self::_add_data('css', $css);
  }

  public static function add_js($js)
  {
    self::_add_data('js', $js);
  }

  private static function _add_data($type, $data)
  {
    if ( ! array_key_exists($type, self::$_data))
      return FALSE;

    if ( ! is_array($data))
      $data = array($data);

    foreach ($data as $datum)
      self::$_data[$type][$datum] = $datum;

    return TRUE;
  }

  public static function get_css()
  {
    $url = self::_get_url('css');
    return empty($url) ? '' : HTML::style($url);
  }

  public static function get_js()
  {
    $url = self::_get_url('js');
    return empty($url) ? '' : HTML::script($url);
  }

  private static function _get_url($type)
  {
    if ( ! empty(self::$_data[$type]))
      return URL::site($type.'/'.implode(',', self::$_data[$type]), NULL, FALSE);
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
      return FALSE;

    // The $files array contains files that exist and are readable so we can just check filemtime easily
    $cache_mtime = filemtime($cache_filename);
    foreach ($files as $file)
    {
      if (filemtime($file) > $cache_mtime)
        return FALSE;
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
      mkdir($cache_dir);

    // Should be writable now...
    return is_int(file_put_contents($cache_filename, $data));
  }
}
