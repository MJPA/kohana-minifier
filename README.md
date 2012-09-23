#Kohana Minifier

## About
Kohana Minifier is a simple Kohana module that provides a simple interface for adding CSS/JS to a page. It also provides 2 new routes, `/css/file.css,file2.css` and `/js/script.js,script2.js` which will concatenate all of the files and then minify them.

## CSS Usage
Simply call `Minifier::add_css('bootstrap.css');` in your controller and `bootstrap.css` will be added to the CSS stack. Calling `Minifier::add_css()` with the same filename will have no effect, the CSS files will be outputted in the order they were first added.
To get the CSS actually on the page, simply add `<?php echo Minifier::get_css(); ?>` to the template body and the <link> HTML tag will be inserted for you.

## JS Usage
Adding Javascript to a page is the same process as for CSS but using js instead of css, so for example `Minifier::add_js('jquery-1.8.2.js');` will add jQuery to the JS stack.

## Configuration
There are 2 configuration options, `css_base_path` and `js_base_path` which provide the file system path that all JS/CSS files are relative to. These config options are used to make sure someone doesn't request files outside of the folder where your CSS/JS are located.

## SASS / SCSS support
SASS / SCSS support is provided by [PHPSass](https://github.com/MJPA/phpsass) automatically for files ending in either `.scss` or `.sass`.

## TODO
Cache support - currently none of the output is cached which needs fixing before use in production environments.
Allow configuration of cssmin/jsmin/phpsass.
Allow adding a string of CSS / JS rather than just files.
Allow external files to be included.
