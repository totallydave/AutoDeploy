# What is AutoDeploy?
AutoDeploy is a *Zend Framework 2*-Module which provides a service to auto deploy code utilising web hooks from VCS providers.

# Installation

Add the below to your index.php or web root php file
```
define('APPLICATION_ROOT', realpath(dirname(__DIR__)));
```

Create a auto_deploy.php and place in config/autoload by running the below
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
      // This is the git branch that you wish to auto deploy
      'vcs' => [
         'originUrl' => '[GIT REPOSITORY URL]'
      ],

      // This is a list of white-listed IP addresses for the modules internal firewall
     'ipAddresses' => [
          [GIT SERVER PROVIDER IP]
      ],
  ]

 ...
```
