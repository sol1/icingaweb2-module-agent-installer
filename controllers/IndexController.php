<?php

use Icinga\Web\Controller;
use Icinga\Web\Session;
use Icinga\Forms\AgentInstaller;
use Icinga\Module\Agentinstaller\Forms\CreateInstallerForm;

class AgentInstaller_IndexController extends Controller {
	public function indexAction() {
        $form = $this->view->form = new CreateInstallerForm;
    }

	/*
	 * Generate valid Icinga2 configuration for an icinga2 client from the bare
	 * essentials.
	 */
	protected function formstr(string $cname, string $caddr, string $pzone) {
		/* Icinga2 cluster object definitions. */
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

		$confstr = $s.$t;
		syslog(LOG_DEBUG, "String $confstr generated from web form");
		return $confstr;
	}

	/*
	 * newpackage creates a new configuration package via the Icinga2
	 * API. It takes the name of a package as an argument and submits a
	 * request to the HTTP API. newpackage returns true or false upon a
	 * successful or failed request respectively.
	 */
	protected function newpackage(string $package) {
		$API_username = $this->Config()->get(
		    'agentinstaller', 'apikey', '');
		$API_password = $this->Config()->get(
		    'agentinstaller', 'apipassword', '');
		$API_url = $this->Config()->get(
		    'agentinstaller', 'apiaddress', '');

		$API_url .= "/v1/config/packages/$package";

		$ch = curl_init($API_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_USERPWD,
		    $API_username . ":" . $API_password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Content-Type:application/json',
		    'Accept:application/json')
		);

		$res = curl_exec($ch);
		if ($res === FALSE) {
			throw new Exception(curl_error($ch), curl_errno($ch));
			$status = -1;
			error_log("API query to $API_url failed");
			curl_close($ch);
			return $status;
		} else {
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($status >= 400) {
				die("Icinga API returned status $status while
				    creating $package");
			} else {
				if ($json = json_decode($res)) {
					if ($json->results &&
					    $json->results[0] &&
					    $json->results[0]->code &&
					    (int)$json->results[0]->code === 200) {
						return true;
					} else {
						die("Unexpected response from API");
					}
				} else {
					die("Error decoding API response");
				}
			}
		}
	}

	/*
	 * lspkg returns an array of names of current configuration packages. On
	 * failure false is returned.
	 */
	protected function lspkg() {
		$API_username = $this->Config()->get(
		    'agentinstaller', 'apikey', '');
		$API_password = $this->Config()->get(
		    'agentinstaller', 'apipassword', '');
		$API_url = $this->Config()->get(
		    'agentinstaller', 'apiaddress', '');

		$API_url .= "/v1/config/packages";

		$ch = curl_init($API_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERPWD,
		    $API_username . ":" . $API_password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Content-Type:application/json',
		    'Accept:application/json')
		);

		$res = curl_exec($ch);
		if ($res === FALSE) {
			throw new Exception(curl_error($ch), curl_errno($ch));
			$status = -1;
			error_log("API query to $API_url failed");
			curl_close($ch);
			return $status;
		} else {
			if ($json = json_decode($res)) {
				$pkgs = array();
				for ($i = 0; $i < count($json->results); $i++) {
					array_push($pkgs, $json->results[$i]->name);
				}
				return $pkgs;
			} else {
				error_log("Error decoding API response from call
				    to $API_url");
				return false;
			}
		}
	}


	/* Find the current 'active-stage' name of configuration packages. */
	protected function activestage(string $package) {
		$API_username = $this->Config()->get('agentinstaller',
		    'apikey', '');
		$API_password = $this->Config()->get('agentinstaller',
		    'apipassword', '');
		$API_url      = $this->Config()->get('agentinstaller',
		    'apiaddress', '');

		$API_url .= "/v1/config/packages/$package";

		$ch = curl_init($API_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERPWD, $API_username . ":" . $API_password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Content-Type:application/json',
		    'Accept:application/json'));

