<?php


/**
 * Easily sort each record based on position
 *
 * @package     csDoctrineSortablePlugin
 * @subpackage  listener
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Travis Black <tblack@centresource.com>
 */
class Doctrine_Template_Listener_Sortable extends Doctrine_Record_Listener
{
  /**
   * Array of sortable options
   *
   * @var array
   */
  protected $_options = array();


  /**
   * __construct
   *
   * @param array $options
   * @return void
   */
  public function __construct(array $options)
  {
    $this->_options = $options;
  }


  /**
   * Set the position value automatically when a new sortable object is created
   *
   * @param Doctrine_Event $event
   * @return void
   */
  public function preInsert(Doctrine_Event $event)
  {
    $fieldName = $this->_options['name'];
    $object = $event->getInvoker();
    $object->$fieldName = $object->getFinalPosition()+1;
  }

  /**
   * When a sortable object is updated, check to see if any of the uniqueBy
   * fields are modified before saving to prevent two items having the same
   * position.
   *
   * @param string $Doctrine_Event
   * @return void
   */
  public function preUpdate(Doctrine_Event $event) {
    $fieldName = $this->_options['name'];
    $object = $event->getInvoker();
    $modified = $object->getModified();

    //-- Check to see if any of the uniqueBy fields have been modified
    foreach ($this->_options['uniqueBy'] as $field)
    {
      if ( array_key_exists($field, $modified) ) {
        //-- Move it to the end
        $object->$fieldName = $object->getFinalPosition()+1;
        break;
      }
    }
  }

  /**
   * When a sortable object is deleted, refresh its position BEFORE it is deleted, to
   * have the right position in postDelete
   *
   * @param Doctrine_Event $event
   * @return void
   */
  public function preDelete(Doctrine_Event $event)
  {
    $object = $event->getInvoker();
    $this->refreshPosition($object);
  }

  /**
   * Refreshs the position of the object
   *
   * @param Doctrine_Record $object
   */
  private function refreshPosition(Doctrine_Record $object)
  {
      $fieldName = $this->_options['name'];
      $identifiers = $object->getTable()->getIdentifierColumnNames();

      $query = $object->getTable()->createQuery()->select($fieldName);

      foreach($identifiers as $identifier)
      {
          $query->andWhere($identifier . ' = ?', $object->get($identifier));
      }

      $position = $query->fetchOne(array(), Doctrine::HYDRATE_ARRAY);
      $object->set($fieldName, $position[$fieldName], false);
  }

  /**
   * When a sortable object is deleted, promote all objects positioned lower than itself
   *
   * @param string $Doctrine_Event
   * @return void
   */
  public function postDelete(Doctrine_Event $event)
  {
    $fieldName = $this->_options['name'];
    $object    = $event->getInvoker();
    $position  = $object->$fieldName;
    $conn      = $object->getTable()->getConnection();

    // Quick fix forSoftDelete behavior
    if ($object->getTable()->hasTemplate('SoftDelete'))
    { 
        $object->setPosition(null); 
        $object->save(); 
    } 

    // Create query to update other positions
    $q = $object->getTable()->createQuery()
                            ->where($fieldName . ' > ?', $position)
                            ->orderBy($fieldName);

    foreach ($this->_options['uniqueBy'] as $field)
    {
      if(is_null($object[$field]))
      {
        $q->addWhere($field . ' IS NULL');
      }
      else
      {
        $q->addWhere($field . ' = ?', $object[$field]);
      }
    }

    if ($this->canUpdateWithOrderBy($conn))
    {
      $q->update(get_class($object))
        ->set($fieldName, $fieldName . ' - ?', '1')
        ->execute();
    }
    else
    {
      foreach ( $q->execute() as $item )
      {
        $pos = $item->get($this->_options['name'] );
        $item->set($this->_options['name'], $pos-1)->save();
      }
    }
  }

  // some drivers do not support UPDATE with ORDER BY
  protected function canUpdateWithOrderBy(Doctrine_Connection $conn)
  {
    // If transaction level is greater than 1,
    // query will throw exceptions when using this function
    return $conn->getTransactionLevel() < 2 &&
      // some drivers do not support UPDATE with ORDER BY query syntax
      $conn->getDriverName() != 'Pgsql' && $conn->getDriverName() != 'Sqlite' && $conn->getDriverName() != 'Mssql';
  }
}
