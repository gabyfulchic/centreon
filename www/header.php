<?
/**
Oreon is developped with GPL Licence 2.0 :
http://www.gnu.org/licenses/gpl.txt
Developped by : Julien Mathis - Romain Le Merlus - Christophe Coraboeuf

Adapted to Pear library by Merethis company, under direction of Cedrick Facon, Romain Le Merlus, Julien Mathis

The Software is provided to you AS IS and WITH ALL FAULTS.
OREON makes no representation and gives no warranty whatsoever,
whether express or implied, and without limitation, with regard to the quality,
safety, contents, performance, merchantability, non-infringement or suitability for
any particular or intended purpose of the Software found on the OREON web site.
In no event will OREON be liable for any direct, indirect, punitive, special,
incidental or consequential damages however they may arise and even if OREON has
been previously advised of the possibility of such damages.

For information : contact@oreon-project.org
*/
	# Bench
	function microtime_float() 	{
	   list($usec, $sec) = explode(" ", microtime());
	   return ((float)$usec + (float)$sec);
	}

	set_time_limit(60);

	$time_start = microtime_float();

	# Define
	define('SMARTY_DIR', '../GPL_LIB/Smarty/libs/');

	# Include
	require_once ("./oreon.conf.php");
	require_once("DBconnect.php");
	require_once ("$classdir/Session.class.php");
	require_once ("$classdir/Oreon.class.php");
	require_once (SMARTY_DIR."Smarty.class.php");

    # Cut Page ID
	$level1 = NULL;
	$level2 = NULL;
	$level3 = NULL;
	$level4 = NULL;
	switch (strlen($p))	{
		case 1 :  $level1= $p; break;
		case 3 :  $level1 = substr($p, 0, 1); $level2 = substr($p, 1, 2); $level3 = substr($p, 3, 2); break;
		case 5 :  $level1 = substr($p, 0, 1); $level2 = substr($p, 1, 2); $level3 = substr($p, 3, 2); break;
		case 6 :  $level1 = substr($p, 0, 2); $level2 = substr($p, 2, 2); $level3 = substr($p, 3, 2); break;
		case 7 :  $level1 = substr($p, 0, 1); $level2 = substr($p, 1, 2); $level3 = substr($p, 3, 2); $level4 = substr($p, 5, 2); break;
		default : $level1= $p; break;
	}
	
   	Session::start();
	if (version_compare(phpversion(), '5.0') < 0) {
	    eval('
	    function clone($object) {
	      return $object;
	    }
	    ');
	}  

	if (isset($_POST["export_sub_list"])) {
		$mime_type = 'text/x-text';
		if (!strcmp($_GET['p'], "2"))
			$mime_type .= 'image/jpeg';
		$filename = "oreon_" . date("Y-m-d") . ".sql";
		// Send headers
		header('Content-Type: '.$mime_type);
		// IE need specific headers
		if (stristr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
			header("Content-Disposition: inline; filename=\"".$filename."\"");
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
		} else {
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header('Expires: 0');
			header('Pragma: no-cache');
		}
		if (isset($_POST["s"]) && ($_POST["s"] == 1 || $_POST["s"] == 2 || $_POST["s"] == 4))	{
			include("./include/options/db/extractDB/extract_sub.php");exit();}
	} else {

		# Skin path
		$res =& $pearDB->query("SELECT template FROM general_opt LIMIT 1");
		$res->fetchInto($data);
		$skin = "./Themes/".$data["template"]."/";
		
		# Delete Session Expired
		$res =& $pearDB->query("SELECT session_expire FROM general_opt LIMIT 1");
		$session_expire =& $res->fetchRow();
		$time_limit = time() - ($session_expire["session_expire"] * 60);
		$res =& $pearDB->query("SELECT * FROM session WHERE last_reload < '".$time_limit."'");
		while ($session =& $res->fetchRow())
			$pearDB->query("DELETE FROM session WHERE session_id = '".$session["session_id"]."'");

		# Get session and Check if session is not expired

		$res =& $pearDB->query("SELECT user_id FROM session WHERE `session_id` = '".session_id()."'");
		if (!$res->numRows())
			header("Location: index.php?disconnect=2");			
		if (!isset($_SESSION["oreon"]))
			header("Location: index.php?disconnect=1");

		# Define Oreon var alias
		global $oreon;
		$oreon =& $_SESSION["oreon"];

		if (!is_object($oreon))
			exit();

		# Init differents elements we need in a lot of pages
		$oreon->user->createLCA($pearDB);
		$oreon->initNagiosCFG($pearDB);
		$oreon->initOptGen($pearDB);

		// Update Session Table For last_reload and current_page row
		$res =& $pearDB->query("UPDATE `session` SET `current_page` = '".$level1.$level2.$level3."',`last_reload` = '".time()."', `ip_address` = '".$_SERVER["REMOTE_ADDR"]."' WHERE CONVERT( `session_id` USING utf8 ) = '".session_id()."' AND `user_id` = '".$oreon->user->user_id."' LIMIT 1");
		if (PEAR::isError($res))
			print ($res->getMessage());

		// Load traduction in the selected language.
		is_file ("./lang/".$oreon->user->get_lang().".php") ? include_once ("./lang/".$oreon->user->get_lang().".php") : include_once ("./lang/en.php");
		is_file ("./include/configuration/lang/".$oreon->user->get_lang().".php") ? include_once ("./include/configuration/lang/".$oreon->user->get_lang().".php") : include_once ("./include/configuration/lang/en.php");
		is_file ("./include/monitoring/lang/".$oreon->user->get_lang().".php") ? include_once ("./include/monitoring/lang/".$oreon->user->get_lang().".php") : include_once ("./include/monitoring/lang/en.php");
		is_file ("./include/options/lang/".$oreon->user->get_lang().".php") ? include_once ("./include/options/lang/".$oreon->user->get_lang().".php") : include_once ("./include/options/lang/en.php");
		is_file ("./include/reporting/lang/".$oreon->user->get_lang().".php") ? include_once ("./include/reporting/lang/".$oreon->user->get_lang().".php") : include_once ("./include/reporting/lang/en.php");
		is_file ("./include/views/lang/".$oreon->user->get_lang().".php") ? include_once ("./include/views/lang/".$oreon->user->get_lang().".php") : include_once ("./include/views/lang/en.php");
		is_file ("./include/tools/lang/".$oreon->user->get_lang().".php") ? include_once ("./include/tools/lang/".$oreon->user->get_lang().".php") : include_once ("./include/tools/lang/en.php");

		foreach ($oreon->modules as $module)
			$module["lang"] ? (is_file ("./modules/".$module["name"]."/lang/".$oreon->user->get_lang().".php") ? include_once ("./modules/".$module["name"]."/lang/".$oreon->user->get_lang().".php") : include_once ("./modules/".$module["name"]."/lang/en.php")) : NULL;
		print "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>";

        $mlang = $oreon->user->get_lang();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<? echo $mlang; ?>" lang="<? echo $mlang; ?>">
<head>
<title>Supervision Tool - Powered By Oreon</title>
<link rel="shortcut icon" href="./img/iconOreon.ico"/>
<link rel="stylesheet" type="text/css" href="./include/common/javascript/autocompletion.css" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<link href="<? echo $skin; ?>style.css" rel="stylesheet" type="text/css"/>
<link href="<? echo $skin; ?>menu.css" rel="stylesheet" type="text/css"/>
<link href="<? echo $skin; ?>configuration_form.css" rel="stylesheet" type="text/css"/>
<link href="<? echo $skin; ?>color<? echo $level1; ?>.css" rel="stylesheet" type="text/css"/>
<script language='javascript' src='./include/common/javascript/ajaxStatusCounter.js'></script>
<?
	// Add Template CSS for sysInfos Pages
	if (isset($p) && !strcmp($p, "505") && file_exists("./include/options/sysInfos/templates/classic/classic.css"))
	  echo "  <link rel=\"stylesheet\" type=\"text/css\" href=\"./include/options/sysInfos/templates/classic/classic.css\">\n";

	if (isset($p) && $p == 310)
		print "<SCRIPT language='javascript' src='./include/common/javascript/datepicker.js'></SCRIPT>";

/*
 * include javascript
 */

	$res = null;
	$res = $pearDB->query("SELECT PathName_js, init FROM topology_JS WHERE id_page = '".$p."' AND (o = '" . $o . "' OR o IS NULL)");
		if (PEAR::isError($res))
			print ($res->getMessage());
	while ($res->fetchInto($topology_js))
		echo "<script language='javascript' src='" . $topology_js['PathName_js'] .  "'></script> ";		

/*
 * init javascript
 */
	$tS = $oreon->optGen["AjaxTimeReloadStatistic"] * 1000;
	$tM = $oreon->optGen["AjaxTimeReloadMonitoring"] * 1000;
	$oreon->optGen["AjaxFirstTimeReloadStatistic"] == 0 ? $tFS = 10 : $tFS = $oreon->optGen["AjaxFirstTimeReloadStatistic"] * 1000;
	$oreon->optGen["AjaxFirstTimeReloadMonitoring"] == 0 ? $tFM = 10 : $tFM = $oreon->optGen["AjaxFirstTimeReloadMonitoring"] * 1000;
	$sid = session_id();

?>
<script type='text/javascript'>
    window.onload = function () {
    setTimeout('reloadStatusCounter(<?=$tS?>,"<?=$sid?>")', <?=$tFS?>);
<?

	$res = null;
	$res = $pearDB->query("SELECT PathName_js, init FROM topology_JS WHERE id_page = '".$p."' AND (o = '" . $o . "' OR o IS NULL)");
		if (PEAR::isError($res))
			print ($res->getMessage());
	while ($res->fetchInto($topology_js)){
		if($topology_js['init'] == "initM")	{
		?>setTimeout('initM(<?=$tM?>,"<?=$sid?>")', <?=$tFM?>);<?
		}		
		else if ($topology_js['init'])
			echo $topology_js['init'] .";";
	}
?>
    };
    </script>
</head>
<body>
<? } ?>