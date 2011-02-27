<?php

include dirname(__FILE__).'/sfPluginTestBootstrap.class.php';
$bootstrap = new sfPluginTestBootstrap();
$bootstrap->bootstrap();

$configuration = $bootstrap->getConfiguration();
$context = $bootstrap->getContext();