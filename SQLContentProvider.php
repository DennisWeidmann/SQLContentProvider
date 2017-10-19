<?php
/**
 * SQLContentProvider
 *
 * PHP content provider class for dynamic MySQL prepared statements.
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2016 Dennis Weidmann
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

class SQLContentProvider {
    /** @const Class constant MySQL Server URL */
    const SQLSERVER = "";

    /** @const Class constant MySQL Database Name */
    const SQLDB = "";

    /** @const Class constant MySQL User */
    const SQLUSER = "";

    /** @const Class constant MySQL Password */
    const SQLPASS = "";

    /** @const Class constant Data Types cache file */
    const SQLCONTENTPROVIDERCACHEFILE = "SQLContentProviderDataTypes.json";


    /**
     * Read data from database, returning an associative array with the results
     *
     * @param    string $sqlQuery a MySQL query string
     * @param    string $tableName MySQL Table name
     * @param    array  $columnValueDictionary an associative array containing all ? escaped column names as key and their value as value
     * @return   array
     */
    public static function getDataAssoc ($sqlQuery, $tableName, $columnValueDictionary) {
        $columnNameArray = array();
        $columnValueArray = array();

        foreach ($columnValueDictionary as $key => $value) {
            array_push($columnNameArray, $key);
            array_push($columnValueArray, $value);
        }

        return self::getData($sqlQuery, $tableName, $columnNameArray, $columnValueArray);
    }


    /**
     * Read data from database, returning an associative array with the results
     *
     * @param    string $sqlQuery a MySQL query string
     * @param    string $tableName MySQL Table name
     * @param    array  $columnNameArray an array containing all ? escaped column names
     * @param    array  $columnValueArray an array containing all ? escaped column values
     * @return   array
     */
    private static function getData ($sqlQuery, $tableName, $columnNameArray, $columnValueArray) {
        $columnNameArray = self::sqlFieldTypeValueArrayByFieldNameArray ($columnNameArray, $tableName);
        
        $whereTypeArray = self::getTypeArrayFromTypeValueArray($columnNameArray);
        $columnNameArray = self::getNameArrayFromTypeValueArray($columnNameArray);
        
        $returnArray = array();
        
        if ($columnNameArray != NULL && $whereTypeArray != NULL && count($columnNameArray) == count($whereTypeArray)) {
            $sqlConnection = self::sqlConnect();
            $sqlStatement = $sqlConnection->prepare($sqlQuery);
            
            if (count($columnNameArray) > 0) {
                $bindArray[] = implode("", $whereTypeArray);
                
                for ($i=0; $i < count($columnValueArray); $i++) {
                    $bindArray[] = &$columnValueArray[$i];
                }
                
                call_user_func_array(array($sqlStatement,'bind_param'), $bindArray);
                
                $sqlStatement->execute();
                $result = $sqlStatement->get_result();
                while ($row = $result->fetch_array(MYSQLI_ASSOC)){
                    array_push($returnArray, $row);
                }
            }
        }

        return $returnArray;
    }


    /**
     * Write data to database, returning the new generated id if insert auto_increment
     *
     * @param    string $sqlQuery a MySQL query string
     * @param    string $tableName MySQL Table name
     * @param    array  $columnValueDictionary an associative array containing all ? escaped column names as key and their value as value
     * @return   integer
     */
    public static function setDataAssoc ($sqlQuery, $tableName, $columnValueDictionary) {
        $columnNameArray = array();
        $columnValueArray = array();

        foreach ($columnValueDictionary as $key => $value) {
            array_push($columnNameArray, $key);
            array_push($columnValueArray, $value);
        }

        return self::setData($sqlQuery, $tableName, $columnNameArray, $columnValueArray);
    }


    /**
     * Write data to database, returning the new generated id if insert auto_increment
     *
     * @param    string $sqlQuery a MySQL query string
     * @param    string $tableName MySQL Table name
     * @param    array  $columnNameArray an array containing all ? escaped column names
     * @param    array  $columnValueArray an array containing all ? escaped column values
     * @return   integer
     */
    private static function setData ($sqlQuery, $tableName, $columnNameArray, $columnValueArray) {
        $columnNameArray = self::sqlFieldTypeValueArrayByFieldNameArray ($columnNameArray, $tableName);
        
        $fieldTypeArray = self::getTypeArrayFromTypeValueArray($columnNameArray);
        $columnNameArray = self::getNameArrayFromTypeValueArray($columnNameArray);
        
        $returnID = NULL;
        
        if ($columnNameArray != NULL && $fieldTypeArray != NULL && count($columnNameArray) == count($fieldTypeArray)) {
            $sqlConnection = self::sqlConnect();
            $sqlStatement = $sqlConnection->prepare($sqlQuery);
            
            if (count($columnNameArray) > 0) {
                $bindArray[] = implode("", $fieldTypeArray);
                
                for ($i=0; $i < count($columnValueArray); $i++) {
                    $bindArray[] = &$columnValueArray[$i];
                }
                
                call_user_func_array(array($sqlStatement,'bind_param'), $bindArray);
                
                $sqlStatement->execute();
                $returnID = $sqlStatement->insert_id;
            }
        }
        
        return $returnID;
    }


    /**
     * Automatic parsing of tables and columns with prepared statement data type association
     *
     * @param    array $fieldNameArray all ? escaped column names of the current query
     * @param    string $tableName name of the MySQL Table
     * @return   array
     */
    private static function sqlFieldTypeValueArrayByFieldNameArray ($fieldNameArray, $tableName) {
        $fieldTypeValueArray = array();

        if ($tableName == "INFORMATION_SCHEMA") {
            foreach ($fieldNameArray as $key => $value) {
                switch ($value) {
                    case 'TABLE_SCHEMA':
                        array_push($fieldTypeValueArray, array("name" => $value, "type" => "s"));
                }
            }
            return $fieldTypeValueArray;
        }

        $fieldTypeInfos = array();
        if (!file_exists(self::SQLCONTENTPROVIDERCACHEFILE)) {
            $fieldTypeInfos = self::parseSQLFieldTypeValueArray();
        } else {
            $fieldTypeInfos = json_decode(file_get_contents(self::SQLCONTENTPROVIDERCACHEFILE), true);
        }

        if (!array_key_exists(strtolower($tableName), $fieldTypeInfos)) {
            $fieldTypeInfos = self::parseSQLFieldTypeValueArray();
        }

        foreach ($fieldNameArray as $key => $value) {
            if (!array_key_exists($value, $fieldTypeInfos[strtolower($tableName)])) {
                $fieldTypeInfos = self::parseSQLFieldTypeValueArray();
            }
            array_push($fieldTypeValueArray, array("name" => $value, "type" => $fieldTypeInfos[strtolower($tableName)][$value]));
        }
        
        return $fieldTypeValueArray;
    }


    /**
     * Parse all Table- and Columnnames to a cache file
     *
     * @return   array
     */
    private static function parseSQLFieldTypeValueArray () {        
        $sqlQuery = "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ?";
        $newTypesArray = self::getData($sqlQuery, "INFORMATION_SCHEMA", array("TABLE_SCHEMA"), array(self::SQLDB));

        $databaseTypesObject = array();
        foreach ($newTypesArray as $newTypesIndex => $newTypesOject) {
            $currentTableName = strtolower($newTypesOject["TABLE_NAME"]);
            $currentColumnName = strtolower($newTypesOject["COLUMN_NAME"]);
            $currentColumnType = strtolower(self::preparedStatementTypeFromDataType($newTypesOject["DATA_TYPE"]));
            $databaseTypesObject[$currentTableName][$currentColumnName] = $currentColumnType;
        }

        file_put_contents(self::SQLCONTENTPROVIDERCACHEFILE, json_encode($databaseTypesObject));

        return $databaseTypesObject;
    }


    /**
     * Parse Prepared Statement data types from MySQL data types
     *
     * @param    string $dataType MySQL data type
     * @return   string Prepared Statement data type
     */
    private static function preparedStatementTypeFromDataType ($dataType) {
        switch (strtoupper($dataType)) {
            case 'TINYINT':
                return "i";
            case 'SMALLINT':
                return "i";
            case 'MEDIUMINT':
                return "i";
            case 'INT':
                return "i";
            case 'BIGINT':
                return "i";
            case 'CHAR':
                return "s";
            case 'VARCHAR':
                return "s";
            case 'DECIMAL':
                return "d";
            case 'NUMERIC':
                return "d";
            case 'FLOAT':
                return "d";
            case 'DOUBLE':
                return "d";
            case 'DATE':
                return "s";
            case 'DATETIME':
                return "s";
            case 'TIMESTAMP':
                return "s";
            case 'BINARY':
                return "s";
            case 'VARBINARY':
                return "s";
            case 'TINYTEXT':
                return "s";
            case 'TEXT':
                return "s";
            case 'MEDIUMTEXT':
                return "s";
            case 'LONGTEXT':
                return "s";
            case 'BLOB':
                return "b";
            default:
                return NULL;
        }
    }


    /**
     * MySQLi connection builder.
     *
     * @return   MySQLi database connection
     */
    private static function sqlConnect () {
        $connection = mysqli_connect(self::SQLSERVER, self::SQLUSER, self::SQLPASS, self::SQLDB);
        
        if (mysqli_connect_error()) {
            die("No Connection: ".mysqli_connect_error());
        } elseif ($connection != NULL) {
            return $connection;
        } else {
            die("No Connection");
        }
    }


    /**
     * Convert a type value array to a column name array
     *
     * @param    array $fieldTypeValueArray an Array created by sqlFieldTypeValueArrayByFieldNameArray
     * @return   array
     */
    private static function getNameArrayFromTypeValueArray ($fieldTypeValueArray) {
        $nameArray = array();
        
        foreach ($fieldTypeValueArray as $key => $value) {
            array_push($nameArray, $value["name"]);
        }
        
        return $nameArray;
    }


    /**
     * Convert a type value array to a column value array
     *
     * @param    array $fieldTypeValueArray an Array created by sqlFieldTypeValueArrayByFieldNameArray
     * @return   array
     */
    private static function getTypeArrayFromTypeValueArray ($fieldTypeValueArray) {
        $typeArray = array();
        
        foreach ($fieldTypeValueArray as $key => $value) {
            array_push($typeArray, $value["type"]);
        }
        
        return $typeArray;
    }
}
?>