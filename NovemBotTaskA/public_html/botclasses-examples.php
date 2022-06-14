<?php
/** requestedmoves.php
 *
 *  (c) 2010 James Hare - http://en.wikipedia.org/wiki/User:Harej
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *   
 *  Developers (add your self here if you worked on the code):
 *    James Hare - [[User:Harej]] - Wrote everything
 *    WBM - [[User:Wbm1058]] - August 2012 updates, WikiProject notifications (June 2015), Subject-space notifications (August 2016)
 **/
set_time_limit(1440);    # 24 minutes
ini_set("display_errors", 1);
error_reporting(E_ALL ^ E_NOTICE);
include("botclasses.php");  // Botclasses.php was written by User:Chris_G and is available under the GNU General Public License
include("logininfo.php");

const bot_version = "7.43";
const ds = 86400;    #number of seconds in a day
const ditmax = 8;    #array of dates, then backlog
const maxmoves = 350;  #maximum number of allowed moves in a multiple move request
const botuser = "RMCD bot";
const p_hats = "ital.*|pp-.*|use dmy.*|use mdy.*|featured article|good article";
const hats = "short description|about|about2|about-distinguish|about-distinguish2|ambiguous link|for|for2|for timeline|dablink|distinguish|distinguish2|distinguish-otheruses|distinguish-otheruses2|" .
	"further|further2|hatnote|other|others|otherpeople|otherpeople1|otherpeople2|otherpeople3|other hurricanes|other people|other people2|other people3|other persons|otherpersons|" .
	"otherpersons2|otherplaces|other places|otherplaces3|other places3|otherships|other ships|other uses|other uses2|otheruses|otheruses2|otheruses3|otheruses4|" .
	"other uses of|otheruse|outline|redirect-acronym|redirect-distinguish|redirect-distinguish2|redirect-multi|redirect-several|redirect|redirect2|redirect3|see also|this|disambig-acronym|selfref";
const dabs = "disambiguation|geodis|hndis|mil-unit-dis|numberdis";
const namespaces = "User|Wikipedia|File|MediaWiki|Template|Module|Help|Category|Portal|Book|Draft";
const reflists = "reflist-talk|reflist talk|talk-reflist|reftalk|talk reflist|talk ref|ref talk|reference talk|talk reference|talkref|tref|talk page reference|ref-talk|reflisttalk";

function talkpagename ($pagename) {
	if (preg_match("/^((" . namespaces . "):)/i",$pagename,$tpcp)) {
		$talkname = str_replace($tpcp[1],$tpcp[2].' talk:',$pagename);
	}
	else {
		$talkname = "Talk:" . $pagename;
	}
	return $talkname;
}
function wikititle ($targettitle) {
	$basename = preg_replace("/^(" . namespaces . "|)( |)(talk|):/i","",$targettitle);
	$ucbasename = ucfirst($basename);
	$targettitle = str_replace ($basename,$ucbasename,$targettitle);
	$targettitle = str_replace ("_"," ",$targettitle);
	$targettitle = trim($targettitle);
	$targettitle = ucfirst($targettitle);
	return $targettitle;
}

echo "PHP version: " . PHP_VERSION . "\n";
#phpinfo();
echo "Bot version: " . bot_version . "\n";

$d = array(date("F j, Y"), date("F j, Y", time()-ds), date("F j, Y", time()-ds*2), date("F j, Y", time()-ds*3),
 date("F j, Y", time()-ds*4), date("F j, Y", time()-ds*5), date("F j, Y", time()-ds*6), date("F j, Y", time()-ds*7));
print_r($d);
$current_time = time();
$twenty_ago = $current_time - 1200;
echo "Current time: ". $current_time . " (" . date("Y-m-d H:i:s", $current_time) . ")\n";
echo "20 mins. ago: ". $twenty_ago . " (" . date("Y-m-d H:i:s", $twenty_ago) . ")\n\n";

echo "Logging in...\n";
$objwiki = new wikipedia();
$objwiki->http->useragent = '[[User:RMCD bot]] php wikibot classes';
$objwiki->login($rmuser, $rmpass);
echo "...done.\n";

$transcludes = array();
$attempts = 0;
while (count($transcludes) == 0) {
		if ($attempts == 5) {
			die("Error 1");
		}
		else {
			echo "Checking for transclusions...\n";
			$transcludes = $objwiki->getTransclusions("Template:Requested move/dated");
			$attempts += 1;
		}
}
print_r($transcludes);

# First pass
$names = 0;
$conflicts = 0;
$conflict = array();

for ($i = 0; $i < count($transcludes); $i++) {
	$subjectpagename[$i] = preg_replace("/^(" . namespaces . "|)( |)talk:/i","$1:",$transcludes[$i]);
	echo "\n" . $i . " Retrieving $transcludes[$i] (" . $subjectpagename[$i] . ") contents...\n";
	$breakcounter = 0;
	while ($contents[$i] == "") {
		if ($breakcounter == 5) {
			die("Error 2");
		}
		else {
			$contents[$i] = $objwiki->getpage($transcludes[$i]);
			$breakcounter += 1;
		}
	}

	# Parse parameters
	$regexpart1 = "/\{{2}\s?(Requested move\/dated|movereq)\s?[^}]*";
	$regexpart2 = "\}{2}/iu";

	$breakcounter = 0;
	while ($parameters[0] == "") {
		preg_match($regexpart1 . $regexpart2, $contents[$i], $parameters);
		$regexpart1 .= "\n";
		$breakcounter += 1;
		if ($breakcounter == 5) {
			echo "Breaking from regex loop!!\n";
			break;
		}
	}

	#echo "parameters ";
	#print_r($parameters);

	$meta = preg_replace("/\n+/", "", $parameters[0]);
	$meta = preg_replace("/ ?\| ?/", "|", $meta);
	$meta = preg_replace("/\{{2}\s?/", "", $meta);
	$meta = preg_replace("/\s?}{2}/", "", $meta);
	$components = explode("|", $meta);
	#echo "components ";
	#print_r($components);

	for ($multi = 1; $multi < count($components); $multi++) {
		#echo "multi " . $multi . "-->" . $components[$multi] . "\n";
		if (preg_match("/^current\d+\s?=\s?/i", $components[$multi], $check)) {
			preg_match("/\d+/", $check[0], $number);
			$number = $number[0] - 1;
			$currentname[$transcludes[$i]][$number] = preg_replace("/^current\d+\s?=\s?/i", "", $components[$multi]);
			echo "Current name> " . $number . ": " . $currentname[$transcludes[$i]][$number] . "\n";
			continue;
		}
		elseif (preg_match("/^new\d+\s?=\s?/i", $components[$multi], $check)) {
			preg_match("/\d+/", $check[0], $number);
			$number = $number[0] - 1;
			$newname[$transcludes[$i]][$number] = preg_replace("/\s?new\d+\s?=\s?/i", "", $components[$multi]);
			echo "New name> " . $number . ": " . $newname[$transcludes[$i]][$number] . "\n";
			if ($newname[$transcludes[$i]][$number] == "" && $currentname[$transcludes[$i]][$number] != "") {
				$newname[$transcludes[$i]][$number] = "?";
				echo "\nSetting NULL newname to ?";
			}
			continue;
		}
	}
	if ($newname[$transcludes[$i]][0] == "") {
		$currentname[$transcludes[$i]][0] = preg_replace("/(\s|_)?talk:/i", ":", $transcludes[$i]);
		$newname[$transcludes[$i]][0] = str_replace("1=", "", $components[1]);
	}
	for ($nom = 0; $nom < count($currentname[$transcludes[$i]]); $nom++) {
		$pagename = $currentname[$transcludes[$i]][$nom];
		for ($ni = 1; $ni < $names; $ni++) {
			if ($currentnames[$ni] == $pagename or $currentnames[$ni] == ":" . $pagename) {
				if ($pagename != "") {
					echo "\n?! Conflicting discussion found! $pagename\n";
					$malformed .= "\n* [[" . $transcludes[$i] . "]] – Conflicting discussion found! [[" . $pagename . "]]\n";
					$conflicts += 1;
					$conflict[$conflicts] = $pagename;
					goto nextname;
				}
			}
		}

		$names += 1;
		$currentnames[$names] = $pagename;
		nextname:
	}
	unset($parameters);
	unset($meta);
	unset($components);
}

echo "\n\nPages requested to be moved:\n";
print_r($currentnames);
echo "\nConflicts: $conflicts\n";
if ($conflicts > 0) print_r($conflict);

# Second pass
$lua = 0;
$relisted = 0;

