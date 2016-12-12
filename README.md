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


##### Step 2 Setup your MySQL columns and their data types
[PHP MySQLi prepared statement data types](http://php.net/manual/de/mysqli-stmt.bind-param.php)
```php
private static function sqlFieldTypeValueArrayByFieldNameArray ($fieldNameArray) {
    $fieldTypeValueArray = array();

    foreach ($fieldNameArray as $key => $value) {
        switch ($value) {
            case 'example_column_id':
                array_push($fieldTypeValueArray, array("name" => $value, "type" => "i"));
                break;
            case 'example_column_integer':
                array_push($fieldTypeValueArray, array("name" => $value, "type" => "i"));
                break;
            case 'example_column_string':
                array_push($fieldTypeValueArray, array("name" => $value, "type" => "s"));
                break;
        }
    }
    
    return $fieldTypeValueArray;
}
```


### Usage example
##### INSERT data to your MySQL database 
```php
$sqlQuery = "INSERT INTO ExampleTable (example_column_integer, example_column_string) VALUES (?, ?)";
$new_auto_incremented_id = SQLContentProvider::setData($sqlQuery, array("example_column_integer", "example_column_string"), array($integer_value, $string_value));
```


##### UPDATE data in your MySQL database 
```php
$sqlQuery = "UPDATE ExampleTable SET example_column_integer = ?, example_column_string = ? WHERE example_column_id = ?";
SQLContentProvider::setData($sqlQuery, array("example_column_integer", "example_column_id", "example_column_id"), array($integer_value, $string_value, $id_value));
```


##### SELECT data from your MySQL database 
```php
$sqlQuery = "SELECT example_column_integer, example_column_string FROM ExampleTable WHERE example_column_id = ? LIMIT 1";
$sql_select_result_array = SQLContentProvider::getData($sqlQuery, array("example_column_id"), array($id_value));
```