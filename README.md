# Greenpeace Planet 4 Registry.

In order to propose a preselection of plugin and theme (as well as the Wordpress core) 
using composer, Greenpeace uses a custom registry. All plugins and themes that are 
available inside the Planet 4 ecosystem will be published here:

https://p4-composer-registry.greenpeace.org/

This project contains the code needed to build this registry. It is based on 
[satis](https://github.com/composer/satis) with some custom tasks to support themes and 
plugins that do not provide composer support by default (see. _how it works_ 
section bellow for more details).

## Licence

GPL v3 or higher. See [LICENCE](LICENCE) file for more information.

## Prerequisite

- You need a web server able to serve html files.
- You need to be able to run PHP v5.6+ in command line (php-cli).
- You need [composer](https://getcomposer.org/doc/00-intro.md) and git.

## Installation

The installation of this registry can be done in fout simple steps:

__Step 1. Clone this repository__

```
git clone https://github.com/greenpeace/planet4-registry
```

__Step 2. [Install composer](https://getcomposer.org/doc/00-intro.md) and the project dependencies__

```
cd planet4-registry
composer install
```

__Step 3. Run the setup script__

```
composer run-script setup
```

This will perform the following tasks:
- Make a working copy of the default statis.json file
- Clone the repositories for projects that do not support composer
- Extract the release zip files references from such repositories  
- Combine the regular VCS references present in the satis file with the extracted static file references
- Build the registry static html files in the `public` directory

__Step 4. Point your webserver to the `public` directory.__

Edit your nginx configuration file or apache virtual host configuration file to use the `public`
directory as root folder for this website. 

## Adjusting the registry url

The default configuration file is set to use https://p4-composer-registry.greenpeace.org/
If you want to use another target URL for your registry you can edit it in `satis.json` file to 
be used under `homepage`. You can then rebuild the registry as follow:

```
composer run-script build
```

## How does it work

This registry is powered by two independent systems that are combined together
with the `composer run-script build` script. 

### List of supported registry

Inside the `satis.json` file the `repositories` section is used to define 
repositories that support Composer. Each repository is required to contain a `composer.json` file. 

More information about how to use this satis configuration file is available inside the 
[Satis documentation](https://getcomposer.org/doc/articles/handling-private-packages-with-satis.md#satis).

You can see two types of registry in the default file:
```
{ "type": "vcs", "url": "https://github.com/greenpeace/planet4-master-theme.git" },
{ "type": "vcs", "url": "repositories/planet4-wordpress" },
```

The first one is a classic git repository containing a composer file. Satis will use the tag references to present
the different versions of the package on the registry.

The second one is a reference to a local repository. This is used for "proxy" repository for libraries, themes and 
plugin that do not support composer by default. Our custom task will clone that repository locally and extract the
references to the static zip files by following the composer references.

This second reference will therefore be converted as packages in final `satis.extended.json` file as follow:
```
{
    "type": "package",
    "package": {
        "name": "greenpeace/planet4-wordpress-upstream",
        "version": "4.7.2",
        "url": "https://wordpress.org/",
        "dist": {
            "url": "https://wordpress.org/wordpress-4.7.2.zip",
            "type": "zip"
        },
        "source": {
            "type": "svn",
            "url": "https://core.svn.wordpress.org/",
            "reference": "tags/4.7.2"
        }
    }
},
```

### That sounds complicated, why is this needed?

The Wordpress Core, many plugins and themes don't support Composer out of the
box. We did not want to maintain a separate repository where we would have added
the required composer.json file, which we would have then to update for every release.
 
We chose to create "proxy repositories" that contain only a composer file pointing to 
packages, e.g. zip files associated with each supported releases of this project.
Each file should reference only one repository of the `package` type. 

You can see an example of such repository for the wordpress core here:

https://github.com/greenpeace/planet4-wordpress/blob/master/composer.json

As [Composer cannot load repositories recursively](https://getcomposer.org/doc/faqs/why-can%27t-composer-load-repositories-recursively.md)
we need to clone the registry with a custom task. 

### Future plans
Once a continuous integration server is made available, the management of this static
package list should be handled automatically. If composer adoption increases we
will drop the custom tasks and the "proxy" repositories. This does not seem to be 
on the agenda for wordpress core yet ([more](core.trac.wordpress.org/ticket/23912)).