for ($i = 0; $i < count($transcludes); $i++) {
	if ($subjectpagename[$i] == $transcludes[$i]) {
		echo "\n__________\n" . $i . " Malformed request " . $transcludes[$i] . ", must be placed on a talk page\n";
		$malformed .= "\n* [[" . $transcludes[$i] . "]]\n";
		continue;
	}

	echo "\n__________\n" . $i . " Processing $transcludes[$i] (" . $subjectpagename[$i] . ") contents...\n";

	#echo "contents:\n";
	#echo "$contents[$i]";
	#echo "\n";

	# Description and Timestamp
	$scontents = preg_replace('/\x{200e}/u', '', $contents[$i]); // strip left-to-right marks
	$ltrcontents = preg_replace('/\x{200e}/u', '&lrm;', $contents[$i]);
	if ($scontents != $contents[$i]) echo "\n!! Left-to-right mark stripped!\n$ltrcontents\n\n";
	$regex1 = "/\{{2}\s?(Requested move\/dated|movereq)\s?[^}]*\}{2}";
	$regex2 = "([0-2]\d):([0-5]\d),\s(\d{1,2})\s(\w*)\s(\d{4})\s\([A-Z]{3}\).*/i";

	for ($lim = 0; $lim < maxmoves; $lim++) {
		$regex1 .= "\n*.*";
		preg_match($regex1 . $regex2, $scontents, $m);
		#echo "m ";
		#print_r($m);
		$description[$transcludes[$i]] = preg_replace("/\{{2}\s?(Requested move\/dated|movereq)\s?[^}]*\}{2}\n*/i", "", $m[0]);
		$description[$transcludes[$i]] = preg_replace("/\n/", "  ", $description[$transcludes[$i]]);    // replace newlines with two spaces
		$description[$transcludes[$i]] = preg_replace("/<p>/", "  ", $description[$transcludes[$i]]);   // replace html <p> (paragraph) tags with spaces
		$description[$transcludes[$i]] = preg_replace("/<\/p>/", "  ", $description[$transcludes[$i]]);
		$description[$transcludes[$i]] = preg_replace("/<ol>/", "  ", $description[$transcludes[$i]]);  // replace html <ol> (ordered list) tags with spaces
		$description[$transcludes[$i]] = preg_replace("/<\/ol>/", "  ", $description[$transcludes[$i]]);
		$description[$transcludes[$i]] = preg_replace("/<ul>/", "  ", $description[$transcludes[$i]]);  // replace html <ul> (unordered list) tags with spaces
		$description[$transcludes[$i]] = preg_replace("/<\/ul>/", "  ", $description[$transcludes[$i]]);
		$description[$transcludes[$i]] = preg_replace("/<li>/", "  ", $description[$transcludes[$i]]);  // replace html <li> (list) tags with spaces
		$description[$transcludes[$i]] = preg_replace("/<\/li>/", "  ", $description[$transcludes[$i]]);
		# newlines before and after {{reflist-talk}} and {{Search for}}
		$description[$transcludes[$i]] = preg_replace("/\{{2}\s?(" . reflists . "|Search for)\s?[^}]*\}{2}/iu", "\n$0\n:", $description[$transcludes[$i]]);

		#echo "$lim Description->" . $description[$transcludes[$i]] . "\n";
		if ($lim == 0) {
			$requests = 1;
		}
		else {
			$requests = $lim;
		}
		$description[$transcludes[$i]] = preg_replace("/\s*(\*\s)?\[{2}.*?\]{2}\s*?→\s*?(\{{2}|\[{2}|).*?(\}{2}|\]{2}|\?)\s*?/", "", $description[$transcludes[$i]], $requests);

		# Timestamp strings range in length from 24 bytes (May) to 30 bytes (September), so there may be up to 23 bytes following the timestamp (24+23=47)
		preg_match("/([0-2]\d):([0-5]\d),\s(\d{1,2})\s(\w*)\s(\d{4})\s\([A-Z]{3}\)/i", $description[$transcludes[$i]], $ts, 0, strlen($description[$transcludes[$i]])-47);
		#print_r($ts);
		$timestamp[$transcludes[$i]] = strtotime($ts[0]);

		if (preg_match("/(--|—)'''''Relist(ing|ed).'''''/i", $description[$transcludes[$i]]) === 1) {
			$relisted += 1;
			$dlink[$transcludes[$i]] = "Di<u>scu</u>ss";
			$olist = preg_replace("/\s*(<small>)?(--|—)'''''Relist(ing|ed).'''''.*/i", "", $description[$transcludes[$i]]);
			#echo "\nOrig. list: " . $olist . "\n";
			preg_match("/([0-2]\d):([0-5]\d),\s(\d{1,2})\s(\w*)\s(\d{4})\s\([A-Z]{3}\)/i", $olist, $ots, 0, strlen($olist)-47);
			#print_r($ots);
			$otimestamp[$transcludes[$i]] = strtotime($ots[0]);
		}
		else {
			$dlink[$transcludes[$i]] = "Discuss";
			$ots = $ts;
			$otimestamp[$transcludes[$i]] = $timestamp[$transcludes[$i]];
		}

		if ($description[$transcludes[$i]] != "") {
			break;
		}
	}

	preg_match("/\[\[:(.*)\]\]\s→\s(\?|\{\{no redirect)/", $m[0], $outside);
	if ($outside[1] != $currentname[$transcludes[$i]][0] and ":" . $outside[1] != $currentname[$transcludes[$i]][0]) {
		echo "\nName outside template: [[" . $outside[1] . "]] does not match name in template: [[" . $currentname[$transcludes[$i]][0] . "]]\n";
		$malformed .= "\n* [[" . $transcludes[$i] . "]] – Pagename to be moved listed below template: [[" . $outside[1] . "]] does not match name in template: [[" .
		    $currentname[$transcludes[$i]][0] . "]]. – Page may have been moved to the requested title.\n";
		goto next_rm;
	}

	$rationale = preg_replace("/^((.*?)*\s?(&mdash;|—|&ndash;|–)\s)/", "", $description[$transcludes[$i]]);

	if ($rationale == "") {
		echo "No dash found\n";
	}
	else {
		$description[$transcludes[$i]] = $rationale;
	}

	if ($twenty_ago > $otimestamp[$transcludes[$i]]) {
		$delay_passed = true;
	}
	else {
		$delay_passed = false;
	}
	echo "Description: " . $description[$transcludes[$i]] . "\n";
	echo "Timestamp: " . $timestamp[$transcludes[$i]] . " - " . $ts[0] . "; Original timestamp: " . $otimestamp[$transcludes[$i]] . " - " . $ots[0] .
	     " Delay passed?: " . json_encode($delay_passed) . "\n";
	if ($otimestamp[$transcludes[$i]] == "" ) {
		echo "\nOriginal timestamp could not be ascertained\n";
		$malformed .= "\n* [[" . $transcludes[$i] . "]] – Original timestamp could not be ascertained; check relisting syntax\n";
	}

	# Section
	if (!preg_match("/=+\s?.*\s?=+(?=\n+.*\{{2}(Requested move\/dated|movereq)+[^}]*\}{2}+)/iu", $contents[$i], $m)) {
		if (preg_match("/\{{2}\s?(Requested move\/dated|movereq)\s?/iu", $contents[$i])) {
			echo "Malformed request, contents:\n";
			echo "$contents[$i]";
			echo "\n";
			$malformed .= "\n* [[" . $transcludes[$i] . "]]\n";
		}
		else {
			echo "A match was not found, contents:\n";
			echo "$contents[$i]";
			echo "\n";
		}
		unset($contents[$i]);
		continue;
	}

	$section[$transcludes[$i]] = preg_replace("/=+\s*/", "", $m[0]);
	$section[$transcludes[$i]] = preg_replace("/\s*=+\n*/", "", $section[$transcludes[$i]]);
	#echo "Section: " . $section[$transcludes[$i]] . "\n";
	# remove links from section titles
	$section[$transcludes[$i]] = preg_replace("/\[\[/", "", $section[$transcludes[$i]]);
	$section[$transcludes[$i]] = preg_replace("/\]\]/", "", $section[$transcludes[$i]]);
	echo "Section> " . $section[$transcludes[$i]] . "\n";
	if ($section[$transcludes[$i]] == "") {
		echo "It's NULL!!\n";
	}

	# Newtitle(s) and notifications
	$nom1 = -1;
	$count = count($currentname[$transcludes[$i]]);

	for ($nom = 0; $nom < $count; $nom++) {
		skipblank: $nom1 += 1;
		$pagename = $currentname[$transcludes[$i]][$nom1];
		echo "Current name: " . $nom1 . ": " . $pagename . "\n";

		if ($pagename == "") {
			if ($nom1 < $count) {
				echo "*** Name is blank; skip to next *** $nom : $count\n";
				goto skipblank;
			}
			else continue;
		}

		$konflikt = false;
		if ($conflicts > 0) {
			for ($z = 1; $z <= $conflicts; $z++) {
				if ($conflict[$z] == $pagename) {
					echo "\n!! $pagename has conflicting discussions!\n\n";
					$malformed .= "\n* [[" . $transcludes[$i] . "]] – [[" . $pagename . "]] has a conflicting request for move on another page\n";
					$konflikt = true;
				}
			}
		}

		$talkname = talkpagename($pagename);

		$break = 0;
		$pagecontents = "";
		while ($pagecontents == "") {
			if ($break == 5) {
				echo "PAGE IS BLANK OR DOES NOT EXIST\n";
				$malformed .= "\n* [[" . $transcludes[$i] . "]] – [[" . $pagename . "]] is blank or does not exist\n";
				unset($pagecontents);
				goto aaa;
			}
			else {
				$pagecontents = $objwiki->getpage($pagename);
				$break += 1;
			}
		}

		$good = true;
		if (preg_match("/^\#REDIRECT(\s*|:)\[{2}.*\]{2}/i", $pagecontents, $redirect)) {
			echo "\n*** PAGE " . $pagename . " IS A REDIRECT!! ***\n";
			echo $pagecontents . "\n\n";
			$good = false;
			preg_match("/(?<=\[{2}).+(?=(\]{2}))/i", $redirect[0], $target);
			echo "Target: " . $target[0] . "\n";
			$target[0] = wikititle($target[0]);

			if ($target[0] == $pagename) { // self-redirect, possibly from a page-mover swap
				echo $pagename . " self-redirects to: " . $target[0] . "\n\n";
				$malformed .= "\n* [[" . $transcludes[$i] . "]] – [[" . $pagename . "]] self-redirects. May be in process of moving or closing.\n";
				goto next_rm;
			}
			else if ($target[0] == $newname[$transcludes[$i]][$nom1]) {
				echo $pagename . " redirects to requested name: " . $target[0] . "\n\n";
				$malformed .= "\n* [[" . $transcludes[$i] . "]] – [[" . $pagename . "]] redirects to requested name: [[" . $target[0] . "]]. – May be in process of closing.\n";
				goto next_rm;
			}
			else {
				$malformed .= "\n* [[" . $transcludes[$i] . "]] – [[" . $pagename . "]] redirects to [[" . $target[0] . "]]\n";
			}
		}
		elseif (strpos($pagename, "Module:") === 0) {
			echo "\nNotices are not placed on Lua modules\n";
			$lua += 1;
		}
		elseif (substr($pagename, -4) == ".css") {
			echo "\nNotices are not placed on CSS pages\n";
			$lua += 1;
		}
		else {
			# Check for errors. In testing this I got a PREG_JIT_STACKLIMIT_ERROR in some cases, which was solved by making the regex "unroll the loop"
			$retcode = preg_match("/\{{2}(?:" . hats . ")[^{]*(?:\{[^{]|\{\{[^{}]+\}\}[^{]*)*\}{2}/iu", $pagecontents, $m, PREG_OFFSET_CAPTURE);
			if ($retcode === 1) {
				#echo "-Hatnote found: " . $m[0][0] . " offset:" . $m[0][1] . "\n";
			}
			else if ($retcode === false) {
				echo "-ERROR: ";
				echo array_flip(get_defined_constants(true)['pcre'])[preg_last_error()] . "\n";
			}

			$pagecontents2 = $pagecontents;
			$hatnum = 0;

			# Match template {{...}} possibly with templates inside it, but no templates inside those: [[Wikipedia:AutoWikiBrowser/Regular expression#Token matching]]
			while (preg_match("/\{{2}(" . hats . ")[^{]*(?:\{[^{]|\{\{[^{}]+\}\}[^{]*)*\}{2}/iu", $pagecontents2, $m, PREG_OFFSET_CAPTURE) === 1) {
				$hatnum += 1;
				echo ":Hatnote " . $hatnum . " found: " . $m[0][0] . " offset:" . $m[0][1] . "\n";
				$pagecontents2 = preg_replace("/(\{{2}(" . hats . ")[^{]*(?:\{[^{]|\{\{[^{}]+\}\}[^{]*)*\}{2}\n?)/iu","",$pagecontents2,1);
			}

			$subjectnotice = "{{User:RMCD bot/subject notice|1=" . $newname[$transcludes[$i]][$nom1] . "|2=" . $transcludes[$i] . "#" . $section[$transcludes[$i]] . "}}";
			$check = strpos($pagecontents, "{{User:RMCD bot/subject notice");

			if ($check !== false && $konflikt == false) { // check for tampering
				preg_match("/\{{2}User:RMCD bot\/subject notice.*\}{2}/i", $pagecontents, $multitalk);
				if ($multitalk[0] != $subjectnotice) {
					echo "Subject page template: " . $multitalk[0] . "\n";
					echo "Expected: " . $subjectnotice . "\n";
					$pagecontents = str_replace($multitalk[0], $subjectnotice, $pagecontents);
					#echo $pagecontents;
					echo "\nSync tampered subject page notice on " . $pagename . "\n";
					if ($objwiki->nobots($pagename,botuser,$pagecontents) == true) {
						$objwiki->edit($pagename,$pagecontents,"Sync tampered notice of move discussion on [[" . $transcludes[$i] . "#" . $section[$transcludes[$i]] .
						   "|" . $transcludes[$i] . "]]",false,true);
					}
				}
			}
			if ($check === false && $objwiki->nobots($pagename,botuser,$pagecontents) == true && $delay_passed == true && $konflikt == false) {
				$pagecontents2 = $pagecontents;
				$hatnum = 0;
				$hatnote = array_fill(1, 10, "");

				# Match template {{...}} possibly with templates inside it, but no templates inside those: [[Wikipedia:AutoWikiBrowser/Regular expression#Token matching]]
				while (preg_match("/\n*(\{\{(" . p_hats . ")\}\})*\n*\{{2}(" . hats .
				    ")[^{]*(?:\{[^{]|\{\{[^{}]+\}\}[^{]*)*\}{2}/iu", $pagecontents2, $m, PREG_OFFSET_CAPTURE) === 1) {
					if ($m[0][1] == 0) {
						$hatnum += 1;
						$hatnote[$hatnum] = $m[0][0];
						echo ":Hatnote " . $hatnum . " found: " . $hatnote[$hatnum] . "\n";
						$pagecontents2 = preg_replace("/\n*(\{\{(" . p_hats . ")\}\})*\n*(\{{2}(" . hats .
							")[^{]*(?:\{[^{]|\{\{[^{}]+\}\}[^{]*)*\}{2}\n?)/iu","",$pagecontents2,1);
					}
					else {
						break;
					}
				}

				$pagecontents = "";
				echo "\nHatnum = " . $hatnum . "\n";
				print_r($hatnote);

				for ($ii = 1; $ii <= $hatnum; $ii++) {
					$pagecontents .= $hatnote[$ii] . "\n";
				}
				echo $pagecontents;
				$pagecontents .= "<noinclude>" . $subjectnotice . "\n</noinclude>" . $pagecontents2;

				#echo $pagecontents;
				echo "\nNotify subject page " . $pagename . "\n";
				$objwiki->edit($pagename,$pagecontents,"Notifying subject page of move discussion on [[" . $transcludes[$i] . "#" . $section[$transcludes[$i]] .
				   "|" . $transcludes[$i] . "]]",false,true);
			}
		}

		if ($nom1 != 0 && $good === true) {
			$break = 0;
			$talkpage = "";
			while ($talkpage == "") {
				if ($break == 5) {
					break;
				}
				else {
					$talkpage = $objwiki->getpage($talkname);
					$break += 1;
				}
			}
			if (preg_match("/^\#REDIRECT(\s*|:)\[{2}.*\]{2}/i", $talkpage, $redirect)) {
				echo "\n" . $talkname . " REDIRECTS. May be a shared talk page.\n";
			}
			else {
				#$check = strpos($talkpage, "<!-- " . $transcludes[$i] . " crosspost -->");
				$check = strpos($talkpage, "{{User:RMCD bot/multimove");
				if ($check === false && $talkname != $transcludes[$i] && $objwiki->nobots($talkname,botuser,$talkpage) == true && $delay_passed == true && $konflikt == false) {
					echo "\nNotify page " . $talkname . "\n";

					if (preg_match("/\=\= Move discussion in progress \=\=\n\nThere is a move discussion in progress on \[\[(.*)\#(.*)\|(.*)\]\] which/",$talkpage,$crossnote)) {
						echo "\nMatched notice!\n";
						print_r($crossnote);
						echo "**" . $transcludes[$i] . "**\n";
						echo "**" . $section[$transcludes[$i]] . "**\n";
						if ($crossnote[1] == $transcludes[$i] and $crossnote[2] == $section[$transcludes[$i]]) {
							echo "\nAdding template to existing notice\n";
							$talkpage = preg_replace("/\=\= Move discussion in progress \=\=\n\nThere is a move discussion in progress on \[\[(.*)\#(.*)\|(.*)\]\] which/",
								"== Move discussion in progress ==\n\n{{User:RMCD bot/multimove|1=" . $newname[$transcludes[$i]][$nom1] . "|2=" .
								$transcludes[$i] . "#" . $section[$transcludes[$i]] . "}}\n" .
								"There is a move discussion in progress on [[$1#$2|$3]] which", $talkpage);
							#echo "\n" . $talkpage . "\n\n";
							goto crossnoted;
						}
					}
					if ($talkpage != "") {
						$talkpage .= "\n\n";
					}
					else if (preg_match("/\{{2}.*(" . dabs . ")\}{2}/iu", $pagecontents)) {
						$talkpage = "{{WikiProject Disambiguation}}\n\n";
					}

					$talkpage .= "== Move discussion in progress ==\n\n{{User:RMCD bot/multimove|1=" . $newname[$transcludes[$i]][$nom1] . "|2=" .
					$transcludes[$i] . "#" . $section[$transcludes[$i]] . "}}\n" .
					 "There is a move discussion in progress on [[" . $transcludes[$i] . "#" . $section[$transcludes[$i]] .
					 "|" . $transcludes[$i] . "]] which affects this page. Please participate on that page and not in this talk page section. Thank you. <!-- " .
			 	 	 $transcludes[$i] . " crosspost --> —[[User:RMCD bot|RMCD bot]] ~~~~~";
					crossnoted:$objwiki->edit($talkname,$talkpage,"Notifying of multimove discussion on [[" . $transcludes[$i] . "#" . $section[$transcludes[$i]] .
					 "|" . $transcludes[$i] . "]]",false,true);
				}
			}
			unset($talkpage);
		}
		unset($pagecontents);
	}
	aaa:

	if ($count != count($newname[$transcludes[$i]])) {
		echo "\n?? Counts aren't equal -- Current: " . $count . "  New: " . count($newname[$transcludes[$i]]) . "\n";
		$malformed .= "\n* [[" . $transcludes[$i] . "]] – Counts aren't equal -- Current: " . $count . "  New: " . count($newname[$transcludes[$i]]) . "\n";
	}

	$nom1 = -1;

	for ($nom = 0; $nom < $count; $nom++) {
		$nom1 += 1;
		while ($currentname[$transcludes[$i]][$nom1] == "" && $nom1 < $count) {
			$nom1 += 1;
		}

		$konflikt = false;
		if ($conflicts > 0) {
			for ($z = 1; $z <= $conflicts; $z++) {
				if ($conflict[$z] == $currentname[$transcludes[$i]][$nom1]) {
					echo "\n!!" . $currentname[$transcludes[$i]][$nom1] . " has conflicting discussions! (second loop)\n\n";
					#$malformed .= "\n* [[" . $transcludes[$i] . "]] – [[" . $currentname[$transcludes[$i]][$nom1] . "]] has a conflicting request for move on another page\n";
					$konflikt = true;
				}
			}
		}

		$talkname = talkpagename($newname[$transcludes[$i]][$nom1]);

		if ($newname[$transcludes[$i]][$nom1] == "?") {
			echo "New name: " . $nom1 . ": " . $newname[$transcludes[$i]][$nom1] . " (name to be decided)\n";
		}
		else if ($talkname == $transcludes[$i]) {
			echo "New name: " . $nom1 . ": " . $newname[$transcludes[$i]][$nom1] . " (moving over the current page)\n";
		}
		else {
			aa:$break = 0;
			$talkpage = "";
			while ($talkpage == "") {
				if ($break == 5) {
					break;
				}
				else {
					$talkpage = $objwiki->getpage($talkname);
					$break += 1;
				}
			}
			if ($talkpage == "") {
				echo "New name: " . $nom1 . ": " . $newname[$transcludes[$i]][$nom1] . " - NO TALKPAGE\n";
				$contentpage = $objwiki->getpage($newname[$transcludes[$i]][$nom1]);
				if (preg_match("/^\#REDIRECT(\s*|:)\[{2}.*\]{2}/i", $contentpage, $redirect)) {
					preg_match("/(?<=\[{2}).+(?=(\]{2}))/i", $redirect[0], $target);
					$target[0] = wikititle($target[0]);
					echo $newname[$transcludes[$i]][$nom1] . " redirects to: " . $target[0];
					if ($subjectpagename[$i] != $target[0] and $subjectpagename[$i] != ":" . $target[0]) {
						$targettalkname = talkpagename($target[0]);
						if (strpos($targettalkname,"#")) {
							$len = strpos($targettalkname,"#");
							$targettalkname = substr($targettalkname,0,$len); // strip section links
						}
						if ($targettalkname != $transcludes[$i]) {
							echo " -- check $targettalkname\n";
							$break = 0;
							$targettalkpage = "";
							while ($targettalkpage == "") {
								if ($break == 5) {
									break;
								}
								else {
									$targettalkpage = $objwiki->getpage($targettalkname);
									$break += 1;
								}
							}

							if (preg_match("/^\#REDIRECT(\s*|:)\[{2}.*\]{2}/i", $targettalkpage, $redirect)) {
								echo " -- it's a REDIRECT!\n";
							}
							else {
								$check = strpos($targettalkpage, "<!-- " . $transcludes[$i] . " crosspost -->");
								if ($check === false && $objwiki->nobots($targettalkname,botuser,$targettalkpage) == true && $delay_passed == true && $konflikt == false) {
								        echo " -- notify talk page\n";
								        if ($targettalkpage != "") {
								            $targettalkpage .= "\n\n";
								        }
								        else {
								            $targetcontent = $objwiki->getpage($target[0]);
								            if (preg_match("/\{{2}.*(" . dabs . ")\}{2}/iu", $targetcontent)) $targettalkpage = "{{WikiProject Disambiguation}}\n\n";
								        }
								        $targettalkpage .= "== Move discussion in progress ==\n\nThere is a move discussion in progress on [[" . $transcludes[$i] . "#" .
								            $section[$transcludes[$i]] . "|" . $transcludes[$i] . "]] which affects this page. Please participate on that page and not " .
				 	 			            "in this talk page section. Thank you. <!-- " . $transcludes[$i] . " crosspost --> —[[User:RMCD bot|RMCD bot]] ~~~~~";
								        $objwiki->edit($targettalkname,$targettalkpage,"Notifying target talkpage of move discussion on [[" . $transcludes[$i] .
						 		            "#" . $section[$transcludes[$i]] . "|" . $transcludes[$i] . "]]",false,true);
								}
								else {
								        echo " -- already notified\n";
								}
							}
						}
						else {
							echo " -- hosting the discussion\n";
						}
					}
					else {
						echo " -- hosting discussion\n";
					}
				}
				elseif ($contentpage != "" && $delay_passed == true && $konflikt == false) {
					echo "\nTarget page " . $newname[$transcludes[$i]][$nom1] . " has non-redirecting content\n";
					$talkpage .= "== Move discussion in progress ==\n\nThere is a move discussion in progress on [[" . $transcludes[$i] . "#" . $section[$transcludes[$i]] .
					 "|" . $transcludes[$i] . "]] which affects this page. Please participate on that page and not in this talk page section. Thank you. <!-- " .
					 $transcludes[$i] . " crosspost --> —[[User:RMCD bot|RMCD bot]] ~~~~~";
					echo "*** Post crosspost notice to $talkname ***\n";
					$objwiki->edit($talkname,$talkpage,"Notifying talkpage of move discussion on [[" . $transcludes[$i] . "#" . $section[$transcludes[$i]] .
					 "|" . $transcludes[$i] . "]]",false,true);
					$not_redir[$transcludes[$i]][$nom1] = "yes";
				}
				elseif ($contentpage == "") {
					#echo "\nTarget page " . $newname[$transcludes[$i]][$nom1] . " is a red link or blank page\n";
					$not_redir[$transcludes[$i]][$nom1] = "yes";
				}
			}
			else if (preg_match("/^\#REDIRECT(\s*|:)\[{2}.*\]{2}/i", $talkpage, $redirect)) {
				preg_match("/(?<=\[{2}).+(?=(\]{2}))/i", $redirect[0], $target);
				$target[0] = wikititle($target[0]);

				if ($target[0] == str_ireplace("Talk:","",$target[0])) {
					echo "Talk page does not redirect to another talk page!!\n";
					$malformed .= "\n* [[" . $transcludes[$i] . "]] – [[" . $talkname . "]] Talk page does not redirect to another talk page!!\n";
				}
				else if ($target[0] == $transcludes[$i] or $target[0] == ":" . $transcludes[$i]) {
					echo "New name: " . $nom1 . ": " . $newname[$transcludes[$i]][$nom1] . " redirects :" . $redirect[0] . " → " . $target[0] . " (same)\n";
				}
				else {
					echo "New name: " . $nom1 . ": " . $newname[$transcludes[$i]][$nom1] . " redirects :" . $redirect[0] . " → " . $target[0] . " (different)\n";
					if ($talkname == $target[0]) {
						echo "Self-redirecting talk page!!\n";
						$malformed .= "\n* [[" . $transcludes[$i] . "]] – [[" . $talkname . "]] Self-redirecting talk page!!\n";
					}
					else {
						$talkname = $target[0];
						goto aa;
					}
				}
			}
			else {
				echo "New name: " . $nom1 . ": " . $newname[$transcludes[$i]][$nom1] . " (" . $talkname . " has non-redirecting content) ";

				$contentpage = $objwiki->getpage($newname[$transcludes[$i]][$nom1]);
				if (preg_match("/^\#REDIRECT(\s*|:)\[{2}.*\]{2}/i", $contentpage, $redirect)) {
					echo "Target-page " . $newname[$transcludes[$i]][$nom1] . " is a REDIRECT\n";
				}
				elseif ($contentpage != "") {
					echo "Target-page " . $newname[$transcludes[$i]][$nom1] . " has non-redirecting content\n";
					#print_r($currentname[$transcludes[$i]]);
					$found = false;
					for ($k = 0; $k < count($currentname); $k++) {
						if ($currentname[$transcludes[$i]][$k] == $newname[$transcludes[$i]][$nom1]) {
							echo $currentname[$transcludes[$i]][$k] . " requested for move to " . $newname[$transcludes[$i]][$k] . "\n";
							$found = true;
						}
					}
					if ($found == false) {
						echo $newname[$transcludes[$i]][$nom1] . " is not requested for move\n";
						$incomplete .= "\n* [[" . $transcludes[$i] . "]] – [[" . $currentname[$transcludes[$i]][$nom1] . "]] is requested for move to [[" .
						    $newname[$transcludes[$i]][$nom1] . "]], which has non-redirecting content and is not requested for move\n";
					}
					$not_redir[$transcludes[$i]][$nom1] = "yes";
				}
				else {
					#echo "\nTarget page " . $newname[$transcludes[$i]][$nom1] . " is a red link or blank page\n";
					$not_redir[$transcludes[$i]][$nom1] = "yes";
				}

				$check = strpos($talkpage, "<!-- " . $transcludes[$i] . " crosspost -->");
				if ($check === false && $objwiki->nobots($talkname,botuser,$talkpage) == true && $delay_passed == true && $konflikt == false) {
				    if ($talkpage != "") {
				        $talkpage .= "\n\n";
				    }
				    $talkpage .= "== Move discussion in progress ==\n\nThere is a move discussion in progress on [[" . $transcludes[$i] . "#" . $section[$transcludes[$i]] .
				     "|" . $transcludes[$i] . "]] which affects this page. Please participate on that page and not in this talk page section. Thank you. <!-- " .
			 	     $transcludes[$i] . " crosspost --> —[[User:RMCD bot|RMCD bot]] ~~~~~";
				    echo "*** Post cross-post notice to $talkname ***\n";
				    $objwiki->edit($talkname,$talkpage,"Notifying talk page of move discussion on [[" . $transcludes[$i] . "#" . $section[$transcludes[$i]] .
				     "|" . $transcludes[$i] . "]]",false,true);
				}
			}
			unset($talkpage);
		}
	}

	# Notify WikiProjects
	preg_match_all("/\{{2}WikiProject\s.*/i", $contents[$i], $projects);
	#print_r($projects[0]);
	for ($ii = 0; $ii < count($projects[0]); $ii++) {
		#echo "Projects> " . $projects[0][$ii] . "\n";

		if (stripos($projects[0][$ii], "banner") !== FALSE) {
			echo "\nBANNER> " . $projects[0][$ii] . "\n";
			goto b;
		}

		if (preg_match("/WikiProject\s.*?(?=(\||\}{2}))/i", $projects[0][$ii], $wikiproject)) {
			$wikiproject[0] = trim($wikiproject[0]);
			echo "\nProject> " . $wikiproject[0] . "\n";
		}
		else {
			preg_match("/WikiProject\s.*/i", $projects[0][$ii], $wikiproject);
			echo "\nPROJECT: " . $wikiproject[0] . "\n";
		}

		$projectname = "Wikipedia:" . $wikiproject[0];
		$templatename = "Template:" . $wikiproject[0];
		$break = 0;
		$projectpage = "";

		while ($projectpage == "") {
			if ($break == 5) {
				break;
			}
			elseif ($break == 1) {
				$projectpage = $objwiki->getpage($templatename);
				$break += 1;
			}
			else {
				$projectpage = $objwiki->getpage($projectname);
				$break += 1;
			}
		}

		if (preg_match("/^\#REDIRECT(\s*|:)\[{2}.*\]{2}/i", $projectpage, $redirect)) {
			if ($redirect[0] == "#REDIRECT [[Template:WikiProjectBannerShell]]") {
				echo "\nSkipping #REDIRECT [[Template:WikiProjectBannerShell]]\n";
				goto b;
			}
			if ($redirect[0] == "#REDIRECT [[Template:WikiProjectBanners]]") {
				echo "\nSkipping #REDIRECT [[Template:WikiProjectBanners]]\n";
				goto b;
			}
			if ($redirect[0] == "#REDIRECT [[Template:WPMILHIST VC migration]]") {
				echo "\nSkipping #REDIRECT [[Template:WPMILHIST VC migration]]\n";
				goto b;
			}
			echo "\nFollowing redirect :". $redirect[0] . "\n";
			preg_match("/(?<=\[{2}).+(?=(\]{2}))/i", $redirect[0], $redirect);
			#echo "\nFollowing redirect :". $redirect[0] . "\n";
			$alertname = $redirect[0] . "/Article alerts";
			$alertname = str_replace("Template:", "Wikipedia:", $alertname);
			$talkname = str_replace("Template:", "Wikipedia:", $redirect[0]);
			$talkname = str_replace("Wikipedia:", "Wikipedia talk:", $talkname);
		}
		else {
			$alertname = "Wikipedia:" . $wikiproject[0] . "/Article alerts";
			$talkname = "Wikipedia talk:" . $wikiproject[0];
		}

		$break = 0;
		$alertpage = "";

		while ($alertpage == "") {
			if ($break == 2) {
				break;
			}
			else {
				$alertpage = $objwiki->getpage($alertname);
				$break += 1;
			}
		}
		#print_r($alertpage);

		if ($alertpage != "") {
			echo $alertname . " exists. Skipping notification.";
		}
		else {
			a:$break = 0;
			$talkpage = "";

			while ($talkpage == "") {
				if ($break == 5) {
					echo "WIKIPROJECT PAGE IS BLANK OR DOES NOT EXIST\n";
					unset($talkpage);
					goto b;
				}
				else {
					$talkpage = $objwiki->getpage($talkname);
					$break += 1;
				}
			}
			#print_r($talkpage);
			if (preg_match("/^\#REDIRECT(\s*|:)\[{2}.*\]{2}/i", $talkpage, $redirect)) {
				echo "\nFollowing redirect:". $redirect[0] . "\n";
				preg_match("/(?<=\[{2}).+(?=(\]{2}))/i", $redirect[0], $redirect);
				#echo "\nFollowing redirect:". $redirect[0] . "\n";
				$talkname = $redirect[0];
				goto a;
			}

			$check = strpos($talkpage, "<!-- " . $transcludes[$i] . "#" . $section[$transcludes[$i]] . " crosspost -->");
			if ($check === false && $objwiki->nobots($talkname,botuser,$talkpage) == true && $talkname != $transcludes[$i] && $delay_passed == true && $konflikt == false) {
				echo "Notify " . $talkname . " of discussion on " . $transcludes[$i] . ", Current name: " . $currentname[$transcludes[$i]][0] .
				 ", New name: " . $newname[$transcludes[$i]][0] . "\n";

				$talkpage .= "\n\n== [[" . $currentname[$transcludes[$i]][0] . "]] listed at Requested moves==\n[[File:Information.svg|30px|left]]" .
				    "A [[Wikipedia:Requested moves|requested move]] discussion has been initiated for [[" . $currentname[$transcludes[$i]][0] . "]] to be moved";

				if ($newname[$transcludes[$i]][0] != "?") {
				     $talkpage .= " to [[" . $newname[$transcludes[$i]][0] . "]]";
				}

				$talkpage .= ". This page is of interest to this WikiProject and interested members may want to participate in the discussion [[" .
				    $transcludes[$i] . "#" . $section[$transcludes[$i]] . "|here]].<!-- " . $transcludes[$i] . "#" . $section[$transcludes[$i]] .
				    " crosspost --> —[[User:RMCD bot|RMCD bot]] ~~~~~" .
				    "\n:<small>To opt out of RM notifications on this page, transclude {{tlp|bots|2=deny=RMCD bot}}," .
				    " or set up [[Wikipedia:Article alerts|Article alerts]] for this WikiProject.</small>";

				#print_r($talkpage);
				$objwiki->edit($talkname,$talkpage,"Notifying WikiProject of move discussion on [[" . $transcludes[$i] . "#" . $section[$transcludes[$i]] .
				 "|" . $transcludes[$i] . "]]", false, true);
			}
			else {
				echo "Skipping WikiProject talk page\n";
			}
			unset($talkpage);
		}
		b:continue;
	}

	next_rm:unset($contents[$i]);
}

