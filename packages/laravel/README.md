# Laravel framework runtime source

The contents of `.laravel/` in this directory are the framework's runtime and generator source. Composer copies them into the consuming application's root `.laravel/` directory after autoload generation.

The generated application directory is ignored by Git. Keep source changes here so a fresh `composer install` can recreate it from this tracked package directory.
