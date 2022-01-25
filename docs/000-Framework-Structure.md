# The Framework Structure

This framework consists of a very few core libraries of the Symfony project
and [Pimple](https://github.com/silexphp/Pimple) as di-container.

The goal is to keep the footprint low, so it is easy to pack applications written with this framework in a small `.phar`
-container for distribution.

The service definitions and app configuration is stored in YAML-files in an application root directory which can
specified during the bootstrap period. Please refer to
the [standard project template](https://github.com/sweikenb/console-framework-standard/tree/main/app) for details about
the files or have a look in the corresponding sections below.