		$status = 0;
		$res = curl_exec($ch);
		if ($res === FALSE) {
			throw new Exception(curl_error($ch), curl_errno($ch));
			$status = -1;
			error_log("API query to $API_url failed");
			curl_close($ch);
			return $status;
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

	/*
	 * lsstage queries the Icinga2 HTTP API to generate a list of the files
	 * present in the given package and stage. The list is returned as an
	 * array of filename strings, or false on error.
	 */
	protected function lsstage(string $package, string $stage) {
		$API_username = $this->Config()->get('agentinstaller',
		    'apikey', '');
		$API_password = $this->Config()->get('agentinstaller',
		    'apipassword', '');
		$API_url      = $this->Config()->get('agentinstaller',
		    'apiaddress', 'https://localhost:5665');

		$API_url .= "/v1/config/stages/$package/$stage";
		$ch = curl_init($API_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERPWD, $API_username . ":" . $API_password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER,
		    array(
			'Content-Type:application/json',
			'Accept:application/json'
		    )
		);

		$status = 0;
		$res = curl_exec($ch);
		if ($res === FALSE) {
			$status = -1;
			throw new Exception(curl_error($ch), curl_errno($ch));
			error_log("Failed API query to $API_url");
			curl_close($ch);
			return $status;
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
			$body = $jres->results;
			$files = array();
			foreach ($body as $k => $v) {
				if ($v['type'] == "file") {
					array_push($files, $v['name']);
				}
			}
			return $files;
		}
	}

	/* Read contents of given file from the active stage.  */
	protected function catconf (string $f) {
		$API_username = $this->Config()->get('agentinstaller',
		    'apikey', '');
		$API_password = $this->Config()->get('agentinstaller',
		    'apipassword', '');
		$API_url      = $this->Config()->get('agentinstaller',
		    'apiaddress', '');

		$ch = curl_init($API_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERPWD, $API_username . ":" . $API_password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$status = 0;
		$res = curl_exec($ch);
		syslog(LOG_INFO, "Queried Icinga API at $API_url");
		if ($res === FALSE) {
			throw new Exception(curl_error($ch), curl_errno($ch));
			curl_close($ch);
			error_log("Failed to read file $f from request to $API_url");
			$status = -1;
			return $status;
		} else {
			return($res);
		}
	}

	protected function postpkg ($jsonpkg, string $pkg) {
		/* Send new configuration package to Icinga */
		$API_username = $this->Config()->get('agentinstaller',
		    'apikey', '');
		$API_password = $this->Config()->get('agentinstaller',
		    'apipassword', '');
		$API_url      = $this->Config()->get('agentinstaller',
		    'apiaddress', '');

		$API_url .= "/v1/config/stages/$pkg";
		try {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_USERPWD, $API_username . ":" . $API_password);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonpkg);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'Content-Type:application/json',
			    'Accept:application/json'));

