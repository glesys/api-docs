# What is this?
This is the place to go for [documentation](https://github.com/GleSYS/API/wiki), [examples](https://github.com/GleSYS/API/) and [support/feedback](https://github.com/GleSYS/API/issues) for [GleSYS](http://www.glesys.se) API.

You should start out by reading the [introduction](https://github.com/GleSYS/API/wiki/Api-Introduction).

# Where can I find documentation?
The [documentation](https://github.com/GleSYS/API/wiki) can be found right here on github in our [wiki](https://github.com/GleSYS/API/wiki). It comes in the form of an [introduction](https://github.com/GleSYS/API/wiki/Api-Introduction) and a full [documentation of all functions available in GleSYS API](https://github.com/GleSYS/API/wiki/API-Documentation). This documentation has been automatically generated from the API.

# Where can I find examples?
The examples are available right here on our [github repo](https://github.com/GleSYS/API/). They are broken up by scripting/programming language and if you browse around in the repository you will find that each example has its own description.

Please feel free to contribute with more examples. You could either do a pull-request or send us an email to [support@glesys.se](mailto:support@glesys.se)

Remember that all these examples are just examples. If you want to use any of these scripts in an production environment, its on your own responsibility.

## Current examples
### BASH
[All bash examples](https://github.com/GleSYS/API/tree/master/BASH)
* [LocalMemUpgrade](https://github.com/GleSYS/API/tree/master/BASH/LocalMemUpgrade) - Upgrade memory on a local server based on memoryusage.
* [RemoteMemUpgrade](https://github.com/GleSYS/API/tree/master/BASH/RemoteMemUpgrade) - Upgrade memory on a remote server based on memoryusage.
* [RoundRobinLoadBalancer](https://github.com/GleSYS/API/tree/master/BASH/RoundRobinLoadBalancer) - Basic round robin load balancer with availability monitoring.

### PHP
[All PHP examples](https://github.com/GleSYS/API/tree/master/PHP)
* [InvoiceRSS](https://github.com/GleSYS/API/tree/master/PHP/InvoiceRSS) - List invoices in a RSS-feed.
* [MiniControlpanel](https://github.com/GleSYS/API/tree/master/PHP/MiniControlpanel) - A miniature control panel.
* [api_classes](https://github.com/GleSYS/API/tree/master/PHP/api_classes) - PHP-classes for managing domains and email accounts.

### Ruby
[All Ruby examples](https://github.com/GleSYS/API/tree/master/Ruby)
* [CreateDestroyList](https://github.com/GleSYS/API/tree/master/Ruby/CreateDestroyList) - Create, Destroy and List servers.

## Python
[All Python examples](https://github.com/GleSYS/API/tree/master/Python)
* [ConkyServerStatus](https://github.com/GleSYS/API/tree/master/Python/ConkyServerStatus) - Commandline script to be used with [conky](http://conky.sourceforge.net/) to show a specific glesys servers status directly on your desktop

# Are there any frameworks/toolboxes available?
If you are looking for a framework to manage your GleSYS virtual servers take a look at the following projects:

* [glesys-go](https://github.com/glesys/glesys-go) - An official client library written in go.
* [docker-machine-driver-glesys](https://github.com/glesys/docker-machine-driver-glesys) - An official glesys api driver for docker machine
* [fog](http://fog.io) - The Ruby cloud services library.
* [jclouds](http://www.jclouds.org/) - API abstractions as java and clojure libraries.
* [knife-glesys](https://github.com/smgt/knife-glesys) - A Knife addon for managing you GleSYS vps. Uses fog.
* [glesys-bash-client](https://github.com/MrDaar/glesys-bash-client) - A bash client for interacting with the GleSYS API.
* [glesys-dnshook](https://github.com/blastur/glesys-dnshook) - A hook for the Let's Encrypt ACME client dehydrated that allows you to use GleSYS DNS records to respond to dns-01 challenges.
* [lexicon](https://github.com/AnalogJ/lexicon) - Manipulate DNS records on various DNS providers (including GleSYS) in a standardized/agnostic way.
* [multipass](https://github.com/joelek/multipass) - Fully-automated certificate manager for NodeJS 16 with support for various DNS providers (including GleSYS).

If you know about any other frameworks that support the GleSYS API, please let us know!

# Where can I get support?
You can either [open a new issue here on github](https://github.com/GleSYS/API/issues) or send us an email at [support@glesys.se](mailto:support@glesys.se)

# License

If not stated otherwise, the contents of this repository are distributed under the MIT license.
