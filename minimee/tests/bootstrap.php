<?php
// Craft's own bootstrap here
require_once '/Users/John/Sites/craftx.dev/craft/app/tests/bootstrap.php';

// the first time we run phpunit we always seem to get an initial error thrown that this does not exist
$_SERVER['SERVER_SOFTWARE'] = 'Apache';

// our tests use this
require_once __DIR__ . '/vendor/autoload.php';

// this usually happens in MinimeePlugin::init()
require_once __DIR__ . '/../library/vendor/autoload.php';