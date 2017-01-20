<?php

use Icinga\Web\Controller;
use Icinga\Web\Session;

class AgentInstaller_DownloadController extends Controller {
    public function indexAction()
    {
	$file_name = "/var/www/icingaclient/_builds/" . $_GET['clientname'] . "_setup.exe";

	$size=filesize($file_name);
	header("Content-Length: $size");
	header('Content-type: application/exe');
	header('Content-Disposition: attachment; filename="' . $_GET['clientname'] . '_setup.exe"');

	header("Location: $file_name");
    }
}
