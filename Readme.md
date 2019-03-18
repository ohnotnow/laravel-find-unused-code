This is little ugly hack of a console command to list unused methods from Laravel
projects.

## Installation

Just copy the `FindUnusedMethods.php` and/or `FindUnusedClasses.php` files to your `app/Console/Commands/` directory.

## Usage

Run :

```
php artisan findunused:methods
php artisan findunused:classes
```

### Details

This just looks for any `function nameOfThing` definitions in your `app` directory, then looks for _any matching calls_ to `nameOfThing` elsewhere in your app or views directories.  It's a very 'dumb' match so don't take this as 100% truth.  It does an ok job
of spotting unused methods and will ignore certain laravel conventions such as ignoring `handle` methods on generated classes, CRUD methods on controllers.

Much the same hackyness for the 'classes' version.

Just to repeat, it is _very_ hacky.
