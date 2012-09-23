<?php defined('SYSPATH') or die('No direct script access.');

Route::set('minifier', '<action>/<files>', array('action' => '(js|css)', 'files' => '.*'))->defaults(array('controller' => 'minifier'));
