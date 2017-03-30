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
		/* Icinga2 API object definitions. */
		$s  = sprintf("object Endpoint \"%s\" {} ", $cname);
		$s .= sprintf("object Zone \"%s\" { ", $cname);
		$s .= sprintf("endpoints = [ \"%s\" ], ", $cname);
		$s .= sprintf("parent = \"%s\"}, ", $pzone);

		/* Icinga2 host object definition. */
		$t  = sprintf("object Host \"%s\" { ", $cname);
		$t .= sprintf("import \"%s\", ", "generic-host");
		$t .= sprintf("address = \"%s\", ", $caddr);
		$t .= sprintf("vars.os = \"%s\", ", "windows");
		$t .= sprintf("vars.client_endpoint = %s}", "name");

		/*
		 * Concatentate definitions, returning valid Icinga2 config as one
		 * fat string.
		 */
		$confstr = $s.$t;

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

		$f = file_get_contents("icinga2.tmpl"); 

		$f = str_replace("CLIENT_NAME", $cname, $f);
		$f = str_replace("PARENT_NAME", $pname, $f);
		$f = str_replace("PARENT_ADDR", $paddr, $f);
		$f = str_replace("PARENT_ZONE", $pzone, $f);

		return $f;
	}

	/* Find the current 'active-stage' name of configuration packages. */
	protected function activestage() {
		$url = "https://localhost:5665/v1/config/packages";

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERPWD, $API_username . ":" . $API_password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt(
			$ch, CURLOPT_HTTPHEADER, array(
				'Content-Type:application/json',
				'Accept:application/json'
			)
		);

		$res = curl_exec($ch);
		if ($res === FALSE) {
				throw new Exception(curl_error($ch), curl_errno($ch));
				curl_close($ch);
		} else {
			curl_close($ch);
			/*
			 * Loop through the 'results' array. Each entry is a dictionary
			 * representing each configuration package.
			 */
			$jres = json_decode($res, true);
			$pkg = $jres['results'];
			foreach ($pkg as $k => $v) {
				return $v['active-stage'];
			}
		}
	}

	/* For a given stage, list its files as entries in an array. */
	protected function lsstage($stage) {
		$url = "https://suboptic.sol1.net:5665/v1/config/stages/agentinstaller/";
		$url .= $stage;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERPWD, $API_username . ":" . $API_password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type:application/json',
				'Accept:application/json'
			)
		);

		$res = curl_exec($ch);
		if ($res === FALSE) {
				throw new Exception(curl_error($ch), curl_errno($ch));
				curl_close($ch);
		} else {
			/*
			 * Loop through the 'results' array.
			 * {
			 *     name: "host.conf"
			 *     type: "file"
			 * },
			 * {
			 *     ...
			 * },
			 */
			$jres = json_decode($res, true);
			$body = $jres['results'];
			$files = array();
			foreach ($body as $k => $v) {
				if ($v['type'] == "file") {
					array_push($files, $v['name']);
				}
			}
			return $files;
		}
	}

	// Main routine
	public function generateAction(){
		/* Initialise variables from web form only if defined properly. */
		$client_name = $_GET['client_name'];
		if strlen($client_name <= 1) {
			die("Unexpected client name length")
		}
		$client_address   = $_GET['client_address'];
		if strlen($client_address <= 1) {
			die("Unexpected client address length")
		}
		$parent_name = $_GET['parent_name'];
		if strlen($parent_name <= 1) {
			die("Unexpected parent name length");
		}
		$parent_address = $_GET['parent_address'];
		if strlen($parent_address <= 1) {
			die("Unexpected parent address length");
		}
		$parent_zone = $_GET['parent_zone'];
		if strlen($parent_zone <= 1) {
			die("Unexpected parent zone length");
		}

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
		if ($confstr < 0) {
			die("Error creating string from input parameters");
		}

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
				)
			);

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