echo "\n__________\nLua modules and CSS pages: " . $lua;
echo "\n" . $relisted . " items have been relisted\n";

echo "\nSorting by timestamp... ";
$keys = array_keys($timestamp);
$values = array_values($timestamp);
array_multisort($values, SORT_DESC, $keys);
$timestamp = array_combine($keys, $values);
echo "done.\n";

echo "Sorting by original timestamp... ";
$keys = array_keys($otimestamp);
$values = array_values($otimestamp);
array_multisort($values, SORT_DESC, $keys);
$otimestamp = array_combine($keys, $values);
echo "done.\n";

echo "Adding entries to different lists...\n";
$hatted = false;
$weekago = time()-ds*7;
$eightdays = time()-ds*8;

foreach ($timestamp as $title => $time) {
	#echo "Description> " . $description[$title] . "\n";
	$description[$title] = preg_replace("/\*{1,2}\s?\[{2}[^\]]*\]{2}\s?→\s?\[{2}[^\]]*\]{2}/", "", $description[$title]);

	if ($newname[$title][0] == "?") {
		$theaddition = "* " . "''([[" . $title . "#" . $section[$title] . "|" . $dlink[$title] . "]])'' – '''[[" . $currentname[$title][0] . "]] → ?''' – " . $description[$title] . "\n";
		$oldaddition = "* " . "'''[[" . $currentname[$title][0] . "]] → ?''' – (''[[" . $title . "#" . $section[$title] . "|" . $dlink[$title] . "]]'') – " . $description[$title] . "\n";
		$summaddition = "*[[" . $currentname[$title][0] . "]] → ? – '''([[" . $title . "#" . $section[$title] . "|" . $dlink[$title] . "]])'''\n";
	}
	else if ($not_redir[$title][0] == "yes") {
		$theaddition = "* " . "''([[" . $title . "#" . $section[$title] . "|" . $dlink[$title] . "]])'' – '''[[" . $currentname[$title][0] . "]] → [[" .
		  $newname[$title][0] . "]]''' – " . $description[$title] . "\n";

		$oldaddition = "* " . "'''[[" . $currentname[$title][0] . "]] → [[" . $newname[$title][0] . "]]''' – (''[[" . $title . "#" . $section[$title] . "|" . $dlink[$title] .
		  "]]'') – " .$description[$title] . "\n";

		$summaddition = "*[[" . $currentname[$title][0] . "]] → [[" . $newname[$title][0] . "]] – '''([[" . $title . "#" . $section[$title] . "|" . $dlink[$title] . "]])'''\n";
	}
	else {
		$theaddition = "* " . "''([[" . $title . "#" . $section[$title] . "|" . $dlink[$title] . "]])'' – '''[[" . $currentname[$title][0] . "]] → {{no redirect|" .
		  $newname[$title][0] . "}}''' – " . $description[$title] . "\n";

		$oldaddition = "* " . "'''[[" . $currentname[$title][0] . "]] → {{no redirect|" . $newname[$title][0] . "}}''' – (''[[" . $title . "#" . $section[$title] . "|" . $dlink[$title] .
		  "]]'') – " .$description[$title] . "\n";

		$summaddition = "*[[" . $currentname[$title][0] . "]] → {{no redirect|" . $newname[$title][0] . "}} – '''([[" . $title . "#" . $section[$title] . "|" . $dlink[$title] . "]])'''\n";
	}

	$indent = 0;

	for ($inden = 1; $inden < count($currentname[$title]); $inden++) {
		skipblank2: $indent += 1;
		echo "\nindent: " . $inden . " | " . $indent . " > " . $newname[$title][$indent];

		if ($newname[$title][$indent] == "?") {
			$theaddition .= "** [[" . $currentname[$title][$indent] . "]]  → ?\n";
			$oldaddition .= "** [[" . $currentname[$title][$indent] . "]]  → ?\n";
		}
		else if ($newname[$title][$indent] != "") {
			if ($not_redir[$title][$indent] == "yes") {
				$theaddition .= "** [[" . $currentname[$title][$indent] . "]]  → [[" . $newname[$title][$indent] . "]]\n";
				$oldaddition .= "** [[" . $currentname[$title][$indent] . "]]  → [[" . $newname[$title][$indent] . "]]\n";
			}
			else {
				$theaddition .= "** [[" . $currentname[$title][$indent] . "]]  → {{no redirect|" . $newname[$title][$indent] . "}}\n";
				$oldaddition .= "** [[" . $currentname[$title][$indent] . "]]  → {{no redirect|" . $newname[$title][$indent] . "}}\n";
			}
		}
		else {
			if ($indent == count($currentname[$title])) break;
			goto skipblank2;
		}
	}

	$theaddition .= "\n";
	$oldaddition .= "*\n";

	#echo date("Y-m-d H:i:s", $time) . " (" . date("Y-m-d H:i:s", $weekago) . ")\n";

	for ($dit = 0; $dit < ditmax; $dit++) {
		if ($time > 0) {
			if (date("F j, Y", $time) == $d[$dit]) {
				
				if ($weekago > $time) {
				    #echo "Over a week ago\n";
				    if ($hatted == false) {
					$add[$dit] .= "===Elapsed listings===\n{{shortcut|WP:RME}}\n{{hatnote|The 7-day listing period has elapsed." .
					    " Items below may be closed if there's a consensus, or if discussion has run its course and consensus could not be achieved.}}\n";
					$oldadd[$dit] .= "===Elapsed listings===\n{{hatnote|The 7-day listing period has elapsed." .
					    " Items below may be closed if there's a consensus, or if discussion has run its course and consensus could not be achieved.}}\n";
					$summ[$dit] .= "{{end}}\n\n";
					$summ[$dit] .= "{{Dashboard grouping|c=#BDD8FF|'''[[Wikipedia:Requested moves#Elapsed listings|Elapsed listings]]'''}}\n";
					#echo "Adding hatnote\n";
					$hatted = true;
				    }
				}
				$add[$dit] .= $theaddition;
				$oldadd[$dit] .= $oldaddition;
				$summ[$dit] .= $summaddition;
			}
			else {
				continue;
			}
		}
	}
	
	if ($time < strtotime($d[ditmax-1]) && $time != "") {
		if ($time > $eightdays) {
			#echo "Previous day elapsed(" . date("Y-m-d H:i:s", $eightdays) . ")\n";
			if ($hatted == false) {
				$add[ditmax-1] .= "===Elapsed listings===\n{{shortcut|WP:RME}}\n{{hatnote|The 7-day listing period has elapsed." .
				    " Items below may be closed if there's a consensus, or if discussion has run its course and consensus could not be achieved.}}\n";
				$oldadd[ditmax-1] .= "===Elapsed listings===\n{{hatnote|The 7-day listing period has elapsed." .
				    " Items below may be closed if there's a consensus, or if discussion has run its course and consensus could not be achieved.}}\n";
				$summ[ditmax-1] .= "{{end}}\n\n";
				$summ[ditmax-1] .= "{{Dashboard grouping|c=#BDD8FF|'''[[Wikipedia:Requested moves#Elapsed listings|Elapsed listings]]'''}}\n";
				#echo "Adding hatnote\n";
				$hatted = true;
			}
			$add[ditmax-1] .= $theaddition;
			$oldadd[ditmax-1] .= $oldaddition;
			$summ[ditmax-1] .= $summaddition;
		}
		else {
			#echo "Backlog\n";
			$BLadd .= $theaddition;
			$BLold .= $oldaddition;
			$BLsumm .= $summaddition;
		}
	}
	elseif ($time == "") {
		$MALadd .= $theaddition;
		$MALold .= $oldaddition;
		$MALsumm .= $summaddition;
	}
}

