<?php

use Icinga\Web\Controller;
use Icinga\Web\Session;

class AgentInstaller_DownloadController extends Controller {
    public function indexAction()
    {
	$this->_helper->layout->disableLayout();
	$this->_helper->viewRenderer->setNoRender(TRUE);

	$file_name = "/var/www/icingaclient/builds/" . $_GET['clientname'] . "_setup.exe";

	$size=filesize($file_name);

	header("Content-Length: $size");
	header('Content-type: application/exe');
	header('Content-Disposition: attachment; filename="' . $_GET['clientname'] . '_setup.exe"');

	readfile("$file_name");
    }

    public function testAction()
    {
	echo "Hello world!";

    }

}
