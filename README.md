# GitList
Simple github api test
 - User can enter github username and script connects to github API https://developer.github.com/v3/ and prints list of username's public repositories
 - Each search is logged to database, searches can be reviewed
 - After login, user can delete searches older than X hours, where X can be selected

Requirements:
PHP version > 5.4.4 (can be reduced if older version of Dibi library is used)
MySQL database
cURL enabled http://php.net/manual/en/book.curl.php - must allow connection to HTTPS secured servers
session enabled

Installation / Configuration
SQL script for creating required table is in _db.sql file
Fill database connection details to dibi::connect in index.php on lines 4 - 13
Login is checked in function dologin() in index.php
 - default only accepted value is username "user" and password "password" - you can change accepted credentials there, or use more soffisticated algorithm

Known issues:
 - no error handling - for test purposes was not included
 - CURLOPT_SSL_VERIFYPEER set to false
  - for security reasons, this should be removed - prevents MITM attacks
  - certificate can be added according to http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/
