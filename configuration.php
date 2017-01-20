<?php

$section = $this->menuSection('Opsgenie', array(
    'url' => 'opsgenie',
    'icon' => 'clock'
));


$this->provideConfigTab('config', array(
    'title' => $this->translate('Configure this module'),
    'label' => $this->translate('Config'),
    'url' => 'config'
));

