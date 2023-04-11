# Quaffa command line usage

## Usage
`quaffa [<command>] [<options>] [Argument]`

Executing quaffa with no command, options or arguments shows a brief help text.

### Execute a backup job
`quaffa backup example-job`  
Executes the backup job example-job.  
A single job name argument is required.

### Send SSH keys to a remote server
`quaffa sendkey example-job`  
Sends an SSH key to the remote server configured for the job example-job.
A single job name argument is required.

### Kill a running backup
`quaffa kill example-job`  
Kills the job example-job if it is running.
A single job name argument is required.

### Kill all running backups
`quaffa killall`  
Kills all running jobs.  
This command takes no argument.

### Display a history of the backups for a job
`quaffa history example-job`  
Displays dates and statistics of the last 100 times the example-job backup was executed.  
A single job name argument is required.

### Show the status of all configured backups
`quaffa status`  
Displays a table of all configured backup jobs, the time since the last backup, the backup size, the next execution time.  
This command takes no argument.

### Force an update of the cron file
`quaffa updatecron`  
Force an imediate update of the cron file.  
Quaffa automaticly checks the job configuration files every 15 minutes and updates the cron file if there are changes. Use this command if you don't want to wait for configuration changes to be taken into account.

## Options
The following options are avaiable on any command:  
`--debug` Activates verbose logging.  
`--cron` This option is added to all scheduled commands executed by cron to distinguish between scheduled and manual backups. It has no effect on backup functionality.