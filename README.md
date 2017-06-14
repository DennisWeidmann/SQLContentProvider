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
const SQLCONTENTPROVIDERCACHEFILE = "SQLContentProviderDataTypes.json";
```

### Usage example
##### INSERT data to your MySQL database 
```php
$sqlQuery = "INSERT INTO ExampleTable (example_column_integer, example_column_string) VALUES (?, ?)";
$new_auto_incremented_id = SQLContentProvider::setData($sqlQuery, "ExampleTable", array("example_column_integer", "example_column_string"), array($integer_value, $string_value));
```


##### UPDATE data in your MySQL database 
```php
$sqlQuery = "UPDATE ExampleTable SET example_column_integer = ?, example_column_string = ? WHERE example_column_id = ?";
SQLContentProvider::setData($sqlQuery, "ExampleTable", array("example_column_integer", "example_column_id", "example_column_id"), array($integer_value, $string_value, $id_value));
```


##### SELECT data from your MySQL database 
```php
$sqlQuery = "SELECT example_column_integer, example_column_string FROM ExampleTable WHERE example_column_id = ? LIMIT 1";
$sql_select_result_array = SQLContentProvider::getData($sqlQuery, "ExampleTable", array("example_column_id"), array($id_value));
```