$submission = "<noinclude>{{shortcut|WP:RMC|WP:RM/C|WP:RMCD}}</noinclude><includeonly>{{shortcut|WP:RM#C}}</includeonly>\n" .
    ":''This <noinclude>page</noinclude><includeonly>section</includeonly> lists all requests filed or identified as potentially controversial which are" .
    " currently under discussion.''\n\n{{ombox|text=Do not attempt to edit this list manually; [[User:RMCD bot|a bot]] will automatically update the page soon after the" .
    " {{tls|Requested move}} template is added to the discussion on the relevant talk page." .
    " The entry is removed automatically soon after the discussion is closed.<br />'''To make a change to an entry, make the change on the linked talk page.'''}}\n\n" .
    "'''This list is also available''' in a '''[[Wikipedia:Requested moves/Current discussions (alt)|page-link-first format]]''' and" .
    " in '''[[Wikipedia:Requested moves/Current discussions (table)|table format]].''' " . $relisted . " discussions have been relisted, indicated by ''(Di<u>scu</u>ss)''\n\n";

$oldsubmission = ":''This <noinclude>page</noinclude><includeonly>section</includeonly> lists all requests filed or identified as potentially controversial which are" .
    " currently under discussion.''\n\n{{ombox|text=Do not attempt to edit this list manually; [[User:RMCD bot|a bot]] will automatically update the page soon after the" .
    " {{tls|Requested move}} template is added to the discussion on the relevant talk page." .
    " The entry is removed automatically soon after the discussion is closed.<br />'''To make a change to an entry, make the change on the linked talk page.'''}}\n\n" .
    "'''This list is also available''' in a '''[[Wikipedia:Requested moves/Current discussions|discussion-link-first format]]''' and" .
    " in '''[[Wikipedia:Requested moves/Current discussions (table)|table format]].''' " . $relisted . " discussions have been relisted, indicated by ''(Di<u>scu</u>ss)''\n\n";

