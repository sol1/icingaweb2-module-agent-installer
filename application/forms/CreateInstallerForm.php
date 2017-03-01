<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Agentinstaller\Forms;

use Icinga\Web\Url;
use Icinga\Data\Filter\Filter;
use Icinga\Web\Form;
use Icinga\Application\Config;

class CreateInstallerForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setAction('/icingaweb2/agentinstaller/index/generate');
	$this->setMethod('get');
	
	//$conf = new Config();

	//$API_username = $conf->get('agentinstaller', 'apikey', 'no username');
        //$API_password = $conf->get('agentinstaller', 'apipassword', 'no password');
        //$API_address = $conf->get('agentinstaller', 'apiaddress', 'https://localhost:5665');

        //$url = "${API_address}/v1/objects/zones";

	//echo $API_username . "<br />" . $API_password . "<br />" . $API_address . "<br />" . $url;

        //try {
        //        $ch = curl_init($url);

        //        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //        curl_setopt($ch, CURLOPT_USERPWD, $API_username . ":" . $API_password);
        //        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //                'Content-Type:application/json',
        //                'Accept:application/json'
        //        ));


        //        $response = curl_exec($ch);
        //        if ($response === FALSE)
        //                throw new Exception(curl_error($ch), curl_errno($ch));
        //        curl_close($ch);
        //}catch(Exception $e) {
        //        trigger_error(sprintf(
        //                'Curl failed with error #%d: %s',
        //                $e->getCode(), $e->getMessage()),
        //                E_USER_ERROR);
        //}

        //echo $response;
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement('text', 'client-domain', array(
            'label'      => 'Client hostname:',
            'required'   => true,
            'validators' => array(
                'NotEmpty',
            )
        )); 

        $this->addElement('text', 'client-ip', array(
            'label'      => 'Client address:',
            'required'   => false
        )); 

        $this->addElement('text', 'parent-domain', array(
            'label'      => 'Parent hostname:',
            'required'   => true,
            'validators' => array(
                'NotEmpty',
            )
        )); 

	$this->addElement('text', 'parent-ip', array(
            'label'      => 'Parent address:',
            'required'   => false
        ));


	$this->addElement('text', 'zone-name', array(
            'label'      => 'Parent zone name:',
            'required'   => true,
            'validators' => array(
            )
        )); 
	
	//$this->addElement('select', 'zone', array(
	//   'label'	=> 'Zone Dropdown:',
	//   'required'	=> true,
	//   'validators'	=> array(),
	//   'multiOptions' => array(
	//	''  => 'select below',
	//	'yes' => 'yes',
	//	'no'  => 'no'
	//   )
	//));

        $this->addElement(
            'submit',
            'btn_submit',
            array(
                'class'         => 'button spinner',
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'control-group form-controls'))
                ),
                'escape'        => false,
                'ignore'        => true,
                'title'         => $this->translate('Generate installer'),
		'label'		=> 'Generate installer',
                'type'          => 'submit'
            )
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess()
    {
	echo "Working!";
        return true;
    }
}
