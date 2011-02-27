<?php

class testActions extends sfActions
{
  // shows a screen where different editable content tags are rendered
  public function executeBlog(sfWebRequest $request)
  {
    $this->blog = Doctrine_Core::getTable('Blog')->createQuery()->fetchOne();
  }
}
