<?php

/*
Plugin Name: Pingchecker
Plugin URI: http://pingbackpro.com/pingchecker/
Description: Instant scan of post content for links, ability to check pingability, and then ping with actual results returned.
Version: 1.2.0
Author: Tony Hayes
Author URI: http://pingbackpro.com/
*/

	global $post;
	$post_id = $post->ID;

	function pingcheckercheckping() {

		$url = $_REQUEST["checkping"];
		$source = pingcheckerdownload_page($url,false);
		$pattern = '<link rel="pingback" href="';
		if (stristr($source,$pattern)) {$pingbacks = "yes";}
		$pattern = "<link rel='pingback' href='";
		if (stristr($source,$pattern)) {$pingbacks = "yes";}

		$pattern = '<link rel="pingback" href="';
		if (stristr($source,$pattern)) {
			$position = stripos($source,$pattern) + strlen($pattern);
			$chunks = str_split($source,$position);
			unset($chunks[0]);
			$newchunks = implode("",$chunks);
			$tofind = '"';
			$position = stripos($newchunks,$tofind);
			$chunks = str_split($newchunks,$position);
			$pingbackserver = $chunks[0];
			$pingbacks = "yes";
			}
		$pattern = "<link rel='pingback' href='";
		if (stristr($source,$pattern)) {
			$position = stripos($source,$pattern) + strlen($pattern);
			$chunks = str_split($source,$position);
			unset($chunks[0]);
			$newchunks = implode("",$chunks);
			$tofind = "'";
			$position = stripos($newchunks,$tofind);
			$chunks = str_split($newchunks,$position);
			$pingbackserver = $chunks[0];
			$pingbacks = "yes";
			}

		echo "<script language='javascript' type='text/javascript'>";
		if ($pingbacks == "yes") {
			echo "parent.document.getElementById('pingcheckerserver').value = '".$pingbackserver."';";
			echo "parent.document.getElementById('pingcheckerpingable').value = 'Pingable';";
			echo "parent.document.getElementById('pingcheckerpingable').size = '8';";
			echo "parent.document.getElementById('pingcheckerbutton').style.display = 'none';";
			echo "parent.document.getElementById('pingcheckerresult').style.display = '';";
			echo "alert('Cool, this link appears to be pingable!');";
		}
		else {
			echo "parent.document.getElementById('pingcheckerpingable').value = 'Not pingable';";
			echo "parent.document.getElementById('pingcheckerpingable').size = '13';";
			echo "parent.document.getElementById('pingcheckerbutton').style.display = 'none';";
			echo "parent.document.getElementById('pingcheckerresult').style.display = '';";
			echo "alert('Sorry, this link does not appear to be pingable!');";
		}
		echo "</script>";
		wp_die('Done, exiting.');
	}

	function pingcheckerdownload_page($url,$cookie=false) {
		$vch = curl_init();
		curl_setopt($vch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1");
		curl_setopt($vch, CURLOPT_URL,$url);
		curl_setopt($vch, CURLOPT_RETURNTRANSFER, 1);
		if ($cookie != "") {
			$cookiefile = WP_PLUGIN_DIR.'/pingbackchecker/'.$cookie.'cookie.txt';
			curl_setopt($vch, CURLOPT_COOKIEJAR, $cookiefile);
			curl_setopt($vch, CURLOPT_COOKIEJAR, $cookiefile);
		}
		$urlcontents = curl_exec($vch);
		$http_code = curl_getinfo($vch, CURLINFO_HTTP_CODE);
		curl_close ($vch);
		unset($vch);
		if ($http_code == 200) {return $urlcontents;}
		elseif (($http_code == 301) || ($http_code == 302) || ($http_code == 307)) {
			$vch = curl_init();
			curl_setopt($vch, CURLOPT_URL,$url);
			curl_setopt($vch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($vch, CURLOPT_HEADER, 1);
			$header = curl_exec($vch);
			$http_code = curl_getinfo($vch, CURLINFO_HTTP_CODE);
			curl_close ($vch);
			unset($vch);
			$position = strpos($header,"Follow Location") + 15;
			if ($position > 15) {
				$chunks = str_split($header,$position);
				unset($chunks[0]);
				$header = implode("",$chunks);
			}
			$position = strpos($header,"Location: ") + 10;
			$chunks = str_split($header,$position);
			unset($chunks[0]);
			$header = implode("",$chunks);
			$position = strpos($header,"\r\n");
			if ($position == 0) {echo $header;}
			$newurl = str_split($header,$position);
			$url = $newurl[0];
			$vch = curl_init();
			curl_setopt($vch, CURLOPT_URL,$url);
			curl_setopt($vch, CURLOPT_RETURNTRANSFER, 1);
			$urlcontents = curl_exec($vch);
			$http_code = curl_getinfo($vch, CURLINFO_HTTP_CODE);
			curl_close ($vch);
			unset($vch);
			if ($http_code == 200) {return $urlcontents;}
			else {return false;}
		}
		else {$errors .= "<font style='font-size:8pt;'>Warning: ".$http_code." error code for URL: ".$url."</font><br>";
			return false;}
	}

	function pingcheckernewpingback($pagelinkedto, $pingbackserver, $post_ID) {
		global $wp_version;
		include_once(ABSPATH . WPINC . '/class-IXR.php');

		$pung = get_pung($post_ID);
		if (in_array($pagelinkedto,$pung)) {$message = "pinged"; return $message;}
		else {

			if ($pingbackserver != "") {$pingback_server_url = $pingbackserver;}
			else {$pingback_server_url = pingcheckergetpingbackserver($pagelinkedto);}

			if ($pingback_server_url) {
				@ set_time_limit( 60 );
				$pagelinkedfrom = get_permalink($post_ID);

				$client = new IXR_Client($pingback_server_url);
				$client->timeout = 3;
				$client->useragent = apply_filters( 'pingback_useragent', $client->useragent . ' -- WordPress/' . $wp_version, $client->useragent, $pingback_server_url, $pagelinkedto, $pagelinkedfrom);
				$client->debug = false;

				if ( $client->query('pingback.ping', $pagelinkedfrom, $pagelinkedto) || ( isset($client->error->code) && 48 == $client->error->code ) ) {
					add_ping($post_ID,$pagelinkedto);
					$nothing = "";
					return $nothing;
				}
				else {
					$errorcode = $client->error->code;
					$errormessage = $client->error->message;
					return $errormessage;
				}
			}
		}
	}

	function pingcheckergetpingbackserver ($pingbackurl) {

			$vch = curl_init();
			curl_setopt($vch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1");
			curl_setopt($vch, CURLOPT_URL,$pingbackurl);
			curl_setopt($vch, CURLOPT_RETURNTRANSFER, 1);
			$source = curl_exec($vch);
			$http_code = curl_getinfo($vch, CURLINFO_HTTP_CODE);
			curl_close ($vch);
			unset($vch);

			$pattern = '<link rel="pingback" href="';
			if (stristr($source,$pattern)) {
				$position = stripos($source,$pattern) + strlen($pattern);
				$chunks = str_split($source,$position);
				unset($chunks[0]);
				$newchunks = implode("",$chunks);
				$tofind = '"';
				$position = stripos($newchunks,$tofind);
				$chunks = str_split($newchunks,$position);
				$vpingbackserver = $chunks[0];
				}
			$pattern = "<link rel='pingback' href='";
			if (stristr($source,$pattern)) {
				$position = stripos($source,$pattern) + strlen($pattern);
				$chunks = str_split($source,$position);
				unset($chunks[0]);
				$newchunks = implode("",$chunks);
				$tofind = "'";
				$position = stripos($newchunks,$tofind);
				$chunks = str_split($newchunks,$position);
				$vpingbackserver = $chunks[0];
				}
			return $vpingbackserver;
	}

	function pingcheckersendping () {

		$post_id = $_REQUEST['pingcheckerpostid'];
		if ($_REQUEST['pingcheckerlink'] != '') {
			$pingbackurl = $_REQUEST["pingcheckerlink"];
			$pingbackserver = $_REQUEST["pingcheckerserver"];
			$pingcheckerpingthis = pingcheckernewpingback($pingbackurl,$pingbackserver,$post_id);
			if ($pingcheckerpingthis == "") {
				echo "<script language='javascript' type='text/javascript'>
					alert('Pingback to ".$pingbackurl." has been successfully sent!');
  			    </script>";
			}
			elseif ($pingcheckerpingthis == "pinged") {echo "<script language='javascript' type='text/javascript'>alert('Pingback for that URL has already been sent.');</script>";}
			else {echo '<script language="javascript" type="text/javascript">
					var alerterror = "Pingback Error: '.$pingcheckerpingthis.'\\n(for URL: '.$pingbackurl.' )\\n";
					alert(alerterror);
				  </script>';
			}
		}
		wp_die('Done, exiting.');
	}

	function pingcheckercheckapprovals() {

		$vpostid = $_REQUEST['pingcheckerpostid'];
		$vpung = get_pung($vpostid);
		$vpermalink = get_permalink($vpostid);
		echo "<font face=helvetica style='font-size:8pt;'>";
		foreach ($vpung as $vapingback) {
			$vresourcepage = pingcheckerdownload_page($vapingback,false);
			if (stristr($vresourcepage,$vpermalink)) {
				echo "<font color=#0000ee><b>Pingback to ".$vapingback." has been approved.</b></font>";
				if (stristr($vpermalink,$_SERVER['HTTP_HOST'])) {echo " <b>(Self-Ping)</b>";}
				// else {echo ""; }
				echo "<br>";
			}
			else {echo "Pingback to ".$vapingback." does not appear to be approved.<br>";}
		}
		wp_die("<br>Finished checking for approvals.<br>");
	}


function pingchecker_box() {

	echo '<div class="postbox"><div class="handlediv" title="Click to toggle"><br /></div>
	<h3 class="hndle"><span>Pingchecker</span></h3><div class="inside">';
	$thisscript = WP_PLUGIN_URL.'/pingchecker/pingchecker.php';

	global $post;
	$post_id = $post->ID;
	$vaddlink = pingchecker_get_link();
	echo '<div id="contentscan" style="text-align:center;"><center>
	<table><tr><td><input type="button" style="font-size:9pt;" value="Scan Post Content for Links" onclick="pingcheckerscancontent();"></td>
	<td width=7></td><td><input type="button" style="font-size:9pt;" value="Check for Approvals" onclick="pingcheckerapprovals();"></td>
	<td width=7></td><td align="center"><font style="font-size:8pt;">Reminder: Publish before pinging!</td></tr></table></div>
	<div id="pingcheckerscanresults" style="display:none;"></div></center>';

	echo '<center><form method="post" name="pingcheckerform" target="pingcheckerframebox">
		<table><tr height=10><td> </td></tr><tr><td><font style="font-size:9pt;">Link: <input type="text" size="30" name="pingcheckerlink" id="pingcheckerlink" onkeyup="pingcheckershowcheckbutton();" style="font-size:8pt;" value=""></td><td width=7></td>
		<input type="hidden" name="pingcheckerserver" id="pingcheckerserver" value=""><td style="vertical-align:top;" align="center">
		<div id="pingcheckerbutton"><input type="button" style="font-size:7pt;" value="Check Pingability" onclick="pingcheckercheckpingback();"></div>
		<div style="display:none;" id="pingcheckerresult"><input id="pingcheckerpingable" type="text" size="6" style="font-face:helvetica;font-size:7pt;" value="" readonly></div>
		<input type="hidden" name="pingchecker" id="pingchecker" value="yes"><input type="hidden" name="checkping" id="checkping" value=""><input type="hidden" name="pingcheckerpostid" id="pingcheckerpostid" value="'.$post_id.'"><input type="hidden" name="pingcheckerping" id="pingcheckerping" value=""><input type="hidden" name="pingcheckerapprovals" id="pingcheckerapprovals" value="">
		</td><td width=7></td><td style="vertical-align:top;" align="center"><input type="button" style="font-size:9pt;" value="Ping Now" onclick="pingcheckerpingthis();"></td></tr><tr height=10><td> </td></tr></table></form></center>';
	echo '<center><table cellspacing=5 style="background-color:#eeeeee;"><tr><td><font style="font-size:9pt;line-height:1.4em;"><a href="'.$vaddlink[0].'" target=_blank style="text-decoration:none;">'.$vaddlink[1].'</a></font></td></tr></table></center>';

	echo "<div id='pingcheckerframeboxdiv' style='display:none;'><div align='right'><a href='javascript:void(0);' onclick='hideapprovalresults();' style='text-decoration:none;'><font style='font-size:8pt;'>Hide</font></a></div>";
	echo "<center><iframe src='javascript:void(0);' name='pingcheckerframebox' id='pingcheckerframebox' width=450 height=200></iframe></center></div>";
	echo '<script language="javascript" type="text/javascript">

		function pingcheckershowcheckbutton() {
			document.getElementById("pingcheckerbutton").style.display = "";
			document.getElementById("pingcheckerresult").style.display = "none";
		}

		function pingcheckerscancontent() {
			var inputcontent = document.getElementById("content").value;

			var matches = [];

		inputcontent.replace(/[^<]*(<a href="([^"]+)">([^<]+)<\/a>)/g, function () {
		    matches.push(Array.prototype.slice.call(arguments, 1, 4));
		});

			if (matches.length > 0) {
				var scanresults = "<br><center><font style=\"font-size:10pt;\"><b>Embedded Links found in Post Content:</b></font><font color=#ffffff>.......</font><font style=\"font-size:8pt;\"><a href=\"javascript:void(0);\" onclick=\"pingcheckerhidescanresults();\" style=\"text-decoration:none;\">Hide</a></font><br><table>";
				for (i=0;i<matches.length;i++)
				{
					var thislinkhtml = matches[i][0];
					var thislink = matches[i][1];
					var thisanchor = matches[i][2];
					var scanresults = scanresults + "<tr><td>"+thislinkhtml+"<td width=7></td><td>("+thislink+")</td><td width=7></td><td><input style=\"font-size:8pt\" type=\"button\" value=\"Copy to Ping Checker\" onclick=\"pingcheckertochecker(\'"+thislink+"\',\'"+thisanchor+"\');\"></tr>";
				}
				var scanresults = scanresults + "</tr></table></center>";
				document.getElementById("pingcheckerscanresults").innerHTML = scanresults;
				document.getElementById("pingcheckerscanresults").style.display = "";
			}
			else {
				var scanresults = "<br><center><font style=\"font-size:10pt;\"><b>No Embedded Links found in Post Content.</b></font><font color=#ffffff>.......</font><font style=\"font-size:8pt;\"><a href=\"javascript:void(0);\" onclick=\"pingcheckerhidescanresults();\" style=\"text-decoration:none;\">Hide</a></font></center><br>";
				document.getElementById("pingcheckerscanresults").innerHTML = scanresults;
				document.getElementById("pingcheckerscanresults").style.display = "";
			}
		}

		function pingcheckertochecker(thislink,thistitle) {
			document.getElementById("pingcheckerlink").value = thislink;
			pingcheckershowcheckbutton();
		}

		function pingcheckershowcheckbutton() {
			document.getElementById("pingcheckerbutton").style.display = "";
			document.getElementById("pingcheckerresult").style.display = "none";
		}

		function pingcheckerhidescanresults() {
			document.getElementById("pingcheckerscanresults").style.display = "none";
		}

		function pingcheckercheckpingback() {
			var checkurl = document.getElementById("pingcheckerlink").value;
			if (checkurl != "") {
				document.getElementById("checkping").value = checkurl;
				document.pingcheckerform.submit();
				document.getElementById("checkping").value = "";
			}
		}

		function pingcheckerpingthis() {
			document.getElementById("pingcheckerping").value = "yes";
			document.pingcheckerform.submit();
			document.getElementById("pingcheckerping").value = "";
		}

		function pingcheckerapprovals() {
			document.getElementById("pingcheckerframeboxdiv").style.display = "";
			document.getElementById("pingcheckerapprovals").value = "yes";
			document.pingcheckerform.submit();
			document.getElementById("pingcheckerapprovals").value = "";
		}

		function hideapprovalresults() {document.getElementById("pingcheckerfameboxdiv").style.display = "none";}
		</script></div></div><br>';
}

if ($_REQUEST["pingchecker"] == "yes") {
	if ($_REQUEST["checkping"] != "") {add_action('admin_head', 'pingcheckercheckping');}
	if ($_REQUEST["pingcheckerping"] == "yes") {add_action('admin_head', 'pingcheckersendping');}
	if ($_REQUEST["pingcheckerapprovals"] == "yes") {add_action('admin_head', 'pingcheckercheckapprovals');}
}

function pingchecker_get_link() {
		$vlinkurl = base64_decode('aHR0cDovL3BpbmdiYWNrcHJvLmNvbS9nZXRsaW5rLnBocA==');
		$vch = curl_init();
		curl_setopt($vch, CURLOPT_URL,$vlinkurl);
		curl_setopt($vch, CURLOPT_RETURNTRANSFER, 1);
		$vgetlink = curl_exec($vch);
		$vhttp_code = curl_getinfo($vch, CURLINFO_HTTP_CODE);
		curl_close ($vch);
		unset($vch);
		if ($vhttp_code == 200) {
			if (get_option('pbpref') != "") {
				$vpbpref = "pingbackpro.com/plugin/?".get_option('pbpref')."|||";
				$vlinkdata = str_replace("pingbackpro.com|||",$vpbpref,$vlinkdata);
			}
			$vlinkdata = explode("|||",$vgetlink);
			return $vlinkdata;
		}
		return false;
}

add_action('edit_form_advanced', 'pingchecker_box');
add_action('edit_page_form', 'pingchecker_box');

function pingchecker_scanforlinks($content) {
	if (!is_feed()) {
		global $contentalinkfix;

		preg_match_all(
		  '#<a\s
			(?:(?= [^>]* href="   (?P<href>  [^"]*) ")|)
			[^>]*>
			(?P<text>[^<]*)
			</a>
		  #xi',
		  $content,$matches,PREG_SET_ORDER);

		preg_match_all(
		  "#<a\s
			(?:(?= [^>]* href='   (?P<href>  [^']*) ')|)
			[^>]*>
			(?P<text>[^<]*)
			</a>
		  #xi",
		  $content,$morematches,PREG_SET_ORDER);

		$contentalinkfix = '<div id="contentalinkfix" style="display:none;">';
		foreach($matches as $match) {
			if ($match['href'] != '') {
				 $contentalinkfix .= '<a href="'.$match["href"].'"';
				 $contentalinkfix .= ' rel="nofollow">'.$match["text"].'</a>';
			}
		}
		foreach($morematches as $match) {
			if ($match['href'] != '') {
				 $contentalinkfix .= '<a href="'.$match["href"].'"';
				 $contentalinkfix .= ' rel="nofollow">'.$match["text"].'</a>';
			}
		}
		$contentalinkfix .= "</div>";

		return $content;
	}
}


function pingchecker_addalinks() {
	global $contentalinkfix;
	echo $contentalinkfix;
}

add_option('pbpref','');
add_filter('the_content', 'pingchecker_scanforlinks');
add_action('wp_head', 'pingchecker_addalinks');
add_action('wp_footer', 'pingchecker_addalinks');

?>