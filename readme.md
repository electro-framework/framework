# electro framework

##### The framework's core, installable as a Composer package

## Installation

By requiring this package on your project's `composer.json` file, you will install the framework with all recommended packages for a standard configuration.

> Some optional parts of the framework are available elsewere as plugin packages that you can install on demand. Refer to the framework's documentation for instructions on how to install plugins.

You should not install this package on an empty project, as it doesn't provide the underlying files and directory structure required by a fully-working application.

Use the [Electro base installation](https://github.com/electro-framework/electro) as your application's starting point.

### Customized framework installation

In the future, it will be possible for anyone to create a customized version of the framework by removing some unneeded packages, and/or replacing some packages by alternative ones.

Electro has been engineered from the ground up to be a fully modular framework composed of several subsystems. Currenly those subsystems are not exposed as independent Composer packages, but they are prepared for that and they will be made available as such (using read-only Git subtree splits) on a future version of the framework.

## License

The Electro framework is open-source software licensed under the [MIT license](http://opensource.org/licenses/MIT).

**Electro framework** - Copyright &copy; Cláudio Silva and Impactwave, Lda.