$summsubmission = "";

for ($dit = 0; $dit < ditmax; $dit++) {
	$submission .= "===" . $d[$dit] . "===\n";
	$submission .= $add[$dit];
	$oldsubmission .= "===" . $d[$dit] . "===\n";
	$oldsubmission .= $oldadd[$dit];
	$summsubmission .= "{{Dashboard grouping|c=#BDD8FF|'''[[Wikipedia:Requested moves#" . $d[$dit] . "|" . $d[$dit] . "]]'''}}\n";
	$summsubmission .= $summ[$dit] . "{{end}}\n\n";
}

if ($BLadd != "") {
	$BLhatnote = "{{hatnote|Elapsed listings fall into the backlog after 24 hours. Consider relisting 8-day-old discussions with minimal participation.}}";
	$submission .= "===Backlog===\n{{shortcut|WP:RMB}}\n" . $BLhatnote . "\n";
	$submission .= $BLadd;
	$oldsubmission .= "===Backlog===\n" . $BLhatnote . "\n";
	$oldsubmission .= $BLold;
	$summsubmission .= "{{Dashboard grouping|c=#BDD8FF|'''[[Wikipedia:Requested moves#Backlog|Backlog]]'''}}\n";
	$summsubmission .= $BLsumm . "{{end}}\n\n";
	echo "\n\n";
	$wprm = $objwiki->getpage("Wikipedia:Requested moves");
	$wprm = str_replace("{{admin backlog|bot=RMCD bot|disabled=yes}}", "{{admin backlog|bot=RMCD bot|backloglink=#Backlog}}", $wprm);
	echo "\nAdding backlog notice...\n";
	$objwiki->edit("Wikipedia:Requested moves",$wprm,"Adding backlog notice",false,true);
}
else {
	$wprm = $objwiki->getpage("Wikipedia:Requested moves");
	$wprm = str_replace("{{admin backlog|bot=RMCD bot|backloglink=#Backlog}}", "{{admin backlog|bot=RMCD bot|disabled=yes}}", $wprm);
	echo "\nRemoving backlog notice...\n";
	$objwiki->edit("Wikipedia:Requested moves",$wprm,"Removing backlog notice",false,true);
}

