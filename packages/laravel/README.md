# Laravel framework runtime source

This Composer plugin and the `.laravel/` directory beside this file are internal framework implementation details. The plugin copies the runtime into the consuming application's ignored root `.laravel/` directory after autoload generation.

Keep framework runtime changes in this package. Applications consume the package through Composer and do not need a scripts directory or a checked-in `.laravel/` runtime copy.