			$resp = curl_exec($ch);
			if ($resp === FALSE) {
				throw new Exception(curl_error($ch), curl_errno($ch));
				curl_close($ch);
				return false;
			}
		} catch(Exception $e) {
			trigger_error(sprintf('curl error: %d: %s',
			    $e->getCode(), $e->getMessage()), E_USER_ERROR);
		}
		return true;
	}

	protected function getticket (string $cn) {
		$API_username = $this->Config()->get('agentinstaller',
		    'apikey', '');
		$API_password = $this->Config()->get('agentinstaller',
		    'apipassword', '');
		$API_url      = $this->Config()->get('agentinstaller',
		    'apiaddress', '');

		$API_url .= "/v1/actions/generate-ticket";

		$req = array("cn" => $cn);
		$body = json_encode($req);
		try {
			$ch = curl_init($API_url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_USERPWD, $API_username . ":" . $API_password);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'Content-Type:application/json',
			    'Accept:application/json'));

			$resp = curl_exec($ch);
			if ($resp === FALSE) {
				throw new Exception(curl_error($ch), curl_errno($ch));
				curl_close($ch);
				return false;
			}
		} catch(Exception $e) {
			trigger_error(sprintf('curl error: %d: %s',
			    $e->getCode(), $e->getMessage()), E_USER_ERROR);
		}

		$decode = json_decode($resp);
		(string) $ticket;
		if ($ticket = $decode->results[0]->ticket) {
			return $ticket;
		} else {
			error_log("No ticket in response to call $API_url");
			return false;
		}
	}

	/*
	 * For now, the custom icingaclient(1) program generates all config
	 * and an exe. Functions of icingaclient(1) should be transitioned to
	 * PHP functions.
	 */
	private function buildexe($cname, $caddr, $pname, $paddr, $pzone) {
		shell_exec("sudo icingaclient ".
		    "$cname $caddr $pname $paddr $pzone");
		return true;

	}

	private function realpath() {
		/* Initialise variables from web form only if defined properly. */
		$client_name = $_GET['client_name'];
		if (strlen($client_name) <= 1) {
			die("Unexpected client name length");
		}
		$client_address = $_GET['client_address'];
		if (strlen($client_address) <= 1) {
			die("Unexpected client address length");
		}
		$parent_name = $_GET['parent_name'];
		if (strlen($parent_name) <= 1) {
			die("Unexpected parent name length");
		}
		$parent_address = $_GET['parent_address'];
		if (strlen($parent_address) <= 1) {
			die("Unexpected parent address length");
		}
		$parent_zone = $_GET['parent_zone'];
		if (strlen($parent_zone) <= 1) {
			die("Unexpected parent zone length");
		}
		$package = "agentinstaller.".$client_name;

		/*
		 * Continue only if the specified client has not been configured
		 * previously; i.e. if a matching config package exists already.
		 */
		if (($pkgs = $this->lspkg()) === FALSE) {
		    die("Error while retrieving list of configuration packages");
		}
		for ($i = 0; $i < count($pkgs); $i++) {
			if (strcmp($package, $pkgs[$i]) == 0) {
				die("Client $client_name has already been
				    configured");
			} else {
			    continue;
			}
		}

		if ($this->newpackage($package) != true) {
			die("Failed to create configuration for $client_name");
		}

		/*
		 * Generate new file name and its contents from user's form
		 * input. Feed into the required data structure by Icinga API.
		 */
		$f = sprintf("zones.d/%s/%s.conf", $parent_zone, $client_name);
		$confstr = $this->formstr($client_name, $client_address, $parent_zone);

		$conf = array($f => $confstr);
		$files = array("files" => $conf);
		$body = json_encode($files);

		if (($this->postpkg($body, $package)) != TRUE) {
			die("Error uploading new client package");
		}

		/* Verify package was created, with expected contents. */
		$valid = 0;
		if (($this->activestage($package)) < 0) {
			$valid = -1;
			error_log("Error querying properties of package $package");
		}
		if ($valid < 0) {
			die("Error validating new package $package");
		}
	}
	/* Main routine. */
	public function generateAction() {
		$this->indexAction();
		(string) $devclient = "devclient";

		/* Initialise variables from web form only if defined properly. */
		$client_name = $_GET['client_name'];
		if (strlen($client_name) <= 1) {
			die("Unexpected client name length");
		}
		$client_address = $_GET['client_address'];
		if (strlen($client_address) <= 1) {
			die("Unexpected client address length");
		}
		$parent_name = $_GET['parent_name'];
		if (strlen($parent_name) <= 1) {
			die("Unexpected parent name length");
		}
		$parent_address = $_GET['parent_address'];
		if (strlen($parent_address) <= 1) {
			die("Unexpected parent address length");
		}
		$parent_zone = $_GET['parent_zone'];
		if (strlen($parent_zone) <= 1) {
			die("Unexpected parent zone length");
		}


		/*
		 * 'Hidden' pathway. If a special client name is specified,
		 * perform the development routine. Otherwise, use our script
		 * hack.
		 */
		if ($client_name == $devclient) {
		    $this->realpath();
		} else {
			$this->buildexe($client_name, $client_address, $parent_name,
			    $parent_address, $parent_zone);
		}

		//Download link, necessary due to everthing being an XHR request
		echo "<a href='../download?clientname=${client_name}' target='_blank'>Download installer</a>";
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
}
?>
