# SQLContentProvider
PHP content provider class for dynamic MySQL prepared statements.
[PHP MySQLi prepared statement quickstart](http://php.net/manual/de/mysqli.quickstart.prepared-statements.php)


### Getting started
##### Step 1 Setup your MySQL login credentials
```php
const SQLSERVER = "";
const SQLDB = "";
const SQLUSER = "";
const SQLPASS = "";
```

### Usage example
##### Require 
```php
require_once("SQLContentProvider.php");
```


##### INSERT data to your MySQL database 
```php
$sqlQuery = "INSERT INTO ExampleTable (example_column_integer, example_column_string) VALUES (?, ?)";
$reusableMySQLiConnectionOrNull = SQLContentProvider::sqlConnect(); //This is only needed if you want to reuse your MySQLi connection
$new_auto_incremented_id = SQLContentProvider::setDataAssoc($reusableMySQLiConnectionOrNull, $sqlQuery, "ExampleTable", array("example_column_integer" => $integer_value, "example_column_string" => $string_value));
```


##### UPDATE data in your MySQL database 
```php
$sqlQuery = "UPDATE ExampleTable SET example_column_integer = ?, example_column_string = ? WHERE example_column_id = ?";
$reusableMySQLiConnectionOrNull = SQLContentProvider::sqlConnect(); //This is only needed if you want to reuse your MySQLi connection
SQLContentProvider::setDataAssoc($reusableMySQLiConnectionOrNull, $sqlQuery, "ExampleTable", array("example_column_integer" => $integer_value, "example_column_string" => $string_value, "example_column_id" => $id_value));
```


##### SELECT data from your MySQL database 
```php
$sqlQuery = "SELECT example_column_integer, example_column_string FROM ExampleTable WHERE example_column_id = ? LIMIT 1";
$reusableMySQLiConnectionOrNull = SQLContentProvider::sqlConnect(); //This is only needed if you want to reuse your MySQLi connection
$sql_select_result_array = SQLContentProvider::getDataAssoc($reusableMySQLiConnectionOrNull, $sqlQuery, "ExampleTable", array("example_column_id" => $id_value));
```