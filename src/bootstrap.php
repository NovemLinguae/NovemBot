<?php

// Making this its own file so that this can be loaded by both index.php, and by PHPUnit when performing unit tests.

require_once(__DIR__ . '\config.php');
require_once(__DIR__ . '\botclasses.php');
require_once(__DIR__ . '\string.php');
require_once(__DIR__ . '\echo.php');
require_once(__DIR__ . '\GiveUpOnThisTopic.php');
require_once(__DIR__ . '\promote.php');
require_once(__DIR__ . '\WikiAPIWrapper.php');