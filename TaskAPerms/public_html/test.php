<?php

/*
GET A .PHP FILE TO WRITE TO ANOTHER FILE
- .php file must be owned by novem-bot ("become novem-bot" "take FILENAME")
- .php file must be "chmod 744 FILENAME"
*/

ini_set("display_errors", 1);
error_reporting(E_ALL);

if ( ($_GET['password'] ?? '') == 1 || $argv[1] == 1 ) {
	echo "\ntest successful\n\n";
	$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
	$txt = "John Doe\n";
	fwrite($myfile, $txt);
	$txt = "Jane Doe\n";
	fwrite($myfile, $txt);
	fclose($myfile);
} else {
	echo "\ntest NOT successful\n\n";
}