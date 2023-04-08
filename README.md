# quaffa
A backup server for Linux using rsync. Targeted at backing up linux web servers.

## Features

- Uses SSH keys to securly connect to backup targets.
- Can back up multiple target servers, multiple times per day. 
- Configurable pre-backup and post-backup jobs. Comes with an included remote mysqldump script for backing up MySQL or MariaDB databases.
- Keeps a configurable history of backups.
- Uses the rsync hardlink method to save space. 
- Every backup is a full backup. No need to alternate full and incremental backups and restores.

Quaffa is a rewrite of affa backup by Michael Weinberger originally written in perl for Linux SME Server.

## Prerequisites

- A linux server (tested on Ubuntu 20.04 and CentOS 7). Any modern Linux should work with minimal modifications.
- PHP (tested with 5.4 and 7.4).
- Composer
- SSH access to the target server
#### Install the prerequisits
On RedHat / CentOS based systems:
```
yum install php composer
```
On Debian and Ubuntu based systems:
```
apt update; apt install php composer
```

### Installation

We recomend installing with composer in the /opt directory but it will happily run anywhere. 
Run the following commands as root to create a directory for the progrram, install it, make it executable.  
```
mkdir /opt/quaffa
cd /opt/quaffa
composer install quayph/quaffa
chmod 0700 quaffa
```
Finally, run the quaffa setup command to copy the necessary files and folders to their respective destinations.
```
./quaffa setup
```

### Backup localhost

Backup jobs are defined in the directory `/etc/quaffa` in files with the `.yaml` extention.
The file `localhost.yaml` defines a job named localhost that backs up the directories /etc, /root and /var/www at 3 am.
Open this file with your favorite editor. Find the line `Enabled: false` and change it to `Enabled: true` then save the file.

A full list of options available for a job configuration file can be seen in `_defaultQuaffaConfig.yaml`. Any option not present in the job configuration file is read from this file. You can edit this file to set your own defaults.

Execute the command `quaffa backup localhost` to run the backup now. The command returns immediately and the backup continues in the background.

Check on the status of the backup with the command `quaffa status`.

Once the backup has finished you can see your backup in a dated directory in `/var/quaffa/localhost`.

### Back up a remote web server with a mysql database

Create a new configuration file ending with the .yaml extention. In the example file content below, the job is called `example-job` so the file should be `/etc/quaffa/example-job.yaml`

Here is the example content of the file that you should ajust to your needs.
```
---
# Lines starting with a # are comments. This line is a comment.
debug: true # everything to the right of a # is a comment
include: 
- /etc
- /home
- /tmp
- /var/www
jobName: 'example-job' 
preJobCommandRemote:
- mysql-dump-tables
remoteHostName: 'example.com'
timeSchedule:
- '0100'
- '0900'
- '1700'
```
#### Generate a public key and send it to the remote server

`quaffa sendkey example-job` You will need to enter the root password of the remote server here. The cursor does not move whilst entering your password.

Test the connection `quaffa check-cnx example-job`.

In order to allow the included mysql-dump-tables script to function you must allow automated mysql login for your linux root user. Log onto your remote server and create the file /root/.my.cnf. The content of the file should be 
```
[client]
password=xxxxxxxxxxxx
```
where xxxxxxxxxxxx is the MySQL root user's password.

Test the backup with `quaffa backup example-job`

TODO:
- Documentation : Full description of job configuration options
- Documentation : Description of all commands and options
- Documentation : Description of how to set up backup when remote root login is not possible. 
- Documentation : Troubleshooting
- Documentation : Deduplication across backups
- Functionality : Job history
- Functionality : Web interface
