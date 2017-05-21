
<?php

use Icinga\Web\Controller;
use Icinga\Web\Session;

class AgentInstaller_DownloadController extends Controller {
    public function indexAction()
    {
	$outdir = "/var/icingaclient";
	$outfile = "icingaclient_" . $_GET['clientname'] . ".exe";
	$file_name = $outdir . "/" . $outfile;

	$this->_helper->layout->disableLayout();
	$this->_helper->viewRenderer->setNoRender(TRUE);

	$size=filesize($file_name);

	header("Content-Length: $size");
	header('Content-type: application/exe');
	header('Content-Disposition: attachment; filename="' . $outfile . '"');

	readfile("$file_name");
    }
}
