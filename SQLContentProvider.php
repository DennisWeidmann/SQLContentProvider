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


    /**
     * You have to declare all MySQL column names and assign their prepared statement data types.
     *
     * @param    array $fieldNameArray all ? escaped column names of the current query
     * @return   array
     */
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


    /**
     * Read data from database, returning a associative array with the results
     *
     * @param    string $sqlQuery a MySQL query string
     * @param    array  $columnNameArray an array containing all ? escaped column names
     * @param    array  $columnValueArray an array containing all ? escaped column values
     * @return   array
     */
    public static function getData ($sqlQuery, $columnNameArray, $columnValueArray) {
        $columnNameArray = self::sqlFieldTypeValueArrayByFieldNameArray ($columnNameArray);
        
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
     * @param    array  $columnNameArray an array containing all ? escaped column names
     * @param    array  $columnValueArray an array containing all ? escaped column values
     * @return   integer
     */
    public static function setData ($sqlQuery, $columnNameArray, $columnValueArray) {
        $columnNameArray = self::sqlFieldTypeValueArrayByFieldNameArray ($columnNameArray);
        
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
    
}
?>