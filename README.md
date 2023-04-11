# Quaffa
A backup server for Linux using rsync. Targeted at backing up linux web servers.
#### This is an alpha version. Some work may be required to get it working.

## Features

- Uses SSH keys to securly connect to backup targets.
- Can back up multiple target servers, multiple times per day. 
- Configurable pre-backup and post-backup jobs. Comes with an included remote mysqldump script for backing up MySQL or MariaDB databases.
- Keeps a configurable history of backups.
- Uses the rsync hardlink method to save space. 
- Every backup is a full backup. No need to alternate full and incremental backups and restores.

Quaffa is a rewrite of affa backup by Michael Weinberger originally written in perl for Linux SME Server.

## Documentation
[Command line usage](Docs/Command-line-options.md)  
[Job configuration files](Docs/Job-configuration.md)  

## Prerequisites

- A linux server (tested on Ubuntu 20.04 and CentOS 7). Any modern Linux should work with minimal modifications.
- PHP (tested with 7.4).
- Composer
- root SSH access to the target server

On RedHat / CentOS based systems:  
`yum install php composer`

On Debian and Ubuntu based systems:  
`apt update; apt install php composer`

### Installation

It is recomended to install with composer in the /opt directory but it will happily run anywhere. 
Run the following commands as root to create a directory for the program, install it, make it executable.  
```
mkdir /opt/quaffa
cd /opt/quaffa
composer install quayph/quaffa
```
### Backup localhost

Backup jobs are defined in the directory `/etc/quaffa` in files with the `.yaml` extention.
The file `localhost.yaml` defines a job named localhost that backs up the directories /etc and /root at 3 am.
Open this file with your favorite editor. Find the line `Enabled: false` and change it to `Enabled: true` then save the file.

A full list of options available for a job configuration file can be seen in `_defaultQuaffaConfig.yaml`. Any option not present in the job configuration file is read from this file. You can edit `_defaultQuaffaConfig.yaml` to set your own defaults. The options are described in detail on the [Job configuration files](Docs/Job-configuration.md) page.

Execute the command `quaffa backup localhost` to run the backup now. The command returns immediately and the backup continues in the background.

Check on the status of the backup with the command `quaffa status`.

Once the backup has finished you can see your backup in a dated directory in `/var/quaffa/localhost`.

### Back up a remote web server with a mysql database

Edit the file `/etc/quaffa/example-job.yaml`.

Set `enabled: true`  
Modify `remoteHostName:` to reflect your remote server hostname or IP address.
If nécessary, change the include: line `- /var/www` to the directory containing your website(s).

#### Generate a public key and send it to the remote server

`quaffa sendkey example-job` You will need to enter the root password of the remote server here. The cursor does not move whilst entering your password.

In order to allow the included mysql-dump-tables script to function you must allow automated mysql login for your linux root user. Log onto your remote server and create the file `/root/.my.cnf` . The content of the file should be as below, replacing xxxxxxxxxxxx with the MySQL root user's password. 
```
[client]
password=xxxxxxxxxxxx
```

Test the backup with `quaffa backup example-job`

TODO:
- Documentation : How to set up backup when remote root login is not possible. 
- Documentation : Global configuration options
- Documentation : Troubleshooting
- Documentation : Deduplication across backups
- Functionality : Email notifications
- Functionality : Web interface