if ($MALadd != "") {
	$submission .= "===Time could not be ascertained===\n";
	$submission .= $MALadd;
	$oldsubmission .= "===Time could not be ascertained===\n";
	$oldsubmission .= $MALold;
	$summsubmission .= "{{Dashboard grouping|c=#BDD8FF|'''[[Wikipedia:Requested moves#Time could not be ascertained|Time could not be ascertained]]'''}}\n";
	$summsubmission .= $MALsumm . "{{end}}\n\n";
}

if ($malformed != "") {
	$submission .= "===Malformed requests===\n{{hatnote|Did you remember to submit your request by using {{tlxs|Requested move}}?" .
	  " See [[Wikipedia:Requested moves/Closing instructions#Bot considerations|\"Bot considerations\"]]}}\n";
	$submission .= $malformed;
	$oldsubmission .= "===Malformed requests===\n{{hatnote|Did you remember to submit your request by using {{tlxs|Requested move}}?" .
	  " See [[Wikipedia:Requested moves/Closing instructions#Bot considerations|\"Bot considerations\"]]}}\n";
	$oldsubmission .= $malformed;
}

if ($incomplete != "") {
	$submission .= "===Possibly incomplete requests===\n{{hatnote|See [[Wikipedia:Requested moves#Request all associated moves explicitly|\"Request all associated moves explicitly\"]]}}\n";
	$submission .= $incomplete;
	$oldsubmission .= "===Possibly incomplete requests===\n{{hatnote|See [[Wikipedia:Requested moves#Request all associated moves explicitly|\"Request all associated moves explicitly\"]]}}\n";
	$oldsubmission .= $incomplete;
}

