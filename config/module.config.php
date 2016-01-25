<?php
 return array(
     'application' => array(
        'email' => array(
            'allowedDevEmailDomains' => array(
                // use regex pattern
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
                 'branch' => 'master', // This is the git branch that you wish to auto deploy
                 'originUrl' => ''
             ),

             'dm' => array(
                 'type' => 'composer',
                 'name' => '' // This is required to identify the correct composer.json file
             ),

             'db' => array(
                 'type' => 'mysql',
                 'migrationDir' => '', // This is where the db migration files are kept relative to the vcs root
                 // This is where the backup taken prior to a db migration are kept relative to the vcs root
                 // make sure this directory is excluded from version control
                 'backupDir' => '',
                 // the below is required to perform db migrations - this is the target db
                 'connection' => array(
                     'hostname' => '',
                     'username' => '',
                     'password' => '',
                     'database' => ''
                 ),
                 // the below is required to perform db migrations - multiple databases can be backed up if required
                 'backup_connections' => array(/*[
                     'hostname' => '',
                     'username' => '',
                     'password' => '',
                     'database' => ''
                 ]*/),
             ),
         ),

         // This is a list of white-listed IP addresses for the modules internal firewall
         'ipAddresses' => array(
            // add ip addresses
         ),

         'log' => array(
             'enabled' => true,
             'logger' => '\Zend_Log', // default use the Zend Log
             'logDir' => 'var/log', // directory that the log file lives in
             'logFile' => 'application.log', // log file name
             'logTitle' => 'AutoDeploy', // log entry title
             'mail' => true,
             'mailerClass' => 'AutoDeploy\Application\SystemEmail', // default use the Zend Logger (must be implement AutoDeploy\Application\SystemEmailInterface)
         )
     ),

     'controllers' => array(
         'invokables' => array(
             'AutoDeploy\Controller\Zf2' => 'AutoDeploy\Controller\Zf2Controller'
         ),
     ),

     // The following section is new and should be added to your file
     'router' => array(
         'routes' => array(
            // The following is the only route we allow by default with this module
            'AutoDeploy' => array(
                'type'    => 'Segment',
                'options' => array(
                    'route' => '/auto-deploy[/]',
                    'defaults' => array(
                        '__NAMESPACE__' => 'AutoDeploy\Controller',
                        'controller' => 'Zf2',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
            ),
         ),
     ),
 );
