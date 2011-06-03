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
   * Array to store old values
   *
   * @var array
   */
  protected $_oldValues = array();

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
   * Set the position value automatically when a sortable object is updated
   *
   * @param Doctrine_Event $event
   * @return void
   */
  public function preUpdate(Doctrine_Event $event)
  {
      $fieldName = $this->_options['name'];
      $object = $event->getInvoker();
      $_modified = $object->getModified(true);
      $this->_oldValues[$fieldName] = $object->$fieldName;
      
      foreach ($this->_options['uniqueBy'] as $key)
      {
          if (array_key_exists($key, $object->getModified(true)))
          {
              $this->_oldValues[$key] = $_modified[$key];
          }
      }

      if (count($this->_oldValues) > 1)
      {
          $object->$fieldName = $object->getFinalPosition() + 1;
      }
  }

  /**
   * Set the position value automatically when a sortable object is updated
   *
   * @param Doctrine_Event $event
   * @return void
   */
  public function postUpdate(Doctrine_Event $event)
  {
      $fieldName = $this->_options['name'];
      $object = $event->getInvoker();
      $position = $this->_oldValues[$fieldName];

      if (count($this->_oldValues) > 1)
      {
          $q = $this->getBaseQueryOrder($object, $fieldName, $position);
          $q->orderBy($fieldName);

          foreach ($this->_options['uniqueBy'] as $field) {
              if (array_key_exists($field, $this->_oldValues))
              {
                  $q->addWhere($field . ' = ?', $this->_oldValues[$field]);
              }
              else
              {
                  $q->addWhere($field . ' = ?', $object[$field]);
              }
          }

          $q->execute();
      }
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

    // Create query to update other positions
    $q = $this->getBaseQueryOrder($object, $fieldName, $position);

    foreach ($this->_options['uniqueBy'] as $field)
    {
      $q->addWhere($field . ' = ?', $object[$field]);
    }

    if ($this->canUpdateWithOrderBy($conn))
    {
        $q->orderBy($fieldName)
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
      $conn->getDriverName() != 'Pgsql' && $conn->getDriverName() != 'Sqlite';
  }

  /**
   * Set the position value automatically when a sortable object is updated
   *
   * @param  Doctrine_Record $object
   * @param  string $fieldname
   * @param  integer $position
   * @return Doctrine_Query
   */
  private function getBaseQueryOrder($object, $fieldName, $position)
  {
      return $object->getTable()->createQuery()
                              ->update(get_class($object))
                              ->set($fieldName, $fieldName . ' - ?', '1')
                              ->where($fieldName . ' > ?', $position);
  }
}
