<?php
 return [
     'application' => [
        'email' => [
            'allowedDevEmailDomains' => [
                // use regex pattern
            ],
            'fromName' => 'AutoDeploy',
            'fromEmail' => 'test@test.com',
            'replyTo' => 'test@test.com',
            'developerEmail' => 'test@test.com',
            'siteUrl' => '',
            'siteName' => '',
            'adminUrl' => '',
            'adminName' => ''
        ]
     ],

     'auto_deploy' => [
         // This is the git branch that you wish to auto deploy
         'vcs' => [
             'type' => 'git',
             'branch' => 'master',
         ],

         // This is a list of white-listed IP addresses for the modules internal firewall
         'ipAddresses' => [
            // add ip addresses
         ],

         'log' => [
             'enabled' => true,
             'logger' => 'Zend\Log\Logger', // default use the Zend Logger (must be implement Zend\Log\LogInterface)
             'logDir' => 'var/log', // directory that the log file lives in
             'logFile' => 'application.log', // log file name
             'logTitle' => 'AutoDeploy', // log entry title
             'mail' => true,
             'mailerClass' => 'AutoDeploy\Application\SystemEmail', // default use the Zend Logger (must be implement AutoDeploy\Application\SystemEmailInterface)
         ]
     ],

     'controllers' => [
         'invokables' => [
             'AutoDeploy\Controller\Index' => 'AutoDeploy\Controller\IndexController'
         ],
     ],

     // The following section is new and should be added to your file
     'router' => [
         'routes' => [
            // The following is the only route we allow by default with this module
            'AutoDeploy' => [
                'type'    => 'Segment',
                'options' => [
                    'route' => '/auto-deploy[/]',
                    'defaults' => [
                        '__NAMESPACE__' => 'AutoDeploy\Controller',
                        'controller' => 'Index',
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
            ],
         ],
     ],
 ];