$submission .= "===References===\n" .
    "{{hatnote|References generally should not appear here. Use {{tlx|reflist-talk}} in the talk page section with the requested move to show references there.}}\n" .
    "<references/>\n\n[[Category:Requested moves| ]]\n";

$oldsubmission .= "===References===\n" .
    "{{hatnote|References generally should not appear here. Use {{tlx|reflist-talk}} in the talk page section with the requested move to show references there.}}\n" .
    "<references/>\n\n[[Category:Requested moves| ]]\n";

$tablesubmission = "{{shortcut|WP:RMTABLE}}\n:''This table lists all move requests filed or identified as potentially controversial which are" .
    " currently under discussion.''\n\n{{ombox|text=Do not attempt to edit this table manually; [[User:RMCD bot|a bot]] will automatically update the page soon after the" .
    " {{tls|Requested move}} template is added to the discussion on the relevant talk page." .
    " The entry is removed automatically soon after the discussion is closed.<br />'''To make a change to an entry, make the change on the linked talk page.'''}}\n\n" .
    "'''This table is also available''' as a list including rationales and multi-moves in a '''[[Wikipedia:Requested moves/Current discussions|discussion-link-first format]]''' and in" .
    " a '''[[Wikipedia:Requested moves/Current discussions (alt)|page-link-first format]].''' " . $relisted . " ''di<u>scu</u>ssions'' have been {{Background color|#ffebeb|relisted}}.\n\n" .
    "{|class=\"wikitable sortable\"\n!scope=\"col\" style=\"width: 100px;\" | Original list date\n!data-sort-type=number | Days<br/>open\n" .
    "!scope=\"col\" style=\"width: 100px;\" | Current list date\n!Talk<br/>link\n!Current title → New title\n";

foreach ($otimestamp as $title => $time) {
	$secondsopen = time()-$time;
	$daysopen = 0;
	while ($secondsopen > ds) {
		$secondsopen = $secondsopen-ds;
		$daysopen += 1;
	}

	#echo "\n" . date("Y-m-d H:i", $time) . " (" . date("Y-m-d H:i", $timestamp[$title]) . ") " . " [" . $daysopen . "] " . $currentname[$title][0] . " → " . $newname[$title][0] .
	#   " " . $dlink[$title] . "\n";

	if ($daysopen == 0) {
		$tablesubmission .= "|-\n| " . date("Y-m-d H:i", $time) . " || style=\"text-align: center\" | < 1 || ";
	}
	else {
		$tablesubmission .= "|-\n| " . date("Y-m-d H:i", $time) . " || style=\"text-align: center\" |" . sprintf('%2d',$daysopen) . " || ";
	}
	if ($time != $timestamp[$title]) $tablesubmission .= "style=\"background: #ffebeb;\" | ";
	$tablesubmission .= date("Y-m-d H:i", $timestamp[$title]) . " || [[" . $title . "#" . $section[$title] . "|" . $dlink[$title] . "]] || [[" . $currentname[$title][0] . "]] → ";

	if ($newname[$title][0] == "?") {
		$tablesubmission .= "?\n";
	}
	else {
		$tablesubmission .= "{{no redirect|" . $newname[$title][0] . "}}\n";
	}

	#echo "Description> " . $description[$title] . "\n";
}

