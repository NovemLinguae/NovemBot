<?php

class View {
	/**
	* flushing (ob_flush, flush) doesn't appear to work on Toolforge web due to gzip compression.
	* Works in CLI though. Deleting all the flushing code for now and just using echo.
	*/
	static function echoAndFlush($str) {
		echo $str;
	}

	static function setHeaders() {
		header('Content-Type:text/plain; charset=utf-8; Content-Encoding: none'); // Content-Encoding: none disables gzip compression, which may help fix an issue with flushing
	}

	static function setErrorReporting() {
		ini_set("display_errors", 1);
		error_reporting(E_ALL);
	}

	static function dieIfInvalidPassword($correctPassword) {
		// Keep randos from running the bot in browser and in bash
		// TODO: inject these globals
		if ( ($_GET['password'] ?? '') != $correctPassword && ($argv[1] ?? '') != $correctPassword ) {
			die('Invalid password.');
		}
	}
}
