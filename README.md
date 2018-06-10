# Glomr Static Site Builder

## Glomr-lib is the library implementation for Glomr

Glomr is yet another static site builder and has the following features:
* Pure PHP implementation, no other runtimes or binary dependencies
* Lightweight
* Extensible - easy to add new Builders for other template langues or content types
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
associative array to the BuildService class method build.

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
is bound to 0.0.0.0 on port 8080 - these are configurable from env vars. To start
the server, us the runServer method from the BuildService class. This will start a
background process the should be closed down in response to a SIG_INT or Ctrl + C

### configuration

If you use a .env file you can override these default values

```
interval=500 # number of milliseconds between polls or time sleeping depending on Poller
debug=0 # set to one to see debug level star/stop/build messages
watcher=poller # to force choice of PollerWatcher
compression=none # can be low or high - relates to using compression for CSS/Js - high can be risky
SERVER_PORT=8080 # port used for internal service
SERVER_ADDRESS=0.0.0.0 # address to server to bind to
SERVER_PATH=/build # used as server root path for serving built content
```
