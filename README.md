# Greenpeace Planet 4 Registry.
This repository contains the Planet 4 composer registry. 
All plugins and themes that are available inside the Planet 4 system will be
published here. 

It is based on [Satis](https://github.com/composer/satis).

## Licence
GPL v3 or higher. See LICENCE for more information.

## Proof of concept
Be advised that this registry is a work in progress and the current 
implementation of combining different sources is just a proof of concept. 

## How does it work
This registry is powered by two independent systems that are combined together
with the `composer run-script build` script. 

### VCS Support
Inside the `satis.json` file the `repositories` section is used to define 
repositories that support Composer. 
Each repository is required to contain a `composer.json` file. 

More information about how to use this file is available inside the 
[Satis documentation](https://getcomposer.org/doc/articles/handling-private-packages-with-satis.md#satis).

### Satis
The Wordpress Core, many plugins and themes don't support Composer out of the
box. To be able to use them with a satis registry we need to enhance them 
manually. The `packages` folder of this repository contains all such 
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

## Installation
The installation if this registry can be done in three simple steps:

0. Copy the `satis.json.default` file to `satis.json`
1. Add your target domain URL in `satis.json` (`localhost:9292` by default)
2. Install the dependencies: `composer install`
3. Build the static Satis registry `composer run-script build`

From there you can start using Add the web address to a `composer.json` file to be
used for example in `https://github.com/greenpeace/planet4-base`

Internally this will combine the `satis.json` with the packages from the 
`packages` folder into a `satis.extended.json`. This file will be used by 
Satis to generate the static registry.

## Example usage
When testing this registry for the first time, you might one to clone some
Planet 4 related repositories to have additional information inside the 
registry.

To download three examples you can execute the following Composer command:

	composer run-script clone-repositories

This will download the following four repositories into a `repositories`
sub-directory.

	- https://github.com/greenpeace/planet4-wordpress
	- https://github.com/greenpeace/planet4-plugin-mappress-google-maps-for-wordpress
	- https://github.com/greenpeace/planet4-master-theme
	- https://github.com/greenpeace/planet4-child-theme
