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
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement('text', 'client_name', array(
			'label'      => 'Client FQDN:',
			'required'   => true,
			'validators' => array(
			'NotEmpty',
            )
        )); 

        $this->addElement('text', 'client_address', array(
            'label'      => 'Client IP address:',
            'required'   => false
        )); 

        $this->addElement('text', 'parent_name', array(
            'label'      => 'Parent FQDN:',
            'required'   => true,
            'validators' => array(
                'NotEmpty',
            )
        )); 

	$this->addElement('text', 'parent_address', array(
            'label'      => 'Parent IP address:',
            'required'   => false
        ));


	$this->addElement('text', 'parent_zone', array(
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
				'label'         => 'Generate installer',
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
