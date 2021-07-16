# Assignment

The purpose of this project is to fulfill requirements of assignment.

## Requirements

- [x] **PHP** Version 7 or higher
- [x] **MySQL** Version 
- [x] **OS** any, I have used windows 10

## Instructions to run project

- set PHP enviornment in your system so that you can run headless server from any location. to know more refer next section.
- clone project from git
    `> git clone https://github.com/karishmanaula/rank_assignment`
- change directory to project
    `> cd rank_assignment`
- import leaderboard.sql file in your database available in root directory of the project.
- update database configrations in config.php file
    ```
    $GLOBALS['db_host'] = "127.0.0.1";
	$GLOBALS['db_user'] = "root";
	$GLOBALS['db_pass'] = "";
	$GLOBALS['db_name'] = "leaderboard";
    ```
- run headless server at port 8080
    `> php -S localhost:8000`
- now use any API client like [postman](https://www.postman.com/downloads/)
- confuse how to use postman? check [Postman learning centre](https://learning.postman.com/) for more details.

### How to set enviornment in Windows 10

- right click on my computer and select properties from context menu.
- click on Advance system settings.
- click on enviornment variable button
- edit path under system variables
- click on new button
- add php executable path `C:\xampp\php` in my case.


## API Documentation

Please check following link for API documentation

https://documenter.getpostman.com/view/4832959/TzmBEETK

## Credits

**Name:** Karishma Naula
**Email:** karishma786naula@gmail.com
**Published on:** 2021-07-16
