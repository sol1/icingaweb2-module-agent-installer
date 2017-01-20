<?php

$section = $this->menuSection('Agent Installer', array(
    'url' => 'agentinstaller',
    'icon' => 'download'
));


$this->provideConfigTab('config', array(
    'title' => $this->translate('Configure this module'),
    'label' => $this->translate('Config'),
    'url' => 'config'
));

