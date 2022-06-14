<?php

echo "\nLogging in...\n";
$objwiki = new wikipedia();
$objwiki->http->useragent = '[[en:User:NovemBot]], owner [[en:User:Novem Linguae]], framework [[en:User:RMCD_bot/botclasses.php]]';
$objwiki->login($username, $password);
echo "...done.\n";

// Will create a page if it's blank, too.
// Page will not record the edit if there is no change in content.
$objwiki->edit(
	'User:NovemBot/sandbox',
	'Test',
	'Bot test'
);