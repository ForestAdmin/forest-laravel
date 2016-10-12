About
=====

The ForestBundle allows you to use the ForestAdmin application to manage your database entities. 
If you don't know what ForestAdmin is, you can [follow this link](http://www.forestadmin.com)

Installation
============

Step 1: Install the Package
---------------------------

Open a command console, enter your project directory and execute the
following commands to download the latest stable version of this package:

```bash
$ composer require forestadmin/forest-php dev-master
$ composer require forestadmin/forest-laravel dev-master
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md) of the Composer documentation.

Next, add the service provider to `config/app.php`

```
ForestAdmin\ForestLaravel\ForestServiceProvider::class,
```

Step 2: Allow CORS Queries from ForestAdmin
-------------------------------------------

To allow Forest to communicate successfully with your application, you will
need to authorize it to do Cross-Origin Resource Sharing (CORS) Queries.
If you do not know how it works, follow these instructions:

First, install a CORS package, for example NelmioCorsBundle:

```
$ composer require barryvdh/laravel-cors
```

Next, add the service provider to `config/app.php`

```
Barryvdh\Cors\ServiceProvider::class,
```

Step 3: Configuration
---------------------

Still in the console, enter this command to install the config file in  the `config/` directory.
 
```
php artisan vendor:publish
```

Two files should've been added `config/forest.php` and `config/cors.php`

Step 3.1 : Configuration `config/cors.php`
------------------------------------------

This configuration files should looks like the following code :

```
return [
    'supportsCredentials' => false,
    'allowedOrigins' => ["http://app.forestadmin.com", "https://app.forestadmin.com"],
    'allowedHeaders' => ['*'],
    'allowedMethods' => ['POST', 'PUT', 'GET', 'DELETE'],
    'exposedHeaders' => [],
    'maxAge' => 0,
    'hosts' => [],
];
```

Step 3.2 : Configuration `config/forest.php`
--------------------------------------------

Generate a secret key for your application on http://forestadmin.com, and paste it in you `forest.php` file.

```
    'SecretKey' => '...', // given by Forest.
```

Also, add your pass phrase to the Forest config:

```
    'AuthKey' => 'ChooseARandomString',
```

Step 4: Database mapping
------------------------

Now, you need to generate a mapping of your database and send it to http://forestadmin.com. 
To do so, you just have to run the following command
 
 ```
 $ php artisan forest:postmap
 ```

This command should be run each time that you make modification in your database's structure. (Not when a new data is inserted)
Mainly, whenever you run a `php artisan migrate` you should run the command above after.

Right now, I didn't found a way to automate this process. If you have an idea, contact me ;)
