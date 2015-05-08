Paype integration
-----------------
This is Paype public repository with examples for synchronizing data between Your and Paype systems.

Paype offers its partners public REST API at api.paype.com, documented at https://business.paype.com/api-docs.html. The scripts here take advantage of this API-s methods to allow data synchronization.

Available methods include pushing customers from Your to Paype systems or pulling customers added via Paype to Your databases.

Usage
-----
The script can run as command-line tool or as a website served over your preferred server.

Recommended to call via time-based job scheduling services such as CRON.

Script has 3 parameters: verbose, sync and force.
- Verbose will help you see scripts progress, helping you identify any possible problems.
- Sync will overwrite configured synchronization methods to call only one method specified in sync.
- Force will make the script synchronize all data and skip using the default sync flags kept in Paype systems.


CLI example:
> php index.php verbose=1 force=1 sync=customersPull

Web example:
> http://< YOUR LOCAL OR PUBLIC ADDRESS >/< SCRIPT LOCATION >/index.php?verbose=1&force=1&sync=customersPull

Integration steps
-----------------
1. Sign Paype contracts and create your business account
2. Generate API keys in business management web
3. Implement or modify webservice client for your business needs or have Paype create it for you
4. Decide and/or discuss what synchronization methods to use (customers push and pull, only pull or only push)
5. Copy config.php-sample to config.php and fill in the blanks with API credentials, your webservice data and sync methods to use
6. Set up method to run the script according to Usage

Windows installation steps
--------------------------
PHP CLI.

1. Get PHP http://windows.php.net/download/ x86
2. Setup PHP php.ini, copy production and
  1. Enable Curl and SOAP
  2. extension_dir = "ext"
  3. error_log absolute path
3. Get Visual studio binaries http://www.microsoft.com/en-us/download/details.aspx?id=30679 "Visual C++ Redistributable for Visual Studio 2012 Update 4" x86 for VC11 PHP version 
4. Test php to work (CMD php -i)
5. Get this project down
6. Set up config values (your webservice location and credentials, Paype Public API connection)
7. Make sync-task.bat 
	Add absolute paths start of PHP sync:
	start "" "C:\Users\paype_user\Downloads\php\php.exe" C:\Users\paype_user\Downloads\php\integration-master\index.php verbose=1
8. Test sync to work, check sync-task to generate logs in error_log and customers to sync
9. Add sync-task.bat path to run every minute in Task Scheduler