
# Roost - Database Backup Manager

Roost is a database backup manager designed to simplify the process of taking backups from one environment and moving them to another. 
It was developed for Magento 2, however can be used without it as well.

Main features:
* create/drop databases
* import databases
  * supported `*.sql`, `*.sql.gz` and `*.sql.zip` dump files
  * db dumps can be filtered on DEFINERs (including procedures and functions) and ROW_FORMAT
* export databases
  * supported `*.sql` and `*.sql.gz` dump files
  * db dumps always exports procedures and functions
  * db dumps can be filtered on DEFINERs (including procedures and functions) and ROW_FORMAT
  * db dumps can be [stripped](https://github.com/netz98/n98-magerun/wiki/Stripped-Database-Dumps)
* local DB dump storage folder
  * DB is exported to the storage by default
  * DB dumps is searched in the storage by default
* AWS S3 bucket as remote storage
  * DB dumps can be uploaded
  * DB dumps can be downloaded
  * DB dumps can be deleted
  * supported cleaning (keeping not more than some amount of dumps for one DB)
* tag in DB dump naming


## Requirements

* PHP 7.3+ (with permission to run `exec` and `passthru`)
* PHP extensions: posix, simplexml, zlib, fileinfo, json, pdo, pcre
* `mysql` client on the `$PATH`
* `pv` optional (run `brew install pv` on macOS)


## Installation

Download the latest release into and make it executable:

    curl -L https://github.com/findcanary/roost/releases/latest/download/roost.phar > /usr/local/bin/roost
    chmod +x /usr/local/bin/roost

Optionally install autocomplete for all commands:

    # BASH - Ubuntu / Debian
    curl -L https://raw.githubusercontent.com/findcanary/roost/master/roost.completion | sudo tee /etc/bash_completion.d/roost

    # BASH - Mac OSX (with Homebrew "bash-completion")
    curl -L https://raw.githubusercontent.com/findcanary/roost/master/roost.completion > $(brew --prefix)/etc/bash_completion.d/roost

    # ZSH - Config file
    curl -L https://raw.githubusercontent.com/findcanary/roost/master/roost.completion > ~/.roost_completion && echo "source ~/.roost_completion" >> ~/.zshrc


## Configuration

The configuration can either be provided through configuration files (file name `.roost.yml`) or as options when executing a command.

    aws-region: The region in which the S3 buckets are located
    aws-bucket: The bucket to store the database backups
    aws-access-key: AWS Access Key
    aws-secret-key: AWS Secret Key
    db-host: MySQL DB host
    db-port: MySQL DB post
    db-username: MySQL DB username
    db-password: MySQL DB password
    db-name: MySQL DB name
    project: Project key
    storage: Local path where DB dumps are located
    magento-directory: Default magento root directory
    table-groups: Table groups for stripping databases during exporting

The configuration is searched in various places then merged, if config keys found in more than one file they are overwritten.
Here are places and priorities for the configuration (from lowest to highest):

* **Internal**: `config/config.yml` the configuration contains the default `table-groups` for stripping M2 databases during export
* **Home directory**: default configuration
* **Fallback**: from the working directory to home (or root if the working directory is outside of the home), so configuration in the working directory has higher priority than their parent directories
* **Magento2 env.php**: it tries to find `app/etc/env.php` file relatively to the working directory (with a fallback to home), if it is found then it has higher priority than all configuration except that one which is located in the working directory
* **cli options**: any config value can be overwritten by command option, they have the highest priority

If a command is run outside of the magneto root folder, but there is needed to use its configuration then `--magento-directory` (or `-m`) option can be passed with full or relative to the current folder path.


## Example of usage

General configuration can be defined in home directory (`~/.roost.yml`):

    aws-region: The region in which the S3 buckets are located
    aws-bucket: The bucket to store the database backups
    aws-access-key: AWS Access Key
    aws-secret-key: AWS Secret Key
    db-host: MySQL DB host
    db-port: MySQL DB post
    db-username: MySQL DB username
    db-password: MySQL DB password
    storage: Local path where DB dumps are located

Project-specific configuration should be defined in each project folder (`<project-folder>/.roost.yml`):

    project: Project key
    
If the project is not Magento 2 then DB configuration can be defined as well:

    project: Project key
    db-name: MySQL DB name


Having such configurations defined allows:
* always have access to all DBs and dumps
* no necessity to specify project key and/or DB credentials when a command is running from a project root folder
* keep all DB dumps in one place (storage folder)
