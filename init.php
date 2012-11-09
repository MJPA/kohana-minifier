<?php defined('SYSPATH') or die('No direct script access.');

$type_regex = '(js|css)';

Route::set('minifier', '<action>/<file_hash>.<type>', array('action' => $type_regex, 'file_hash' => '[a-f0-9]{32}', 'type' => $type_regex))->defaults(array('controller' => 'minifier'));
