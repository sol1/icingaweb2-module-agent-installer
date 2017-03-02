<?php

use Icinga\Web\Controller;
use Icinga\Web\Session;
use Icinga\Forms\AgentInstaller;
use Icinga\Module\Agentinstaller\Forms\CreateInstallerForm;

class AgentInstaller_IndexController extends Controller {
	public function indexAction() {
        $form = $this->view->form = new CreateInstallerForm;
    }
    
	protected function config_string($cname, $caddr, $pzone) {
		if (func_num_args() < 3) {
			printf("Not enough arguments specified\n");
			exit(1);
		}

		if (strlen($cname) <= 1) {
			printf("Client name undefined\n");
			exit(1);
		}
		if (strlen($caddr) <= 1) {
			printf("Client IP address undefined\n");
			exit(1);
		}
		if (strlen($pzone) <= 1) {
			printf("Parent zone undefined\n");
			exit(1);
		}

		/* Icinga2 API object definitions. */
		$a  = sprintf("object Endpoint \"%s\" {} ", $cname);
		$a .= sprintf("object Zone \"%s\" { ", $cname);
		$a .= sprintf("endpoints = [ \"%s\" ], ", $cname);
		$a .= sprintf("parent = \"%s\"}, ", $pzone);

		/* Icinga2 host object definition. */
		$b  = sprintf("object Host \"%s\" { ", $cname);
		$b .= sprintf("import \"%s\", ", "generic-host");
		$b .= sprintf("address = \"%s\", ", $caddr);
		$b .= sprintf("vars.os = \"%s\", ", "windows");
		$b .= sprintf("vars.client_endpoint = %s}", "name");

		/*
		 * Concatentate definitions, returning valid Icinga2 config as one
		 * fat string.
		 */
		$confstr = $a.$b;
		
		return $confstr;
	}

	protected function config_json($confstr) {
		/*
		 * Create an array mapping a client's configuration file to an Icinga2
		 * configuration string. Pass the generated config array to an array of config
		 * arrays. Finally return encoded json.
		 */
		$a = array("zones.d/$parent_zone/$client_name.conf" => $confstr);
		$b = array("files" =>  $b);

		if (json_encode($b)) {
			return json_encode($b);
		} else {
			printf("Error: %d - %s", json_last_error(), json_last_error_msg());
			exit(1);
		}
	}

	protected function config_ssl($outdir) {
		$safe_client = escapeshellarg($client_name);

		$cert_res = shell_exec("sudo -u nagios icinga2 pki new-cert ".
			"--cn $safe_client ".
			"--key {$outdir}/$safe_client.key ".
			"--csr {$outdir}/$safe_client.csr"
			);
		$csr_res = shell_exec("sudo -u nagios icinga2 pki sign-csr ".
			"--csr {$outdir}/$safe_client.csr ".
			"--cert {$outdir}/$safe_client.crt"
		);

		if (file_exists("{$outdir}/$safe_client.key") === FALSE) {
			printf("Client key not generated\n");
			exit(1);
		}
		if (file_exists("{$outdir}/$safe_client.csr") === FALSE) {
			printf("Client cert sign request not generated\n");
			exit(1);
		}
		if (file_exists("{$outdir}/$safe_client.crt") === FALSE) {
			printf("Client cert not generated\n");
			exit(1);
		}
		return 0;
	}

	protected function config_agent($cname, $pname, $paddr, $pzone) {
		if (func_num_args() < 4) {
			printf("Not enough arguments specified\n");
			exit(1);
		}
		if (strlen($cname) <= 1) {
			printf("Client name undefined\n");
			exit(1);
		}
		if (strlen($pname) <= 1) {
			printf("Parent name undefined\n");
			exit(1);
		}
		if (strlen($paddr) <= 1) {
			printf("Parent IP address undefined\n");
			exit(1);
		}
		if (strlen($pzone) <= 1) {
			printf("Parent zone undefined\n");
			exit(1);
		}

		$f = file_get_contents("icinga2.tmpl"); 

		$f = str_replace("CLIENT_NAME", $cname, $f);
		$f = str_replace("PARENT_NAME", $pname, $f);
		$f = str_replace("PARENT_ADDR", $paddr, $f);
		$f = str_replace("PARENT_ZONE", $pzone, $f);

		return $f;
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

		// Generate the 'configuration package' api request body.
		// See 'Configuration Management' in the Icinga2 API documentation for
		// format. 
		$confstr = $this->config_string($client_name, $client_address, $parent_zone);
		$body = $this->config_json($confstr);

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
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Content-Type:application/json',
						'Accept:application/json'
						));

			$response = curl_exec($ch);
			if ($response === FALSE) {
				throw new Exception(curl_error($ch), curl_errno($ch));
				curl_close($ch);
			}
		} catch(Exception $e) {
			trigger_error(
				sprintf('curl error: %d: %s', $e->getCode(), $e->getMessage()),
				E_USER_ERROR
			);
		}

		 /* Generate client's signed certificates to workspace dir. */
		if ($this->config_ssl("{$output_dir}working-dir") != 0) {
			printf("Error creating signed client certificates\n");
			exit(1);
		}

		/* Generate Icinga2 agent config file for the client. */
		$client_config = $this->config_agent(
			$client_name,
			$parent_name, $parent_address, $parent_zone
		);
		file_put_contents($output_dir."working-dir/icinga2.conf", $client_config);

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

?>
