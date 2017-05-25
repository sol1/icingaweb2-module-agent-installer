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
        $this->addElement(
	    'text',
	    'client_name',
	     array(
	        'label'       => 'Client CN:',
	        'required'    => true,
	        'validators'  => array('NotEmpty',),
		'description' => $this->translate(
	            'The common name. The client FQDN '.
	            'is recommended e.g. db1.sydney.example.com'
	        )
	    )
	); 

        $this->addElement('text', 'client_address',
	    array(
		'label'       => 'Client IP address:',
		'required'    => false,
		'description' => $this->translate(
		    'An address from which the parent ' .
	            'can ping the client.'
	        )
	    )
	);

        $this->addElement('text', 'parent_name',
	    array(
		'label'       => 'Parent CN:',
	        'required'    => true,
		'validators'  => array('NotEmpty'),
		'description' => $this->translate(
		    "The parent's Endpoint name. Usually this ".
		    "is the parent's FQDN e.g. ".
		    "icinga.sydney.example.com"
		)
	    )
	);

	$this->addElement('text', 'parent_address',
	    array(
		'label'       => 'Parent IP address:',
		'required'    => false,
		'description' => $this->translate(
		    "The address the client will use to ".
		    "contact the parent"
		)
	    )
	);

	$this->addElement('text', 'parent_zone',
	    array(
		'label'      => 'Parent zone name:',
		'required'   => true,
		'description' => $this->translate(
		    "The Zone to which the parent belongs. ".
		    "A Zone name may be as short as 'sydney'. ".
		    "Note this may NOT be the same as ".
		    "the parent Endpoint name."
		)
	    )
	); 
	
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
