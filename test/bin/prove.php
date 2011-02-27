<?php

// handle --symfony_dir argument
$symfony = null;
foreach ($argv as $arg) 
{
  $params = explode('=', $arg);
  if (isset($params[1]) && $params[0] == '--symfony_dir') 
  {
    $symfony = $params[1];
    break;
  }
}

include dirname(__FILE__).'/../bootstrap/sfPluginTestBootstrap.class.php';

$bootstrap = new sfPluginTestBootstrap($symfony);
$bootstrap->bootstrap();
$bootstrap->run();