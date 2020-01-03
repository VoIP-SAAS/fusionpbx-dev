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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('multi_node_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//check for the ids
	if (is_array($_REQUEST) && sizeof($_REQUEST) > 0) {

        // die(print_r($_REQUEST));
        $multinode_uuids = $_REQUEST["id"];
        
        // die(print_r($multinode_uuids));

		foreach($multinode_uuids as $multinode_uuid) {
            
            $multinode_uuid = check_str($multinode_uuid);
            
			if ($multinode_uuid != '') {
				
				//log the transaction results
				//delete the extension
				$sql = "select switch_name,node_priority from v_multinode ";
		                //$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
                		$sql .= "where multinode_uuid = '".$multinode_uuid."'";
                		$result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                		foreach ($result as &$row) {
		                        $switch_name = $row['switch_name'];
                		        $node_priority = $row['node_priority'];
                		}

					$sql = "delete from v_multinode ";
					//$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "where multinode_uuid = '".$multinode_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					unset($prep_statement, $sql);
                                        $cmd = "api switchname";
                                        $response = trim(event_socket_request_cmd($cmd));
                                        unset($cmd);
                                        if ( ($switch_name == $response) and ($node_priority == 'primary')){
                                                //save_amqp_xml($response);
                                                $cmd = "api unload mod_amqp";
                                                event_socket_request_cmd($cmd);
                                                unset($cmd);
                                        }
					elseif(($switch_name == $response) and ($node_priority == 'secondary')){
						save_amqp_xml($response);
                                                $cmd = "api reload mod_amqp";
                                                event_socket_request_cmd($cmd);
                                                unset($cmd);

					}
                                        else
                                        {
                                                echo "sorry";
                                                //save_amqp_xml();
                                        }
	
			}
		}


		//synchronize configuration
			// if (is_readable($_SESSION['switch']['extensions']['dir'])) {
			// 	$extension = new extension;
			// 	$extension->xml();
			// }
	}

//redirect the browser
	$_SESSION["message"] = $text['message-delete'];
	header("Location: multi_node.php");
	return;

?>
