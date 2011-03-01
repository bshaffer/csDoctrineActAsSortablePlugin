<?php

include dirname(__FILE__).'/sfPluginTestBootstrap.class.php';
$bootstrap = new sfPluginTestBootstrap();
$bootstrap->bootstrap();

$configuration = $bootstrap->getConfiguration();
$context = $bootstrap->getContext();

function doctrine_refresh()
{
  $args = func_get_args();
  foreach ($args as $arg) 
  {
    $arg->refresh();
  }
}