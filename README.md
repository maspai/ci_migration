# ci_migration
Database migration and seeding for CodeIgniter framework

# Installation
Simply put the two classes into `application/controllers` directory of your CodeIgniter project.

# Migration
### Create a migration file
`php index.php migrate create MIGRATION_NAME`

**Eg.** `php index.php migrate create users` will create a file called `xxx_users.php` in `application/migrations` directory, where `xxx` is current integer timestamps.

The file will contain following code:

    <?php
    return [
    "up" => function($dbforge, $db) {
	    //code here
    },
    "down" => function($dbforge, $db) {
	    //code here
    }];
    
The file returns an associative array containing:
- `"up":`callback for actual migration (up)
- `"down"`: callback for reverting the `"up"` process

Both `"up"` and `"down"` callback functions are equipped with:
- `$dbforge`: CodeIgniter's [database forge object](https://codeigniter.com/user_guide/database/forge.html)
- `$db`: CodeIgniter's [database/query builder](https://codeigniter.com/user_guide/database/index.html)

**Eg.**

    <?php
    return [
    "up" => function($dbforge, $db) {
        $dbforge
          ->add_field('id')
          ->add_field([
            'username' => ['type' => 'varchar(30)'],
            'name' => ['type' => 'varchar(50)'],
            'password' => ['type' => 'varchar(100)']
          ])
          ->create_table('users');
    },
    "down" => function($dbforge, $db) {
	      $dbforge->drop_table('users', true);
    }];

### Run the migration
Running `php index.php migrate` in terminal console will run in sequence all migration files that have not been run.

To run migration certain steps ahead, you can use `php index.php migrate run NUMBER_OF_STEPS` command.
**Eg.** `php index.php migrate run 3` will run 3 next migration files

### Revert (rollback) the migration
Running `php index.php migrate rollback` in console will revert all migration files in inverse direction.

To revert certain steps back, you can use `php index.php migrate rollback NUMBER_OF_STEPS`
**Eg.** `php index.php migrate rollback 1` will revert just 1 migration file that has already been run before.

### List of migrations
- `php index.php migrate past` to get list of migrations that already run successfully before.

**Result eg.**

    2019-12-14 09:40:21 1576052117_users.php
    2019-12-14 09:40:21 1576052137_roles.php
    2019-12-14 09:40:21 1576052148_user_roles.php
    
- `php index.php migrate future` to get list of migrations that has not run.

**Result eg.**

    1576052156_permissions.php
    1576052206_permission_groups.php

# Seed
Seed files are actually contains normal PHP codes, so you can literally put anything in there, including the expected purpose - seeding your database.

### Create a seed file
Run `php index.php seed create FILE_NAME` in console.

**Eg.** `php index.php seed create Users` will create a file called `Users.php` in `application/seed` directory of your project with following content:

    <?php
    return function($db) {
      //code here
    }

The function is equipped with `$db` which is CodeIgniter's [database object](https://codeigniter.com/user_guide/database/index.html).

**Eg.**

    <?php
    return function($db) {
      $admin = new Models\User();
      $admin->username = $username;
      $admin->name = 'Admin';
      $admin->password = 'admin';
      $admin->save();
    }

Above example uses syntax of [Eloquent ORM](https://laravel.com/docs/5.8/eloquent) for the callback.

### Run the seed
- Run `php index.php seed run SEED_NAME` in console will run a seed file, while
- `php index.php seed` will run all seed files

### List of seeds
`php index.php seed list` to get list of all available seed files.

**Result eg.**

    Admin.php
    AdminRolePermissions.php
    Permissions.php

# Running Migrations and Seeds in Web Browser
Thanks to nature of CodeIgniter, all functions above can be run in your web browser as well.

So instead of running `php index.php migrate run 3` in console, you can open `http://YOUR_DOMAIN/migrate/run/3` URL in web browser.

`php index.php seed run Users` command is also accessible by using `http://YOUR_DOMAIN/seed/run/Users` in web browser.

# Customizations
This is open source, so it's all yours. But maybe the most important things to custom are:

- `MIGRATION_TABLE` constant of `Migration` class: name of table to store history of migrations that already successfully executed, defaults to `'migrations'`.
- `MIGRATION_DIR` constant of `Migration` class: directory to put all migration files, defaults to `APPPATH.'migrations'`.
- `DIR` constant of `Seed` class: directory to put all seed files, defaults to `APPPATH.'seeds'`.
