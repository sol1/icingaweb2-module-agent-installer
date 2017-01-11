<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Opsgenie\Forms\Config;

use Icinga\Forms\ConfigForm;

class GeneralConfigForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_opsgenie_start_schedule');
        $this->setSubmitLabel($this->translate('Clock on'));
    }

    /**
     * {@inheritdoc}
     */
    public function setup()
    {
	$this->addElement('submit', 'hey');
    }
}

