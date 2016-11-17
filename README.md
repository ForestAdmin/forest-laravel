<!-- https://github.com/the-control-group/voyager -->

# Forest Laravel Liana

The official Laravel liana for Forest.  
Forest is a modern admin interface (see the [live demo](https://app.forestadmin.com/login?livedemo)) that works on all major web frameworks.
forestadmin/forest-laravel is the package that makes Forest admin work on any Laravel application.

## Installation

Visit [Forest's website](http://www.forestadmin.com), enter your email and click "Get started".  
You will then follow a 4-step process:

1. Choose your stack (Laravel)
2. Install Forest Liana
  ```bash
  ## Install the forest-laravel package
  composer require forestadmin/forest-laravel
  ```
  Next, add the service provider to `config/app.php`

  ```
  ForestAdmin\ForestLaravel\ForestServiceProvider::class,
  ```

  Still in the console, enter this command to install the config file in  the `config/` directory.

  ```
  php artisan vendor:publish
  ```

  Generate a secret key for your application on http://forestadmin.com, and
  configure the `config/forest.php` file like this:

  ```
  return [
    'secret_key' => 'YOUR-SUPER-SECRET-SECRET-KEY',
    'auth_key' => 'YOUR-SUPER-SECRET-AUTH-KEY',
    'models_path' => 'app/models'
  ];
  ```

  Finally, you need to generate a mapping of your database schema and send it to http://forestadmin.com running the following command:

   ```
   $ php artisan forest:send-apimap
   ```


3. Get your app running, provide your application URL and check if you have successfully installed Forest Liana on your app.  
4. Choose your credentials, log into https://app.forestadmin.com and start customizing your admin interface! 🎉

**NOTE: If you’re stuck, can’t get something working or need some help, feel free to contact the Forest team at support@forestadmin.com**

## How it works

Installing forestadmin/forest-laravel into your app will automatically generate an admin REST API for your app.  
This API allows the Forest admin UI to communicate with your app and operate on your data.  
Note that data from your app will never reach Forest's servers. Only your UI configuration is saved.  
As this package is open-source, you're free to extend the admin REST API for any operation specific to your app.  

## How to contribute

This liana is officially maintained by Forest.  
We're always happy to get contributions for other fellow lumberjacks.  
All contributions will be reviewed by Forest's team before being merged into master.

Here is the contribution workflow:

1. **Fork** the repo on GitHub
2. **Clone** the project to your own machine
3. **Commit** changes to your own branch
4. **Push** your work back up to your fork
5. Submit a **Pull request** so that we can review your changes

## Licence

[GPL v3](https://github.com/ForestAdmin/forest-rails/blob/master/LICENSE)
