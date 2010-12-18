<?php

/**
 * Easily adds sorting functionality to a record.
 *
 * @package     csDoctrineSortablePlugin
 * @subpackage  template
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Travis Black <tblack@centresource.com>
 */
class Doctrine_Template_Sortable extends Doctrine_Template
{
  /**
   * Array of Sortable options
   *
   * @var string
   */
  protected $_options = array('name'        =>  'position',
                              'alias'       =>  null,
                              'type'        =>  'integer',
                              'length'      =>  8,
                              'unique'      =>  true,
                              'options'     =>  array(),
                              'fields'      =>  array(),
                              'uniqueBy'    =>  array(),
                              'uniqueIndex' =>  true,
                              'indexName'   =>  'sortable'
  );

  /**
   * __construct
   *
   * @param string $array
   * @return void
   */
  public function __construct(array $options = array())
  {
    $this->_options = Doctrine_Lib::arrayDeepMerge($this->_options, $options);
  }


  /**
   * Set table definition for sortable behavior
   * (borrowed and modified from Sluggable in Doctrine core)
   *
   * @return void
   */
  public function setTableDefinition()
  {
    $name = $this->_options['name'];

    if ($this->_options['alias'])
    {
      $name .= ' as ' . $this->_options['alias'];
    }

    $this->hasColumn($name, $this->_options['type'], $this->_options['length'], $this->_options['options']);

    if (!empty($this->_options['uniqueBy']) && !is_array($this->_options['uniqueBy'])) 
    {
      throw new sfException("Sortable option 'uniqueBy' must be an array");
    }
    
    if ($this->_options['uniqueIndex'] == true && ! empty($this->_options['uniqueBy']))
    {
      $indexFields = array($this->_options['name']);
      $indexFields = array_merge($indexFields, $this->_options['uniqueBy']);

      $this->index($this->getSortableIndexName(), array('fields' => $indexFields, 'type' => 'unique')); 

    }
    elseif ($this->_options['unique'])
    {
      $indexFields = array($this->_options['name']);
      $this->index($this->getSortableIndexName(), array('fields' => $indexFields, 'type' => 'unique')); 

    }

    $this->addListener(new Doctrine_Template_Listener_Sortable($this->_options));
  }
  
  /** 
  * Returns the name of the index to create for the position field. 
  * 
  * @return string 
  */ 
  protected function getSortableIndexName() 
  { 
    return sprintf('%s_%s_%s', $this->getTable()->getTableName(), $this->_options['name'], $this->_options['indexName']); 
  } 
  
  
  /**
   * Demotes a sortable object to a lower position
   *
   * @return void
   */
  public function demote()
  {
    $object = $this->getInvoker();
    $position = $object->get($this->_options['name']);

    if ($position < $object->getFinalPosition())
    {
      $object->moveToPosition($position + 1);
    }
  }


  /**
   * Promotes a sortable object to a higher position
   *
   * @return void
   */
  public function promote()
  {
    $object = $this->getInvoker();
    $position = $object->get($this->_options['name']);

    if ($position > 1)
    {
      $object->moveToPosition($position - 1);
    }
  }

  /**
   * Sets a sortable object to the first position
   *
   * @return void
   */
  public function moveToFirst()
  {
    $object = $this->getInvoker();
    $object->moveToPosition(1);
  }


  /**
   * Sets a sortable object to the last position
   *
   * @return void
   */
  public function moveToLast()
  {
    $object = $this->getInvoker();
    $object->moveToPosition($object->getFinalPosition());
  }


  /**
   * Moves a sortable object to a designate position
   *
   * @param int $newPosition
   * @return void
   */
  public function moveToPosition($newPosition)
  {
    if (!is_int($newPosition))
    {
      throw new Doctrine_Exception('moveToPosition requires an Integer as the new position. Entered ' . $newPosition);
    }

    $object = $this->getInvoker();
    $position = $object->get($this->_options['name']);
    $connection = $object->getTable()->getConnection();

    //begin Transaction
    $connection->beginTransaction();

    // Position is required to be unique. Blanks it out before it moves others up/down.
    $object->set($this->_options['name'], null);
		$object->save();


    if ($position > $newPosition)
    {
      $q = $object->getTable()->createQuery()
                              ->update(get_class($object))
                              ->set($this->_options['name'], $this->_options['name'] . ' + 1')
                              ->where($this->_options['name'] . ' < ?', $position)
                              ->andWhere($this->_options['name'] . ' >= ?', $newPosition)
                              ->orderBy($this->_options['name'] . ' DESC');

      foreach ($this->_options['uniqueBy'] as $field)
      {
        $q->addWhere($field . ' = ?', $object[$field]);
      }

      $q->execute();
    }
    elseif ($position < $newPosition)
    {

      $q = $object->getTable()->createQuery()
                              ->update(get_class($object))
                              ->set($this->_options['name'], $this->_options['name'] . ' - 1')
                              ->where($this->_options['name'] . ' > ?', $position)
                              ->andWhere($this->_options['name'] . ' <= ?', $newPosition)
                              ->orderBy($this->_options['name'] . ' ASC');

      foreach($this->_options['uniqueBy'] as $field)
      {
        $q->addWhere($field . ' = ?', $object[$field]);
      }

      $q->execute();
    }

    $object->set($this->_options['name'], $newPosition);
		$object->save();

    // Commit Transaction
    $connection->commit();
  }


