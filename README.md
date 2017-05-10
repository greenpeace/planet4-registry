# Greenpeace Planet 4 Registry.
In order to propose a preselection of plugin and theme as well as the Wordpress core using composer, 
Greenpeace uses a custom registry. All plugins and themes that are available inside the Planet 4 system will be
published here:

https://p4-composer-registry.greenpeace.org/

This project contains the code needed to build this Planet 4 composer registry. 
It is based on [satis](https://github.com/composer/satis) with some custom task to support themes and plugins that do not
provide composer support.

## Licence
GPL v3 or higher. See LICENCE for more information.

## Prerequisite
You need a web server able to serve html files.
You need to be able to run PHP v5.6+ in command line.
You need [composer](https://getcomposer.org/doc/00-intro.md) and git.

## Installation
The installation if this registry can be done in three simple steps:

__Step 0. Clone this repository__
```
git clone https://github.com/greenpeace/planet4-registry
```

__tep 1. [Install composer](https://getcomposer.org/doc/00-intro.md) and the project dependencies__
```
cd planet4-registry
composer install
```
__Step 2. Run the setup script__
```
composer run-script setup
```
This will do the following task:
- Copy the default statis.json file
- Clone the repositories for projects that do not support composer
- Extract the zip files references from such repositories  
- Combine the regular satis file with the extracted references
- Build the registry static html files

__Step 3. Point your virtual host configuration file to the `public` directory.__

You should then in the `public` directory the html files to be served by the webserver.

## Adjusting the registry url
If you want to use another target URL for your registry than the default one
you can edit it in `satis.json` file to be used under `homepage`. You can then
rebuild the registry as follow:
```
composer run-script build
```

## How does it work
This registry is powered by two independent systems that are combined together
with the `composer run-script build` script. 

### List of supported registry
Inside the `satis.json` file the `repositories` section is used to define 
repositories that support Composer. Each repository is required to contain a `composer.json` file. 

More information about how to use this file is available inside the 
[Satis documentation](https://getcomposer.org/doc/articles/handling-private-packages-with-satis.md#satis).

### What are the "helpers" repositories
The Wordpress Core, many plugins and themes don't support Composer out of the
box. To be able to use them with a satis registry we need to enhance them 
manually. The `repositories` folder of this repository contains all such 
dependencies.

The folder name is the name of the dependency and it contains multiple JSON 
files that reference the published version we did test and support.

As [Composer cannot load repositories recursively](https://getcomposer.org/doc/faqs/why-can%27t-composer-load-repositories-recursively.md)
we need to manage the source code and the download manually. Each file should
reference only one repository of the `package` type. 

### Continuous Integration
Once a continuous integration server is available, the management of this static
package list should be handled automatically. As soon as a new repository is 
created and push, the CI server should collect this `composer.json` files from 
them and store them in the `packages` directory. This will make sure all new
versions of Wordpress, the plugins and themes will appear in our registry, 
without each of them having direct Composer support.
