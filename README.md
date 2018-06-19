# Glomr Static Site Builder

## Glomr-lib is the library implementation for Glomr

Glomr is yet another static site builder and has the following features:
* Pure PHP implementation, no other runtimes or binary dependencies
* Lightweight
* Extensible - easy to add new Builders for other template languages or content types
* Natively uses [Blade](http://laravel.com/docs/5.6/blade) - the templating system from [Laravel](https://laravel.com/)

### instalation:

composer install tedmoyses/glomr-lib

### usage

Glomr reads content from ./source directory in your project and outputs formatted
HTML to ./build directory. These paths the are defaults but can be configured with the setPath
method on the BuildContext class

```
//make principle classes
$buildContext = new Glomr\Build\BuildContext();
$buildService = new Glomr\Build\BuildService($buildContext);

// make builders
$bladeBuilder = new Glomr\Build\BladeBuilder($buildContext);
$assetBuilder = new Glomr\Build\assetBuilder($buildContext);
// ... instantiate any other Builders

// register builders
$bladeBulder->registerBuilder($bladeBuilder);
$bladeBulder->registerBuilder($assetBuilder);

// build!
$buildService->build();
```

The BladeBuilder class will look for templates in ./source/templates named \*.blade.php
It is recommended to places layouts, partials and shared elements in their own directory
outside/above templates. Variables to be used in Blade templates can be passed in an
associative array to the BuildService class method build. The templates path is a
switchable context exposed by the BladeBuilder method setContext

Glomr has a choice of watchers that will watch the source directory for file changes.
The InotifyEventsWatche is the best choice if your OS/environment supports Inotiy Events,
if not - or you notice that changes to source files do not trigger a build - then
use the PollerWatcher. This will hold the php process in a loop using a configurable
interval - to interupt the process a SIG_INT or Ctrl + C will stop the process.

```
$watcher = new Glomr\Watch\InotifyEventsWatcher($buildContext);
while ($watcher->watchBuild()){
  $buildService->build();
}
```

There is an internal service to serve up the built content - by default this service
is bound to 0.0.0.0 on port 8080. To start the server, use the runServer method from
the BuildService class - you can pass parameters here to configure the server bind address,
port and document root. This will start a background process the should be closed
down in response to a SIG_INT or Ctrl + C

### configuration

Watcher intervals are measured in milliseconds and passed as the second param in the
constructor or in the watch method on the build service as the first parameter.

You can enable debug level logging using Logr::setdebug(true)

To force the watcher used by BuildService to be the poller, use true as the second
parameter to the watch method

To enable 'low' or 'high' compression for the Asset Builders either set the second
parameter as a string value 'low' or 'high' in the constructor or  use the setCompression
method. Default is none
