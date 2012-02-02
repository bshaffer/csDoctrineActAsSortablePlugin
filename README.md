csDoctrineActAsSortablePlugin
=============================

The `csDoctrineActAsSortablePlugin` is a symfony plugin that allows use of the doctrine behavior actAsSortable.

This behavior provides methods on your model for setting display order/position.

This plugin also contains images to implement for ordering.

Installation
------------

### With git

    git submodule add git://github.com/bshaffer/csDoctrineActAsSortablePlugin.git plugins/csDoctrineActAsSortablePlugin
    git submodule init
    git submodule update

### With subversion

    svn propedit svn:externals plugins

In the editor that's displayed, add the following entry and then save

    csDoctrineActAsSortablePlugin https://svn.github.com/bshaffer/csDoctrineActAsSortablePlugin.git

Finally, update:

    svn up

# Setup

In your `config/ProjectConfiguration.class.php` file, make sure you have
the plugin enabled.

    $this->enablePlugins('csDoctrineActAsSortablePlugin');

Apply the behavior to your model in your schema file `config/doctrine/schema.yml`

    MyModel:
      actAs: [Sortable]

Optionally accepts a UniqueBy attribute which will be used on a model with a one-to-many relationship
    
    MyModel:
      actAs:    
        Sortable:
          uniqueBy: [parent_id]

Rebuild your models and database
  
    ./symfony doctrine:build --all --and-load
    
Publish your assets

    ./symfony plugin:publish-assets

Clear your cache

    ./symfony cc


#Available Record Methods

  * **promote**

        $record->promote();
      
  * **demote**
  
        $record->demote();
      
  * **moveToFirst**
  
        $record->moveToFirst();
      
  * **moveToLast**
  
        $record->moveToLast();
      
  * **moveToPosition**
  
        $record->moveToPosition($newPosition);
        

#Available Table Methods

  * **sort** - accepts the array created by the symfony/prototype sortableElement tag

        Doctrine::getTable('MyModel')->sort($order);

  * **findAllSorted** - Accepts sort order (asc, desc)

        Doctrine::getTable('Model')->findAllSorted('asc');

  * **findAllSortedWithParent** - accepts the parent column name, the value, and sort order (asc, desc)

        Doctrine::getTable('MyModel')->findAllSortedWithParent($fk_value, $fk_name, 'asc');


#Example Usage With Admin Generator

In your module, edit `config/generator.yml`, and under list, object actions, add:

    object_actions:
      promote:
        action: promote
      demote:
        action: demote
      _edit:        -
      _delete:      -
          
In your module, edit `actions/actions.class.php`, Add the following actions:
  
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
    
Running Unit Tests
------------------

This plugin comes with a fully functional test suite.  If you'd like to run this against the symfony version
used in your project, export the `$symfony` environment variable as follows:

    export SYMFONY=/usr/share/php/symfony

The above is where PEAR installs symfony on an Ubuntu/Debian-system.  
**Be sure to point this at your symfony `lib` directory!**

By default, this will run against a `sqlite` database.  If you'd like to run against `postgres` or `mysql`, 
export the environmental `$DB` variable as follows:

    # Running against mysql:
    export DB=mysql; php ./test/bin/prove.php

    #Running against postgre:
    export DB=postgre; php ./test/bin/prove.php

*Database connection data*

 - mySQL uses root with no password per default
 - postgreSQL uses postgres with no password per default

Database-Configuration can be changed in `/test/fixtures/project/config/database-*.yml`