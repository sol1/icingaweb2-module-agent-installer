<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Agentinstaller\Forms;

use Icinga\Web\Url;
use Icinga\Data\Filter\Filter;
use Icinga\Web\Form;

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
    public function addSubmitButton()
    {
        return "hello";
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement('text', 'client-domain', array(
            'label'      => 'Client domain:',
            'required'   => true,
            'validators' => array(
                'NotEmpty',
            )
        )); 

	$this->addElement('text', 'parent-domain', array(
            'label'      => 'Parent domain:',
            'required'   => true,
            'validators' => array(
                'NotEmpty',
            )
        )); 

	$this->addElement('text', 'zone-name', array(
            'label'      => 'Zone name:',
            'required'   => false,
            'validators' => array(
            )
        )); 
	
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
