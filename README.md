Paype integration examples
--------------------------
This is Paype public repository with examples for synchronizing data between Your and Paype systems.

Paype offers its partners public REST API at api.paype.com, documented at https://dev.paype.me/api-docs.html. The script takes advantage of this API-s methods to allow data synchronization.

Available methods include pushing customers from your to Paype systems or pulling customers added via Paype to your databases.

Usage
-----
The script can run as command-line tool or as a website served over your preferred server.

Recommended to call via time-based job scheduling services such as CRON.

Script has 2 parameters, verbose and force. Verbose will help you see what the script does helping you identify any possible problems. Force will make the script to synchronize all data, not using the default sync flags kept in Paype systems.

CLI example:
        php index.php verbose=1 force=1

Web example:
        http://<YOUR_LOCAL_OR_PUBLIC_ADDRESS>/<SCRIPT_LOCATION>/index.php?verbose=1&force=1

Integration steps
-----------------
1. Sign Paype contracts and create your business account
2. Generate API keys in business management web
3. Implement or modify webservice client for your business needs or have Paype create it for you
4. Decide and/or discuss what synchronization methods to use (customers push and pull, only pull or only push)
5. Copy config.php-sample to config.php and fill in the blanks with API credentials, your webservice data and sync methods to use
6. Set up method to run the script according to Usage
