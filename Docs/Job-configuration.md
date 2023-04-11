# The Quaffa backup job configuration file

Quaffa backup job configuration files are in yaml format and must have a .yaml extention. All .yaml files in the job config directory are parsed. 

The minimum required fields are `jobName`, `include` and `timeSchedule`.

Arrays can be written as a json array
```javascript
include: [
    '/home',
    '/root'
]
```
or as a dashed list
```yaml
include:
- /home
- /root
```
## Option list

### `bandwidthLimit`
_Integer_ Default: `0`  
The bandwidth limit in KBytes per second, passed as an option to rsync when connecting to a remote server. Zero for no limit.    

### `connectionCheckTimeout`
_Integer_ Default: `120`  
Timout in seconds for the SSH connection check to a remote server  
### `debug`
_Boolean_ Default: `false`  
Overide the default logging level for this job and log verbosely

### `description`
_String_ Default: `''` (empty string)  
Free text description of the backup job.

### `diskSpaceWarn`
_Integer_ Default: `85`  
Disk space warning limit expressed as a percentage. If the target disk is full over this percentage a warning will be shown on the command line, in the logs and in email notifications.

### `extraEmailNotifications`
_Array_ Default: `[]` (empty array)  
Email notifications will be sent to the adresses in this list in addition to the global administrator email address. The administrator email address and other email configuration options are available in the application configuration file config.yaml.

### `exclude`
_Array_ Default: `[]` (empty array)  
A list of paths to exclude from the backup.

### `include`
**REQUIRED** _Array_ Default: [] (empty array)  
A list of paths to backup.

### `jobName`
**REQUIRED** _String_ Default: `''` (empty string)  
The name of the job. No white space or caracters not allowed in a file name.

### `keepDaily`
_Integer_ Default: `7`  
The number of daily backups to keep. See Backup retention.

### `keepMonthly`
_Integer_ Default: `12`  
The number of monthly backups to keep. See Backup retention.

### `keepScheduled`  
_Integer_ Default: `1`  
The number of scheduled backups to keep. See Backup retention.

### `keepWeekly`
_Integer_ Default: `4`  
The number of weekly backups to keep. See Backup retention.

### `keepYearly`
_Integer_ Default: `2`  
The number of yearly backups to keep. See Backup retention.

### `localNice`
_Integer_ Default: `0`  
Value passed to the nice binary on the backup server to proiritise the cpu usage of the rsync process. -20 = top priority, +19 = lowest priority.

### `postJobCommand`
_String_ | _Array_ Default: `[]` (empty array)  
 
Name(s) of script(s) in the scripts folder. Scripts must be executable. The scripts are run on the local quaffa server after the rsync backup has completed. 

### `postJobCommandRemote`
_Array_ Default: `[]` (empty array)  
Name(s) of script(s) in the scripts folder. Scripts must be executable. The scripts are run on the remote server after the rsync backup has completed. 

### `preJobCommand`
_Array_ Default: `[]` (empty array)  
Name(s) of script(s) in the scripts folder. Scripts must be executable. The scripts are run on the local quaffa server before the rsync backup has completed. 

### `preJobCommandRemote`
_Array_ Default: `[]` (empty array)  
Name(s) of script(s) in the scripts folder. Scripts must be executable. The scripts are run on the remote server before the rsync backup has completed.  
_Commonly used to run a database dump to be included in the backup_.

### `remoteAuthorizedKeysFile`
_String_ Default: "/root/.ssh/authorized_keys"  
Path to the authorized_keys file on the remote server. Commonly `authorized_keys` or `authorized_keys2`.

### `remoteHostName`
_String_ Default: `''` (empty string)  
Hostname or IP address of the remote server to back up. An empty string is interpreted as `localhost`.

### `remoteNice`
_Integer_ Default: `0`  
Value passed to the nice binary on the remote server to proiritise the cpu usage of the rsync process. -20 = top priority, +19 = lowest priority.

### `remoteNiceBinary`
_String_ Default: `/bin/nice`
Path to the nice binary on the remote server.

### `remoteRsyncBinary`
_String_ Default: `/usr/bin/rsync`  
Path to the rsync binary on the remote server.

### `remoteUser`
_String_ Default: `root`
SSH username with which to connect to the remote server.

### `rootDir`
_String_ Default: `/var/quaffa`
Directory on the quaffa backup server that will contain the backups.

### `rsyncCompress`
_Boolean_ Default: `true`  
Activates compression in the rsync data transfer.

### `rsyncInplace`
_Boolean_ Default: `true`  
This option changes how rsync transfers a file when the file's data needs to be updated: instead of the default method of creating a new copy of the file and moving it into place when it is complete, rsync instead writes the updated data directly to the destination file.

WARNING: you should not use this option to update files that are being accessed by others, so be careful when choosing to use this for a copy.

This option is useful for transfer of large files with block-based changes or appended data, and also on systems that are disk bound, not network bound.

### `#rsyncOptions`
_String_ Default: `''` (empty string)  
Any extra rsync options that you wish to use. The following options are already used by default:
`--archive --hard-links --stats --delete-during --ignore-errors --delete-excluded --relative --partial --numeric-ids --link-dest` 

### `rsyncRemote`
_String_ Default: `/usr/bin/rsync`  
Path to rsync on the remote server.

### `rsyncTimeout`
_Integer_ Default: `900`  
Timeout for the rsync command in seconds.

### `sshOpts`
_String_ Default: `''` (empty string)  
Extra options passed to the SSH command. The following options are already used by quaffa: `CheckHostIP StrictHostKeyChecking HostKeyAlias UserKnownHostsFile`

### `sshPort`
_Integer_ Default: `22`  
The port for SSH connection to the remote server.

### `enabled`
_Boolean_ Default: `true`  
Enable or disable the backup job.

### `timeSchedule`
**REQUIRED** _String_ | _Array_ Default: `'0000'` (midnight)  
The time or times to run the backup. Expressed as HHMM.