<?php

//namespace Icinga\Module\Agentinstaller\Forms\AgentInstaller;
//namespace Icinga\Module\Agentinstaller\Controllers;

use Icinga\Web\Controller;
use Icinga\Web\Session;
use Icinga\Forms\AgentInstaller;
use Icinga\Module\Agentinstaller\Forms\CreateInstallerForm;

class AgentInstaller_IndexController extends Controller {
	public function indexAction() {
        $form = $this->view->form = new CreateInstallerForm;
    }
    
	public function generateAction(){
	//Setup
	$client_name = $_GET['client_name'];
	$client_address   = $_GET['client_address'];

	$parent_name = $_GET['parent_name'];
	$parent_address = $_GET['parent_address'];
	$parent_zone = $_GET['parent_zone'];

	$output_dir = "/var/www/icingaclient/";

	$check_exists = shell_exec('icinga object list --type Host --name ' . escapeshellarg($client_name));
	if (strlen($check_exists) > 0) {
	    echo "Client already exists: $client_name";
	    return 1;
	}

	//get hostname IPs
	$client_address = gethostbyname($client_name);
	$parent_address = gethostbyname($parent_name);

	//if host name could not resolve fallback to IP
	$client_address = ($client_address == $client_name ? $_GET['clientaddress'] : $client_address);
	$parent_address = ($parent_address == $parent_name ? $_GET['parentaddress'] : $parent_address);

	// Generate the 'configuration package' api request body.
	// See 'Configuration Management' in the Icinga2 API documentation for
	// format. 
	$config = <<<EOT
{ "files": { "zones.d/$zone_name/$client_name.conf":"object Endpoint \\"$client_name\\" { host = \\"$client_address\\", port = \\"5665\\"}, object Zone \\"$client_name\\" { endpoints = [ \\"$client_name\\" ], parent = \\"$zone_name\\"}, object Host \\"$client_name\\" { import \\"generic-host\\", address = \\"$client_address\\", vars.os = \\"windows\\", vars.client_endpoint = name}" } }
EOT;

	$API_username = $this->Config()->get('agentinstaller', 'apikey', 'no username');
	$API_password = $this->Config()->get('agentinstaller', 'apipassword', 'no password');
	$API_address = $this->Config()->get('agentinstaller', 'apiaddress', 'https://localhost:5665');
	
	$url = "${API_address}/v1/config/stages/agentinstaller";

	try {
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERPWD, $API_username . ":" . $API_password);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $config);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type:application/json',
			'Accept:application/json'
		));

		$response = curl_exec($ch);
		if ($response === FALSE)
			throw new Exception(curl_error($ch), curl_errno($ch));
			curl_close($ch);
	} catch(Exception $e) {
		trigger_error(sprintf(
				'Curl failed with error #%d: %s',
				$e->getCode(), $e->getMessage()
			),
			E_USER_ERROR
		);
	}

	//Generate ssl keys
	$safe_client = escapeshellarg($client_name);

	$cert_res = shell_exec("sudo -u nagios icinga2 pki new-cert ".
		"--cn $safe_client ".
		"--key {$output_dir}working-dir/$safe_client.key ".
		"--csr {$output_dir}working-dir/$safe_client.csr");

	$csr_res = shell_exec("sudo -u nagios icinga2 pki sign-csr ".
		"--csr $output_dir"."working-dir/$safe_client.csr ".
		"--cert $output_dir"."working-dir/$safe_client.crt");

	//Generate config file for client
	$client_config = <<<EOT
/*
 * Initialise an API listener using signed certificates from the master 
 * node. Our client will communicate with its parent node through the
 * Icinga2 API.
 */
object ApiListener "api" {
  cert_path = SysconfDir + "/icinga2/pki/$client_name.crt"
  key_path = SysconfDir + "/icinga2/pki/$client_name.key"
  ca_path = SysconfDir + "/icinga2/pki/ca.crt"

  accept_config = true
  accept_commands = true
}


/* Define the Icing child-parent relationshaddress for this node. */
object Endpoint "$parent_name" {
	host = "$parent_address"
	port = "5665"
}

object Zone "$zone_name" {
	endpoints = [ "$parent_name" ]
}

object Endpoint "$client_name" {
}

object Zone "$client_name" {
	endpoints = [ "$client_name" ]
	parent = "$zone_name"
}

/* Initialise a global zone that will sync most config to the client. */
object Zone "global-templates" {
	global = true
}


/* Include config that is enabled using the `icinga2 feature` commands */
include "features-enabled/*.conf"

/*
 * Although we believe these are not called anywhere we define these constants 
 * just in case. For simplicity we match the node and zone names.
 */ 
const NodeName = "$client_name"
const ZoneName = NodeName

/**
 * The Icinga Template Library (ITL) provides a number of useful templates
 * and command definitions.
 * Common monitoring plugin command definitions are included separately.
 */
include <itl>
include <plugins>
include <plugins-contrib>
include <manubulon>       // Manubulon SNMP
include <windows-plugins> 
include <nscp>            // NSClient++ command templates

/* Define paths where the plugin binaries are found. */
const PluginDir = PrefixDir + "/sbin"
const ManubulonPluginDir = PrefixDir + "/sbin"
const PluginContribDir = PrefixDir + "/sbin"
EOT;

	//generate the parsed icinga2.conf to the appropriate directory
	$result = file_put_contents($output_dir."working-dir/icinga2.conf", $client_config);

	//run setup generator
	shell_exec(
		"sudo -u nagios makensis".
		"-DPARENT_NAME=$parent_name".
		"-DCLIENT_NAME=$client_name".
		"${output_dir}working-dir/buildagent.nsis"
	);

	//cleanup files
	unlink("{$output_dir}working-dir/{$client_name}.crt");
	unlink("{$output_dir}working-dir/{$client_name}.csr");
	unlink("{$output_dir}working-dir/{$client_name}.key");

	//Download link, necessary due to everthing being an XHR request
	echo "<a href='../download?clientname=${client_name}' target='_blank'>Download installer</a>";
	
	}
}
