<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Opsgenie\Forms\Config;

use Icinga\Forms\ConfigForm;

class GeneralConfigForm extends ConfigForm
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_opsgenie_general');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'opsgenie_duration',
            array(
                'value'         => '8',
                'label'         => $this->translate('Duration (hours)'),
                'description'   => $this->translate('On call duration assigned to user when activated.')
            )
        );
       $this->addElement(
            'text',
            'opsgenie_api_key',
            array(
                'value'         => 'XXXX-XXXX-XXXX-XXXX',
                'label'         => $this->translate('OpsGenie developer API key'),
                'description'   => $this->translate('API key used to update OpsGenie schedule information.')
            )
        );
	$this->addElement(
            'text',
            'opsgenie_email_domain',
            array(
                'value'         => 'example.com.au',
                'label'         => $this->translate('Email domain'),
                'description'   => $this->translate('Email domain name, this is combined with the logged in user\'s username.')
            )
        );
	$this->addElement(
            'text',
            'opsgenie_schedule_names',
            array(
                'value'         => 'Colo,HQ,Databases,Lunch',
                'label'         => $this->translate('Schedule names'),
                'description'   => $this->translate('A list of schedule names seperated by commas.')
            )
        );
    }
}

