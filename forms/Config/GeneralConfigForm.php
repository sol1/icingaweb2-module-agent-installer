<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\AgentInstaller\Forms\Config;

use Icinga\Forms\ConfigForm;

class GeneralConfigForm extends ConfigForm
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_agentinstaller_general');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
	 $this->addElement(
            'text',
            'agentinstaller_apikey',
            array(
                'value'         => 'username',
                'label'         => $this->translate('API Username'),
                'description'   => $this->translate('API Username used to add custom configurations.')
            )
        );
       $this->addElement(
            'password',
            'agentinstaller_apipassword',
            array(
                'value'         => 'password',
                'label'         => $this->translate('API Password'),
                'description'   => $this->translate('API Password used to authenticate custom configurations')
            )
        );
	$this->addElement(
            'text',
            'agentinstaller_apiaddress',
            array(
                'value'         => 'https://localhost:5665',
                'label'         => $this->translate('API host domain'),
                'description'   => $this->translate('Address the API is host on, default should be fine.')
            )
        );
    }
}

