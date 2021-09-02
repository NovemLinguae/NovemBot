<?php

// Making this its own file so that this can be loaded by both index.php, and by PHPUnit when performing unit tests.

require_once(__DIR__ . '\config.php');
require_once(__DIR__ . '\botclasses.php');
require_once(__DIR__ . '\Helper.php');
require_once(__DIR__ . '\EchoHelper.php');
require_once(__DIR__ . '\GiveUpOnThisTopic.php');
require_once(__DIR__ . '\Promote.php');
require_once(__DIR__ . '\WikiAPIWrapper.php');