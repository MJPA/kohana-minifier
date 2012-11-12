<?php defined('SYSPATH') or die('No direct script access.');

return array(
  'css_base_path' => $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'css',
  'css_cache_path' => APPPATH.'cache/minifier.css',
  'css_cache_mode' => (Kohana::$environment == Kohana::PRODUCTION) ? Minifier::CACHE_PERM : Minifier::CACHE_AUTO,

  'js_base_path' => $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'js',
  'js_cache_path' => APPPATH.'cache/minifier.js',
  'js_cache_mode' => (Kohana::$environment == Kohana::PRODUCTION) ? Minifier::CACHE_PERM : Minifier::CACHE_AUTO,
);
