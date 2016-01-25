# What is AutoDeploy?
AutoDeploy is framework agnostic auto deployment application with added support as a *ZF2*-Module
which provides a service to auto deploy code utilising web hooks from VCS providers.

AutoDeploy is still in development

##Available services
- Vcs (Git)
- Dm - Dependency Manager (Composer)
- Db - Database Migrations (Executing MySql migration file/s)

###Notes
- Dm and Db services depend on a Vcs service
- Db service will be run last
- Db migration files must begin with '\_auto_deploy\_' this allows them to be ommitted from auto deployment
- An error on the Db service will roll the other services back so it's important Vcs is run first (@todo force this)
- An email log will be sent to any commiters in the update
- If logging is enables a log will be written to the file system

## Installation

```
composer require totallydave/auto-deploy dev-master
```

Add the below to the top of your index.php or web root php file
```
define('APPLICATION_ROOT', realpath(dirname(__DIR__)));
```

### ZF2 CONFIGURATION

Create an auto_deploy.php and place in config/autoload by running the below
```
cp vendor/totallydave/auto-deploy/config/module.config.php config/autoload/auto_deploy.php
```

Update the default configuration as you need (below is the minimum you'll need to get up and running)
```
 ...

 'application' => [
     'email' => [
         'allowedDevEmailDomains' => [
             [YOUR EMAIL DOMAIN] // use regex pattern e.g. 'github\.com' for foo@github.com
         ],
     ]
 ],

  ...

 'auto_deploy' => [
     'services' => [
         'vcs' => [
             'originUrl' => '[GIT REPOSITORY URL]' // This is the git branch that you wish to auto deploy
         ],

         ...

         'dm' => [
             'name' => '[COMPOSER PACKAGE NAME]' // This is required to identify the correct composer.json file
         ],

        ...

         'db' => [
             'type' => 'mysql',

              // This is where the db migration files are kept relative to the vcs root
              // migration files will only be applied if they start with '_auto_deploy_' - this is
              // to allow you the flexibility to pick and choose when auto db migration is applied
              // as it is not advised to use this feature for any heavy lifting
             'migrationDir' => '[MIGRATION FILE DIRECTORY]',

             // This is where the backup taken prior to a db migration are kept relative to the vcs root
             // make sure this directory is excluded from version control
             'backupDir' => '[MIGRATION BACKUP DIRECTORY]',

             // the below is required to perform db migrations - this is the target db
             'connection' => [
                 'hostname' => '[DB HOST]',
                 'username' => '[DB USERNAME]',
                 'password' => '[DB PASSWORD]',
                 'database' => '[DB NAME]'
             ],
             // the below is required to perform db migrations - multiple databases can be backed up if required
             'backup_connections' => [[
                 'hostname' => '[DB HOST]',
                 'username' => '[DB USERNAME]',
                 'password' => '[DB PASSWORD]',
                 'database' => '[DB NAME]'
             ]],
         ],

         ...
     ],

      // This is a list of white-listed IP addresses for the modules internal firewall
     'ipAddresses' => [
          [GIT SERVER PROVIDER IP]
     ],
  ]

 ...
```

Add 'AutoDeploy' to your registered modules in application.config.php
```
return array(
    'modules' => array(
        // other modules
        ...

        'AutoDeploy'

        ...
    ),
    // other content
);

```

### GENERAL CONFIGURATION (NOT ZF2)
Create an auto_deploy.php somewhere outside your web root in the following example
I will be using 'config/auto_deploy.php' relative to the project root
```
cp vendor/totallydave/auto-deploy/config/generic.config.php config/auto_deploy.php
```

Replace the values in [] below - i.e. [GIT BRANCH]
```
<?php
 return array(
     'application' => array(
        'email' => array(
            'allowedDevEmailDomains' => array(
                [YOUR EMAIL DOMAIN] // use regex pattern e.g. 'github\.com' for foo@github.com
            ),
            'fromName' => 'AutoDeploy',
            'fromEmail' => 'test@test.com',
            'replyTo' => 'test@test.com',
            'developerEmail' => 'test@test.com',
            'siteUrl' => '',
            'siteName' => '',
            'adminUrl' => '',
            'adminName' => ''
        )
    ),

     'auto_deploy' => array(
         /**
          * @todo allow for multiple of each service type
          */
         'services' => array(
             'vcs' => array(
                 'type' => 'git',
                 'branch' => '[GIT BRANCH]', // This is the git branch that you wish to auto deploy
                 'originUrl' => '[GIT REPOSITORY URL]'
             ),

             'dm' => array(
                 'type' => 'composer',
                 'name' => '[COMPOSER PACKAGE NAME]' // This is required to identify the correct composer.json file
             ),

             'db' => array(
                 'type' => 'mysql',

                 'migrationDir' => '[MIGRATION FILE DIRECTORY]',

                 // This is where the backup taken prior to a db migration are kept relative to the vcs root
                 // make sure this directory is excluded from version control
                 'backupDir' => '[MIGRATION BACKUP DIRECTORY]',

                 // the below is required to perform db migrations - this is the target db
                 'connection' => array(
                      'hostname' => '[DB HOST]',
                      'username' => '[DB USERNAME]',
                      'password' => '[DB PASSWORD]',
                      'database' => '[DB NAME]'
                  ),
                  // the below is required to perform db migrations - multiple databases can be backed up if required
                  'backup_connections' => array(array(
                      'hostname' => '[DB HOST]',
                      'username' => '[DB USERNAME]',
                      'password' => '[DB PASSWORD]',
                      'database' => '[DB NAME]'
                  )),
             ),
         ),

         // This is a list of white-listed IP addresses for the modules internal firewall
         'ipAddresses' => array(
             [GIT SERVER PROVIDER IP]
         ),

         'log' => array(
             'enabled' => true,
             'logger' => 'Zend_Log', // default use the Zend Log
             'logDir' => 'var/log', // directory that the log file lives in
             'logFile' => 'application.log', // log file name
             'logTitle' => 'AutoDeploy', // log entry title
             'mail' => true,
             'mailerClass' => 'AutoDeploy\Application\SystemEmail', // default use the Zend Logger (must be implement AutoDeploy\Application\SystemEmailInterface)
         )
     ),
 );
```

Create and exclude log directory 'var/log' from vcs

Place the below at the top of your web root php file after 'APPLICATION_ROOT' definition
so any requests for '/auto-deploy/' will be handled by AutoDeploy before hitting you application

Ensure the '../vendor/autoload.php' and 'config/auto_deploy.php' path is correct to your application structure.

```

// auto load composer packages - assuming this is the path to your vendor dir
if (file_exists('../vendor/autoload.php')) {
	require_once '../vendor/autoload.php';
}

if ($_SERVER['REQUEST_URI'] === '/auto-deploy/') {
    // Run the auto deploy application!
    $autoDeploy = new \AutoDeploy\AutoDeploy(require 'config/auto_deploy.php');
    $autoDeploy->run();
    exit;
}
```

### GENERAL INSTALLATION CONTINUED (INCLUDING ZF2)

Configure web hook in your chosen vcs provider to call [YOUR APPLICATION URL]/auto-deploy/ on push event

## Prerequisites
- composer must be available globally by using command 'composer' to user composer service
- mysql-server must be installed to use mysql db service
- git must be installed to user git vcs service

## GOTCHAS
- auto_deploy.ipAddress : IP of VCS server must be added in config [GIT SERVER PROVIDER IP]
- application.email : correctly configure this or you might find the emails end up in your spam
- application.email.allowedDevEmailDomains : this relies on php environment variable 'env'
- auto_deploy.services.db : create and exclude backupDir from vcs
- auto_deploy.services.db : migration depends on vcs
- auto_deploy.services.db : migration files must start with '\_auto_deploy\_'
- auto_deploy.services.db : sql update files should contain the database that is being updated
- auto_deploy.log : create and exclude log dir from vcs

## @todo
- add rollback for other failures
- write rollback for db
- allow multiple services within each type
- write unit tests
