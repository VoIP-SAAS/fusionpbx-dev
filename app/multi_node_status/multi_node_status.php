<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

if (permission_exists('system_multi_node_status')
	|| permission_exists('multi_node_status')
	|| if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//define variables
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	require_once "resources/header.php";
	$document['title'] = $text['title-sip-status'];

	$msg = $_GET["savemsg"];
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if (!$fp) {
		$msg = "<div align='center'>".$text['error-event-socket']."<br /></div>";
	}
	if (strlen($msg) > 0) {
		echo "<div align='center'>\n";
		echo "<table width='40%'>\n";
		echo "<tr>\n";
		echo "<th align='left'>".$text['label-message']."</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='row_style1'><strong>$msg</strong></td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
	}

//get the gateways
 		if ($fp) {
                $hostname = trim(event_socket_request($fp, 'api switchname'));
	        }

//get the rabitMQ profiles
	$sql = "select * from v_multinode ";
	$sql .= "where node_priority = 'primary' ";
	if ($hostname) {
		$sql .= "and (switch_name = '" . check_str($hostname) . "') ";
	}
	$sql .= "order by node_priority asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$multi_profiles = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);

//multi node status
	if ($fp && permission_exists('system_multi_node_status') || permission_exists('multi_node_status')) {
		echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		echo "<tr>\n";
		echo "<td width='50%'>\n";
		echo "	<b>".$text['header-sip-status']."</b>";
		echo "	<br><br>";
		echo "</td>\n";
		echo "<td width='50%' align='right'>\n";
		echo "<input type='button' class='btn' value='".$text['button-refresh']."' onclick=\"document.location.href='multi_node_status.php';\" />\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<table width='100%' cellpadding='0' cellspacing='0' border='0' style='margin-bottom: 10px;'>\n";
		echo "<tr>\n";
//		echo "<td><b><a href='javascript:void(0);' onclick=\"$('#sofia_status').slideToggle();\">".$text['title-sofia-status']."</a></b></td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<div id='sofia_status' style='margin-top: 20px; margin-bottom: 30px;'>";
		echo "<table width='100%' cellspacing='0' border='0'>\n";
		echo "<tr>\n";
		echo "<th>".$text['label-name']."</th>\n";
		echo "<th>".$text['label-data']."</th>\n";
		echo "<th>".$text['label-type']."</th>\n";
		echo "<th>".$text['label-state']."</th>\n";
		echo "</tr>\n";
		foreach ($multi_profiles as $field) {
                                        $multi_hostname = $field["hostname"];
					$multi_virtualhost=$field["virtualhost"];
					if ($field["virtualhost"] == "/"){  $multi_virtualhost="%2f"; }
                                        $multi_username = $field["username"];
                                        $multi_password = $field["password"];
                                        $multi_exchange_name = $field["exchange_name"];
                        $ch = curl_init();
                     	//curl_setopt($ch, CURLOPT_URL,"http://localhost:15672/api/exchanges/%2f/main.topic");
			curl_setopt($ch, CURLOPT_URL,"http://".$multi_hostname.":15672/api/exchanges/".$multi_virtualhost."/".$multi_exchange_name);
                        curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_USERPWD, 'guest:guest');
                        $headers = array();
                        $headers[] = 'Content-Type: application/json';
                        $output = curl_exec($ch);
                        curl_close($ch);
                        $curlJSON = json_decode($output);
			//print_r($curlJSON);exit;
                        if (!$curlJSON->error){  $state="Connected";}
			if ($curlJSON->error){  $state="Not Connected";}
                        echo "<tr>\n";
                        echo "  <td class='".$row_style[$c]."'>".$field["name"]."</td>\n";
                        echo "  <td class='".$row_style[$c]."'>".$field["exchange_name"]."</td>\n";
                        echo "  <td class='".$row_style[$c]."'>".$field["exchange_type"]."</td>\n";
                        echo "  <td class='".$row_style[$c]."'>".$state."</td>\n";
                        echo "</tr>\n";
                        if ($c==0) { $c=1; } else { $c=0; }
                }
		echo "</table>\n";
		echo "</div>\n";
		unset($xml);
	}

//include the footer
	require_once "resources/footer.php";

?>