$tablesubmission .= "|}\n\n[[Category:Requested moves| ]]\n";

echo "\nPosting the new requested pagemoves...\n";
$objwiki->edit("Wikipedia:Requested moves/Current discussions",$submission,"Updating requested pagemoves list",false,true);
$objwiki->edit("Wikipedia:Requested moves/Current discussions (alt)",$oldsubmission,"Updating requested pagemoves list",false,true);
$objwiki->edit("Wikipedia:Dashboard/Requested moves",$summsubmission,"Updating requested pagemoves list",false,true);
$objwiki->edit("Wikipedia:Requested moves/Current discussions (table)",$tablesubmission,"Updating requested pagemoves table",false,true);
echo "done.\n\n";

# Remove closed subject notices
$subjecttranscludes = array();
$attempts = 0;
while (count($subjecttranscludes) == 0) {
		if ($attempts == 5) {
			die("Error 1");
		}
		else {
			echo "Checking for subject transclusions...\n";
			$subjecttranscludes = $objwiki->getTransclusions("User:RMCD bot/subject notice");
			$attempts += 1;
		}
}

$hosting = 0;
$hosted = 0;
$lua = 0;
$nf = 0;
$requestcount = array_fill(0, count($transcludes), 0);

for ($i = 0; $i < count($subjecttranscludes); $i++) { // Subject notices may transclude to Modules if they are placed on the module's documentation page
	if (strpos($subjecttranscludes[$i], "Module:") === 0) {
		echo $i . " : " . $subjecttranscludes[$i] . " => Lua module\n";
		$lua += 1;
		$requestcount[$j] += 1;
		continue;
	}

	for ($j = 0; $j < count($transcludes); $j++) {
		$pagename = $currentname[$transcludes[$j]][0];
		#echo "\n" . $subjecttranscludes[$i] . " <==> " . $pagename;

		if (":" . $subjecttranscludes[$i] == $pagename or $subjecttranscludes[$i] == $pagename) {
			echo $i . " : " . $subjecttranscludes[$i] . " => " . $j . "\n";
			$hosting += 1;
			$requestcount[$j] += 1;
			continue 2;
		}
	}

	$break = 0;
	$pagecontents = "";
	while ($pagecontents == "") {
		if ($break == 5) {
			break;
		}
		else {
			$pagecontents = $objwiki->getpage($subjecttranscludes[$i]);
			$break += 1;
		}
	}

	preg_match("/\{{2}User:RMCD bot\/subject notice.*\}{2}/i", $pagecontents, $multitalk);
	#echo "Template: ". $multitalk[0] . "\n";
	preg_match("/\|2\=.*\#/i", $multitalk[0], $multitalk);
	$crosstalk = preg_replace("/\|2\=/", "", $multitalk[0]);
	$crosstalk = preg_replace("/\#/", "", $crosstalk);
	echo "Multi-move discussion at: " . $crosstalk;

	$subjectcrosstalk = preg_replace("/Talk:/", ":", $crosstalk);
	$subjectcrosstalk = preg_replace("/talk:/", ":", $subjectcrosstalk);
	$subjectcrosstalk = preg_replace("/\s:/", ":", $subjectcrosstalk);
	echo "\nMulti-move discussion at talk page for: " . $subjectcrosstalk . "\n";

	for ($j = 0; $j < count($transcludes); $j++) {
		$pagename = $currentname[$transcludes[$j]][0];
		#echo "\n" . $subjecttranscludes[$i] . " → " . $subjectcrosstalk . " <==> " . $pagename;

		if ($subjectcrosstalk == $pagename or $subjectcrosstalk == ":" . $pagename) {
			echo $i . " : " . $subjecttranscludes[$i] . " → " . $subjectcrosstalk . " => " . $j . "\n";
			$hosted += 1;
			$requestcount[$j] += 1;
			$hosted_talkname[$hosted] = talkpagename($subjecttranscludes[$i]);
			continue 2;
		}
	}

	$crosscontents = $objwiki->getpage($crosstalk);
	if (preg_match("/\{{2}\s?(Requested move\/dated|movereq)\s?/iu", $crosscontents)) {
		echo "\nCentrally-hosted discussion on " . $crosstalk . "\n";
		echo $i . " : " . $subjecttranscludes[$i] . " → " . $subjectcrosstalk . "\n";
		$hosted += 1;
		#$requestcount[$j] += 1;
		$hosted_talkname[$hosted] = talkpagename($subjecttranscludes[$i]);
	}
	else {
		echo "\n" . $i . " : " . $subjecttranscludes[$i] . " => NOT FOUND\n";
		$nf += 1;

		$newpagecontents = preg_replace("/<noinclude>\{{2}User:RMCD bot\/subject notice.*\}{2}\s*\n<\/noinclude>/", "", $pagecontents);
		$newpagecontents = preg_replace("/<noinclude>\{{2}User:RMCD bot\/subject notice.*\}{2}<\/noinclude>(\n|)/", "", $newpagecontents);
		$newpagecontents = preg_replace("/\{{2}User:RMCD bot\/subject notice.*\}{2}(\n|)/", "", $newpagecontents);

		if ($newpagecontents == $pagecontents) {
			echo "!!! Failed to remove subject notice\n\n";
		}
		else {
			$objwiki->edit($subjecttranscludes[$i],$newpagecontents,"Removing notice of move discussion",false,true);
		}
	}
}

echo "\nHosting pages: " . $hosting;
echo "\nHosted pages: " . $hosted;
echo "\nLua modules (transcluded from documentation): " . $lua;
echo "\nNOT FOUND: " . $nf;
$total = $hosting + $hosted + $lua + $nf;
echo "\nTotal: " . $total . "\n\n";
$totalrequests = 0;

for ($j = 0; $j < count($requestcount); $j++) {
	echo "\n[" . $j . "] => " . $requestcount[$j] . "  " . $transcludes[$j];
}
echo "\n";
for ($j = 0; $j < count($requestcount); $j++) {
	if ($requestcount[$j] == 0) echo "\nNotice is not posted on " . $transcludes[$j];
	$totalrequests += $requestcount[$j];
}
echo "\n\nTotal requests: " . $totalrequests . "\n\n";

# Remove closed multimove notices
$multitranscludes = array();
$attempts = 0;
while (count($multitranscludes) == 0) {
		if ($attempts == 5) {
			die("Error 1");
		}
		else {
			echo "Checking for multimove transclusions...\n";
			$multitranscludes = $objwiki->getTransclusions("User:RMCD bot/multimove");
			$attempts += 1;
		}
}
print_r($hosted_talkname);
print_r($multitranscludes);

for ($i = 1; $i <= $hosted; $i++) {
	for ($j = 0; $j < count($multitranscludes); $j++) {
		if ($multitranscludes[$j] == $hosted_talkname[$i]) {
			continue 2;
		}
	}
	echo "\nNotice not found: " . $hosted_talkname[$i] . "\n";
}

for ($i = 0; $i < count($multitranscludes); $i++) {
	for ($j = 1; $j <= $hosted; $j++) {
		#echo "\n" . $i . "-->" . $multitranscludes[$i] . "  " . $j .  "-->" . $hosted_talkname[$j];
		if ($multitranscludes[$i] == $hosted_talkname[$j]) {
			#echo "\nFound " . $multitranscludes[$i];
			continue 2;
		}
	}

	echo "\nNot found: " . $multitranscludes[$i] . "\n";
	$break = 0;
	$pagecontents = "";
	while ($pagecontents == "") {
		if ($break == 5) {
			break;
		}
		else {
			$pagecontents = $objwiki->getpage($multitranscludes[$i]);
			$break += 1;
		}
	}

	preg_match("/\{{2}User:RMCD bot\/multimove.*\}{2}/i", $pagecontents, $multitalk);
	echo "Template: ". $multitalk[0] . "\n";
	$pagecontents = preg_replace("/\{{2}User:RMCD bot\/multimove.*\}{2}\n/", "", $pagecontents);
	#echo $pagecontents;
	if ($objwiki->nobots($multitranscludes[$i],botuser,$pagecontents) == true) {
		$objwiki->edit($multitranscludes[$i],$pagecontents,"Removing transcluded notice of move discussion",false,true);
	}
}

echo "\n\nMission accomplished.\n\n";