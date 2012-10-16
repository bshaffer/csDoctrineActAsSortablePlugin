<?php

class sfPluginTestBootstrap
{
  protected
    $symfonyDir     = null,
    $configuration  = null,
    $context        = null;
  /**
   *
   * @var sfDatabaseManager
   */
  protected
    $databasemanager = null;

  public function __construct($symfonyDir = null)
  {
    if ($symfonyDir)
    {
      $this->setSymfonyDir($symfonyDir);
    }

    $this->setSymfonyPluginsDir(dirname(__FILE__).'/../../..');
  }

  public function teardown()
  {
    sfToolkit::clearDirectory(dirname(__FILE__).'/../fixtures/project/cache');
    sfToolkit::clearDirectory(dirname(__FILE__).'/../fixtures/project/log');
  }

  public function setup()
  {
    $db = $this->databasemanager->getDatabase('doctrine');
    /* @var $db sfDoctrineDatabase */

    // Special Handling for postgre, since droping even when closing the connection, fails with
    // SQLSTATE[55006]: Object in use: 7 ERROR:  database "cs_doctrine_act_as_sortable_test" is being accessed by other users DETAIL:  There are 1 other session(s) using the database.
    if ($db->getDoctrineConnection() instanceof Doctrine_Connection_Pgsql) {
        try {
            $db->getDoctrineConnection()->createDatabase();
        } catch (Exception $e) {

        }
        $export = new Doctrine_Export_Pgsql($db->getDoctrineConnection());
        $import = new Doctrine_Import_Pgsql($db->getDoctrineConnection());
            $tablenames = array(
                SortableArticleTable::getInstance()->getTableName(),
                SortableArticleUniqueByTable::getInstance()->getTableName(),
                SortableArticleCategoryTable::getInstance()->getTableName()
            );

            foreach($tablenames as $tablename)
            {
                if ($import->tableExists($tablename)) {
                    $export->dropTable($tablename);
                }
            }
    } else {
        try {
            // ignore error if database does not yet exist (clean CI-env)
            $db->getDoctrineConnection()->dropDatabase();
        } catch (Exception $e) {
        }
        $db->getDoctrineConnection()->createDatabase();
    }

    // Using Doctrine instead of Doctrine_Core keeps it symfony 1.2 compatible, which uses
    Doctrine::loadModels(dirname(__FILE__).'/../fixtures/project/lib/model/doctrine', Doctrine::MODEL_LOADING_CONSERVATIVE);
    Doctrine::createTablesFromArray(Doctrine::getLoadedModels());
    Doctrine::loadData(dirname(__FILE__).'/../fixtures/project/data/fixtures/categories.yml');
  }

  // Find all tests and run them
  public function run()
  {
    $h = new lime_harness(array('verbose' => true, 'output' => new lime_output_color()));
    $h->register(sfFinder::type('file')->name('*Test.php')->in(dirname(__FILE__).'/..'));

    exit($h->run() ? 0 : 1);
  }

  public function autoload($class)
  {
    $autoload = sfSimpleAutoload::getInstance();
    $autoload->reload();
    return $autoload->autoload($class);
  }

  public function getConfiguration()
  {
    return $this->configuration;
  }

  public function getContext()
  {
    return $this->context;
  }

  public function setSymfonyDir($symfonyDir)
  {
    if ($symfonyDir)
    {
      $this->symfonyDir   = $symfonyDir;
      $_SERVER['SYMFONY'] = $symfonyDir;
      file_put_contents('/tmp/symfony_dir', $this->symfonyDir);
    }
  }

  public function setSymfonyPluginsDir($pluginsDir)
  {
    $this->pluginsDir               = $pluginsDir;
    $_SERVER['SYMFONY_PLUGINS_DIR'] = $pluginsDir;
  }

  public function getSymfonyDir()
  {
    if (!$this->symfonyDir)
    {
      // Get path to symfony
      if (isset($_SERVER['SYMFONY']))
      {
        $this->symfonyDir = $_SERVER['SYMFONY'];
      }
      elseif(file_exists('/tmp/symfony_dir'))
      {
        // Hack to allow the passing in of symfony_dir at runtime
        $this->symfonyDir = file_get_contents('/tmp/symfony_dir');
        $_SERVER['SYMFONY'] = $this->symfonyDir;
      }
      else
      {
        throw new Exception(sprintf("Symfony directory%s not found.  Please set \$_SERVER['SYMFONY'] or provide a --symfony_dir argument", isset($_SERVER['SYMFONY']) ? " '$_SERVER[SYMFONY]'" : ''));
      }
    }

    return $this->symfonyDir;
  }

  public function bootstrap($app = 'frontend', $debug = true)
  {
    // so that all notices will appear
    error_reporting(E_ALL);

    // Load symfony core and lime testing framework
    require_once $this->getSymfonyDir().'/autoload/sfCoreAutoload.class.php';
    sfCoreAutoload::register();

    // Create configuration and context
    require_once dirname(__FILE__).'/../fixtures/project/config/ProjectConfiguration.class.php';

    $dbms = 'sqlite';
    if (isset($_SERVER['DB']))
    {
        $dbms = strtolower($_SERVER['DB']);
    }
    if (!isset($_SERVER['DSN']))
    {
        // Check if configuration for dbms exists
        if (!file_exists(dirname(__FILE__).'/../fixtures/project/config/database-' . $dbms . '.yml'))
        {
            throw new Exception('Didnt find database-'.$dbms. 'yml for DBMS: "' . $dbms . '"');
        }

        copy(dirname(__FILE__).'/../fixtures/project/config/database-' . $dbms . '.yml',
            dirname(__FILE__).'/../fixtures/project/config/databases.yml');
    }
    else
    {
        $databaseConfiguration = <<<EOT
all:
  doctrine:
    class: sfDoctrineDatabase
    param:
      dsn: {$_SERVER['DSN']}
EOT;
        file_put_contents(
            dirname(__FILE__).'/../fixtures/project/config/databases.yml',
            $databaseConfiguration
        );
    }
    $this->configuration = ProjectConfiguration::getApplicationConfiguration($app, 'test', $debug);

    require_once $this->configuration->getSymfonyLibDir().'/vendor/lime/lime.php';
    $this->context = sfContext::createInstance($this->configuration);

    $this->databasemanager = new sfDatabaseManager($this->configuration);

    // Register teardown / autoload functions
    spl_autoload_register(array($this, 'autoload'));
    register_shutdown_function(array($this, 'teardown'));

    $this->teardown();
    $this->setup();
  }
}