  /**
   * Send an array from the sortable_element tag (symfony+prototype)and it will
   * update the sort order to match
   *
   * @param string $order
   * @return void
   * @author Travis Black
   */
  public function sortTableProxy($order)
  {
    /*
      TODO 
        - Add proper error messages.
    */
    $table = $this->getInvoker()->getTable();
    $class  = get_class($this->getInvoker());
    $connection = $table->getConnection();

    $connection->beginTransaction();

    foreach ($order as $position => $id)
    {
      $newObject = Doctrine::getTable($class)->findOneById($id);

      if ($newObject->get($this->_options['name']) != $position + 1)
      {
        $newObject->moveToPosition($position + 1);
      }
    }

    // Commit Transaction
    $connection->commit();
  }


  /**
   * Finds all sortable objects and sorts them based on position attribute
   * Ascending or Descending based on parameter
   *
   * @param string $order
   * @return $query
   */
  public function findAllSortedTableProxy($order = 'ASC')
  {
    $order = $this->formatAndCheckOrder($order);
    $object = $this->getInvoker();

    $query = $object->getTable()->createQuery()
                                ->orderBy($this->_options['name'] . ' ' . $order);

    return $query->execute();
  }


  /**
   * Finds and returns records sorted where the parent (fk) in a specified
   * one to many relationship has the value specified
   *
   * @param string $parentValue
   * @param string $parent_column_value
   * @param string $order
   * @return $query
   */
  public function findAllSortedWithParentTableProxy($parentValue, $parentColumnName = null, $order = 'ASC')
  {
    $order = $this->formatAndCheckOrder($order);

    $object = $this->getInvoker();
    $class  = get_class($object);

    if (!$parentColumnName)
    {
      $parents = get_class($object->getParent());

      if (count($parents) > 1)
      {
        throw new Doctrine_Exception('No parent column name specified and object has mutliple parents');
      }
      elseif (count($parents) < 1)
      {
        throw new Doctrine_Exception('No parent column name specified and object has no parents');
      }
      else
      {
        $parentColumnName = $parents[0]->getType();
        exit((string) $parentColumnName);
        exit(print_r($parents[0]->toArray()));
      }
    }

    $query = $object->getTable()->createQuery()
                                ->from($class . ' od')
                                ->where('od.' . $parentColumnName . ' = ?', $parentValue)
                                ->orderBy($this->_options['name'] . ' ' . $order);

    return $query->execute();
  }


  /**
   * Formats the ORDER for insertion in to query, else throws exception
   *
   * @param string $order
   * @return $order
   */
  public function formatAndCheckOrder($order)
  {
    $order = strtolower($order);

    if ('ascending' === $order || 'asc' === $order)
    {
      $order = 'ASC';
    }
    elseif ('descending' === $order || 'desc' === $order)
    {
      $order = 'DESC';
    }
    else
    {
      throw new Doctrine_Exception('Order parameter value must be "asc" or "desc"');
    }

    return $order;
  }


  /**
   * Get the final position of a model
   *
   * @return int $position
   */
  public function getFinalPosition()
  {
    $object = $this->getInvoker();

    $q = $object->getTable()->createQuery()
                            ->select($this->_options['name'])
                            ->orderBy($this->_options['name'] . ' desc');

   foreach($this->_options['uniqueBy'] as $field)
   {
     if(is_object($object[$field]))
     {
       $q->addWhere($field . ' = ?', $object[$field]['id']);
     }
     else
     {
       $q->addWhere($field . ' = ?', $object[$field]);
     }
   }

   $last = $q->limit(1)->fetchOne();
   $finalPosition = $last ? $last->get($this->_options['name']) : 0;

   return (int)$finalPosition;
  }
}