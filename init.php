<?php defined('SYSPATH') or die('No direct script access.');

Route::set('minifier', '<action>/<files>', array('action' => '(js|css|less)', 'files' => '.*'))->defaults(array('controller' => 'Minifier'));
