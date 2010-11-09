csDoctrineActAsSortablePlugin
=============================

The `csDoctrineActAsSortablePlugin` is a symfony plugin that allows use of the doctrine behavior actAsSortable.

This behavior provides methods on your model for setting display order/position.

This plugin also contains images to implement for ordering.

Installation
------------

  * Install the plugin

        $ symfony plugin:install csDoctrineActAsSortablePlugin

  * Apply the behavior to your model in your schema file `config/doctrine/schema.yml`, ie:

        [yml]
        model:
          actAs: [Sortable]
    Optionally accepts a UniqueBy attribute which will be used on a model with a one-to-many relationship
    
          model:
            actAs: [Sortable]
            uniqueBy: [parent_id]

  * Rebuild your models and database
  
        $ symfony doctrine:build-all-reload
    
    alternatively you could build the models, the sql, then run the sql manually
        
  * Publish your assets

        $ symfony plugin:publish-assets

  * Clear your cache

        $ symfony cc


Available Record Methods
------------------------

  * promote

        [php]
        $record->promote();
      
  * demote
  
        [php]
        $record->demote();
      
  * moveToFirst
  
        [php]
        $record->moveToFirst();
      
  * moveToLast
  
        [php]
        $record->moveToLast();
      
  * moveToPosition
  
        [php]
        $record->moveToPosition($newPosition);
        

Available Table Methods
------------------------

  * sort - accepts the array created by the symfony/prototype sortableElement tag

        [php]
        Doctrine::getTable('Model')->sort($order);

  * findAllSorted - Accepts sort order (asc, desc)

        [php]
        Doctrine::getTable('Model')->findAllSorted('ASCENDING');

  * findAllSortedWithParent - accepts the parent column name, the value, and sort order (asc, desc)

        [php]
        Doctrine::getTable('Model')->findAllSortedWithParent($fk_value, $fk_name, 'ASCENDING');


Example Usage With Admin Generator
----------------------------------

  * In your module, edit `config/generator.yml`, and under list, object actions, add:

        [yml]
        object_actions:
          promote:
            action: promote
          demote:
            action: demote
          _edit:        -
          _delete:      -
          
  * In your module, edit ``, Add the following actions:
  
        [php]
        public function executePromote()
        {
          $object=Doctrine::getTable('MyModel')->findOneById($this->getRequestParameter('id'));


          $object->promote();
          $this->redirect("@moduleIndexRoute");
        }

        public function executeDemote()
        {
          $object=Doctrine::getTable('MyModel')->findOneById($this->getRequestParameter('id'));

          $object->demote();
          $this->redirect("@moduleIndexRoute");
        }