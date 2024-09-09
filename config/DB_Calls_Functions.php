<?php

namespace Config;

/** This class contains all database call functions
 * View 
 *
 * PHP version 5.4
 */
class DB_Calls_Functions  extends DB_Connect {
    /**
     * used for creating a random unique code for a colum like trackid, userpubkey etc
     * 
     */
    public static function createUniqueRandomStringForATableCol($length,$tablename,$tablecolname,$tokentag,$addnumbers,$addcapitalletters,$addsmalllletters,$base32number=false){
           
            $loopit=true;
            $input="";
            if($addcapitalletters){
                $capitalletters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                $input=$input.$capitalletters;
            }
            if($addnumbers){
                if($base32number){
                    $numbers = "234567";
                }else{
                    $numbers = "0123456789";
                }
                $input=$input.$numbers;
            }
            if($addsmalllletters){
                $smallletters ="abcdefghijklmnopqrstuvwxyz";
                $input=$input.$smallletters;
            }
            
            $strength= $length;
            $tokenis = Utility_Functions::generate_string_from_chars($input, $strength);
            
            while($loopit){
                    // check field
                if (self::checkIfRowExistAndCount($tablename,
                    [
                        [
                            ['column' =>$tablecolname,'operator' =>'=','value' =>$tokenis],
                        ]
                    ]
                    ) > 0){
                    $tokenis = Utility_Functions::generate_string_from_chars($input, $strength);
                }else{
                    $loopit=false; 
                }
            }
            return $tokentag.$tokenis;
    }
    /**
     * Check if data exist in a colum where some data is
     * if response is >0 then data already in the column
     * $whereColumns = ['age' => ['>=', 18], 'name' => ['!=', 'John']];
     */
    public static function checkIfRowExistAndCount($tableName, $whereColumns, $options=[], $topLevelOperator='AND'){
        $total=0;
        $total=count(self::selectRows($tableName, 'id', $whereColumns, $options, $topLevelOperator));
        return $total;
    }
    /**
     * Delete rows from a specified table based on given conditions.
     * 
     * @param string $tableName The name of the table from which to delete rows.
     * @param array $whereColumns An array containing conditions for deletion.
     * @param array $options Additional options for deletion.
     * @return mixed Returns the result of the deletion operation.
     * 
     * Example usage:
     * 
     *  $deletedData = $db_call_class->deleteRows(
     *      "coupons", // Table name: 'coupons'
     *      [   // Conditions for deletion:
     *          [
     *              ['column' =>'trackid', 'operator' =>'=', 'value' =>$coupon_tid]
     *          ]
     *      ],
     *      [   // Additional options:
     *          'joins' => [
     *              [
     *                  'type' => 'LEFT',
     *                  'table' => 'table2',
     *                  'condition' => 'main_table.id = table2.main_id'
     *              ],
     *              [
     *                  'type' => 'INNER',
     *                  'table' => 'table3',
     *                  'condition' => 'table2.id = table3.table2_id'
     *              ]
     *          ],
     *          'orderBy' => 'main_table.name',
     *          'orderDirection' => 'DESC',
     *          'limit' => 10,
     *          'offset' => 10
     *      ]
     * );
     */
    public static function deleteRows($tableName, $whereColumns,$options=[], $topLevelOperator = 'AND')
    {
        $connect = static::getDB();
        $orderBy= $options['orderBy'] ?? null;
        $orderDirection = $options['orderDirection'] ?? 'ASC';
        $limit = $options['limit'] ?? null;

        if (count($whereColumns) >= 1) {
            // Construct WHERE part of the query
            $wherePart = '';
            $whereValues = [];
            $whereDatais= self::buildWhereClause($whereColumns,$topLevelOperator);
            if(!Utility_Functions::input_is_invalid($whereDatais)){
                $wherePart = $whereDatais['wherePart'];
                $whereValues =  $whereDatais['whereValues'];
            }
           
            

            // Construct ORDER BY part of the query if provided
            $orderByPart = $orderBy !== null ? " ORDER BY $orderBy $orderDirection" : '';

            // Construct LIMIT part of the query if provided
            $limitPart = $limit !== null ? " LIMIT $limit" : '';

            // Combine WHERE, ORDER BY, and LIMIT parts
            $query = "DELETE FROM $tableName $wherePart$orderByPart$limitPart";
            // print_r($query);
            $stmt = $connect->prepare($query);

            if ($stmt === false) {
                throw new \Exception('Failed to prepare statement: ' . $connect->error ." Query: $query");
            }

            // Combine all data values
            if (!Utility_Functions::input_is_invalid($whereColumns) && !Utility_Functions::input_is_invalid($whereValues)) {
                $params = array_values($whereValues);
                // Detect types
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i'; // integer
                    } elseif (is_float($param)) {
                        $types .= 'd'; // double
                    } elseif (is_string($param)) {
                        $types .= 's'; // string
                    } else {
                        $types .= 'b'; // blob and unknown types
                    }
                }

                if (! $stmt->bind_param($types, ...$params)) {
                    throw new \Exception("Binding parameters failed: " . $stmt->error  ." Query: $query");
                }
            }
            
            if (!$stmt->execute()) {
                throw new \Exception('Failed to execute statement: ' . $stmt->error  ." Query: $query");
            }

            $affectedRows = $stmt->affected_rows;
            $stmt->close();
        


            return $affectedRows > 0;
        }

        return false;
    }
    /**
     * Update data in a column based on conditions  
     * @param string $tableName The name of the table from which to delete rows.
     * @param array $updateColumns An array containing column to update.
     * @param array $whereColumns An array containing conditions for deletion.
     * @param array $options Additional options for deletion.
     * @return mixed Returns the result of the deletion operation.
     * Example usage:
     * $db_call_class->updateRows("system_otps",["status"=>1],
     * [   
     *     [
     *         ['column' =>'id', 'operator' =>'=', 'value' =>$createdOtpId]
     *     ]
     * ]
     *  );
    */
    public static function updateRows($tableName, $updateColumns, $whereColumns, $options=[], $topLevelOperator = 'AND')
    {
        $connect = static::getDB();
        $orderBy= $options['orderBy'] ?? null;
        $orderDirection = $options['orderDirection'] ?? 'ASC';
        $limit = $options['limit'] ?? null;

        if (count($whereColumns) >= 1) {
                // Construct SET part of the query
                $setPart = implode(', ', array_map(function ($col) {
                    return "$col = ?";
                }, array_keys($updateColumns)));

                $wherePart = '';
                $whereValues = [];
                // Construct WHERE part of the query
                $wherePart = '';
                $whereValues = [];
                $whereDatais= self::buildWhereClause($whereColumns,$topLevelOperator);
                    if(!Utility_Functions::input_is_invalid($whereDatais)){
                    $wherePart = $whereDatais['wherePart'];
                    $whereValues =  $whereDatais['whereValues'];
                }
                
                // Construct ORDER BY part of the query if provided
                $orderByPart = $orderBy !== null ? " ORDER BY $orderBy $orderDirection" : '';

                // Combine SET and WHERE parts
                $query = "UPDATE $tableName SET $setPart $wherePart$orderByPart";
                // Add LIMIT clause if limit is specified
                if ($limit !== null) {
                    $query .= " LIMIT $limit";
                }
                $stmt = $connect->prepare($query);

                if ($stmt === false) {
                    throw new \Exception('Failed to prepare statement: ' . $connect->error  ." Query: $query");
                }
                // Combine all data values
                $params = array_merge(array_values($updateColumns), array_values($whereValues));
                // Detect types
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i'; // integer
                    } elseif (is_float($param)) {
                        $types .= 'd'; // double
                    } elseif (is_string($param)) {
                        $types .= 's'; // string
                    } else {
                        $types .= 'b'; // blob and unknown types
                    }
                }

                if (! $stmt->bind_param($types, ...$params)) {
                    throw new \Exception("Binding parameters failed: " . $stmt->error  ." Query: $query");
                }

                if (!$stmt->execute()) {
                    throw new \Exception('Failed to execute statement: ' . $stmt->error  ." Query: $query");
                }
                $affectedRows = $stmt->affected_rows;
                $stmt->close();
        


                return $affectedRows > 0;
        }
        return false;

    }
     /**
     * select data in a column based on conditions  
     * Example usage:
     * 'orderBy' => 'main_table.name',
     * 'orderDirection' => 'DESC',
     * 'limit' => 10,
     * 'pageno' => 10,
     * 'groupBy'=>''
     * 'operator'=>'AND'
     * ['column' =>'id','operator' =>'IN','value' =>[1,2]],
     * ['column' =>'MONTH(created_at)','operator' =>'=','value' =>'MONTH(CURDATE())']
     * $getAllData= $db_call_class->selectRows("bookings", "SUM(profit) as total_profit", 
     * [
     *     [
     *         ['column' =>'id','operator' =>'IN','value' =>'DATE_SUB(DATE_SUB(LAST_DAY(NOW()), INTERVAL 1 MONTH), INTERVAL DAY(LAST_DAY(NOW())) - 1 DAY)'],
     *         ['column' =>'created_at','operator' =>'<','value' =>'DATE_SUB(DATE(NOW()), INTERVAL DAYOFMONTH(NOW()) - 1 DAY)'],
     *         'operator'=>'AND'
     *     ],
     *     [
     *         ['column' =>'MONTH(created_at)','operator' =>'=','value' =>'MONTH(CURDATE())'],
     *         ['column' =>'YEAR(created_at)','operator' =>'=','value' =>'YEAR(CURDATE())'],
     *         'operator'=>'AND'
     *     ]
     * ],
    *  [
    *    'joins' => [
    *           [
     *              'type' => 'LEFT',
     *              'table' => 'table2',
     *              'condition' => 'main_table.id = table2.main_id'
     *          ],
     *          [
     *              'type' => 'INNER',
     *              'table' => 'table3',
     *              'condition' => 'table2.id = table3.table2_id'
     *          ]
     *   ],
     *   'orderBy' => 'main_table.name',
     *   'orderDirection' => 'DESC',
     *   'limit' => 10,
     *   'pageno' => 10,
     *   'groupBy'=>''
     * ],
     *   'OR');
     * SELECT SUM(profit) as total_profit FROM bookings WHERE (id = ? OR   (status = ? AND date >= ?)) OR (created_at = ?)
     * $getAllData= $db_call_class->selectRows("bookings", "SUM(profit) as total_profit", 
     *   [
     *       [
     *           ['column' => 'id', 'operator' => '=', 'value' => 1],
     *           [
     *             ['column' => 'status', 'operator' => '=', 'value' => 'confirmed'],
     *             ['column' => 'date', 'operator' => '>=', 'value' => '2024-01-01'],
     *             'operator' => 'AND'
     *           ],
     *           'operator' => 'OR'
     *       ],
     *       [
     *       ['column' => 'created_at', 'operator' => '=', 'value' => 10],
     *       ]
     *   ],[],'OR');
     */
    public static function selectRows($tableName, $selectColumns = 'id', $whereColumns = [], $options = [], $topLevelOperator = 'AND'){
        $connect = static::getDB();
        $orderBy = $options['orderBy'] ?? null;
        $orderDirection = $options['orderDirection'] ?? 'ASC';
        $limit = $options['limit'] ?? null;
        $page_no= $options['pageno'] ?? null;
        $joins = $options['joins'] ?? [];
        $groupBy = $options['groupBy'] ?? null;
        $offset =$limit!==null && $page_no!==null? ($page_no - 1) * $limit:null;

    
        // Construct SELECT part of the query
        $selectPart = is_array($selectColumns) ? implode(', ', $selectColumns) : $selectColumns;
    
        // Construct JOIN part of the query if provided
        $joinPart = '';
        if (!Utility_Functions::input_is_invalid($joins)) {
            foreach ($joins as $join) {
                if (isset($join['type'], $join['table'], $join['condition'])) {
                    $joinType = $join['type'];
                    $joinTable = $join['table'];
                    $joinCondition = $join['condition'];
                    $joinPart .= " $joinType JOIN $joinTable ON $joinCondition";
                }
            }
        }
    
        // Construct WHERE part of the query
        // Construct WHERE part of the query
     
        $wherePart = '';
        $whereValues = [];
        $whereDatais= self::buildWhereClause($whereColumns,$topLevelOperator);
        if(!Utility_Functions::input_is_invalid($whereDatais)){
            $wherePart = $whereDatais['wherePart'];
            $whereValues =  $whereDatais['whereValues'];
        }

    
        

        // Construct GROUP BY part of the query if provided
        $groupByPart = $groupBy !== null ? " GROUP BY $groupBy" : '';
    
        // Construct ORDER BY part of the query if provided
        $orderByPart = $orderBy !== null ? " ORDER BY $orderBy $orderDirection" : '';
    
        // Construct LIMIT part of the query if provided
        $limitPart = $limit !== null ? " LIMIT $limit" : '';
        $limitPart.= $offset !== null ? " OFFSET $offset" : '';
    
        // Combine all parts to form the complete query
        $query = "SELECT $selectPart FROM $tableName$joinPart$wherePart$groupByPart$orderByPart$limitPart";
        // print($query );
        $stmt = $connect->prepare($query);
        if ($stmt === false) {
            throw new \Exception('Failed to prepare statement: ' . $connect->error  ." Query: $query");
        }
        // Combine all data values
        if (!Utility_Functions::input_is_invalid($whereColumns) && !Utility_Functions::input_is_invalid($whereValues)) {
            $params = array_values($whereValues);
            // Detect types
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i'; // integer
                } elseif (is_float($param)) {
                    $types .= 'd'; // double
                } elseif (is_string($param)) {
                    $types .= 's'; // string
                } else {
                    $types .= 'b'; // blob and unknown types
                }
            }
            if (!$stmt->bind_param($types, ...$params)) {
                throw new \Exception("Binding parameters failed: " . $stmt->error  ." Query: $query");
            }
        }
    
        if (!$stmt->execute()) {
            throw new \Exception('Failed to execute statement: ' . $stmt->error  ." Query: $query");
        }
    
        // Fetch results
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
    
        return $rows;
    }
    /**
     * Insert data 
     * Example usage:
     * $table = 'ticket_master';
     * $valuesToFill = ['age' => 1, 'name' => 1];
    */
    public static function insertRow($tableName,$valuesToFill)
    {
        $connect = static::getDB();

        // Construct the column names part of the query
        $columns = implode(', ', array_keys($valuesToFill));

        // Construct the placeholders part of the query
        $placeholders = implode(', ', array_fill(0, count($valuesToFill), '?'));

        // Construct the prepared statement
        $query = "INSERT INTO $tableName ($columns) VALUES ($placeholders)";
        $stmt = $connect->prepare($query);

        if ($stmt === false) {
            throw new \Exception('Failed to prepare statement: ' . $connect->error  ." Query: $query");
        }
        // Construct the values part of the query
        $values = array_values($valuesToFill);
        // Detect types
        $types = '';
        foreach ($values as $param) {
            if (is_int($param)) {
                $types .= 'i'; // integer
            } elseif (is_float($param)) {
                $types .= 'd'; // double
            } elseif (is_string($param)) {
                $types .= 's'; // string
            } else {
                $types .= 'b'; // blob and unknown types
            }
        }
        // Bind parameters
        if (! $stmt->bind_param($types, ...$values)) {
            throw new \Exception("Binding parameters failed: " . $stmt->error  ." Query: $query");
        }

        if (!$stmt->execute()) {
            throw new \Exception('Failed to execute statement: ' . $stmt->error  ." Query: $query");
        }

        $insertId = $stmt->insert_id;
        $stmt->close();
        

        return $insertId;
    }
    public static function runQuery($query){
        $connect = static::getDB();
        $connect->query($query);
        
    }
    public static function buildWhereClause(array $whereColumns, string $topLevelOperator = 'AND'): array{
        $wherePart = '';
        $whereValues = [];
     
        if (!Utility_Functions::input_is_invalid($whereColumns)) {
            $whereConditions = [];
            foreach ($whereColumns as $conditionGroup) {
                if (is_array($conditionGroup)) {
                    $groupConditions = [];
                    $groupOperator = $conditionGroup['operator'] ?? 'AND';
                    unset($conditionGroup['operator']);

                   
                    foreach ($conditionGroup as $condition) {
                        if (is_array($condition)&& !isset($condition['column'])) {
                            // Recursively call buildWhereClause for nested conditions
                            $nestedWhere = self::buildWhereClause([$condition], $topLevelOperator);
                            $groupConditions[] = str_replace('WHERE','',$nestedWhere['wherePart']);
                            $whereValues = array_merge($whereValues, $nestedWhere['whereValues']);
                        } else  if (is_array($condition) && isset($condition['column'], $condition['operator'], $condition['value'])) {
                            $column = $condition['column'];
                            $operator = strtoupper($condition['operator']);
                            $value = $condition['value'];

                            if (in_array($operator, ['IS', 'IS NOT']) && strtoupper($value) == 'NULL') {
                                // Handle IS NULL and IS NOT NULL operators
                                $groupConditions[] = "$column $operator NULL";
                            } elseif ($operator === 'BETWEEN' && is_array($value) && count($value) === 2) {
                                // Handle BETWEEN operator with potential SQL functions
                                $startValue = $value[0];
                                $endValue = $value[1];
                                $startCondition = (is_string($startValue) && preg_match('/^\w+\(.*\)$/', $startValue)) ? $startValue : '?';
                                $endCondition = (is_string($endValue) && preg_match('/^\w+\(.*\)$/', $endValue)) ? $endValue : '?';

                                $groupConditions[] = "$column $operator $startCondition AND $endCondition";

                                if ($startCondition === '?') {
                                    $whereValues[] = $startValue;
                                }
                                if ($endCondition === '?') {
                                    $whereValues[] = $endValue;
                                }
                            } elseif (is_string($value) && preg_match('/^\w+\(.*\)$/', $value)) {
                                // If value is a SQL function, do not bind it
                                $groupConditions[] = "$column $operator $value";
                            } elseif (is_array($value)) {
                                // Handle IN and NOT IN operators with array values
                                if (in_array($operator, ['IN', 'NOT IN'])) {
                                    $placeholders = implode(', ', array_fill(0, count($value), '?'));
                                    $groupConditions[] = "$column $operator ($placeholders)";
                                    $whereValues = array_merge($whereValues, $value);
                                } else {
                                    throw new \Exception("Unsupported operator or value for column $column with operator $operator");
                                }
                            } else {
                                // Handle regular operators
                                $groupConditions[] = "$column $operator ?";
                                $whereValues[] = $value;
                            }
                        }
                    }

                    if (!empty($groupConditions)) {
                        $whereConditions[] = '(' . implode(" $groupOperator ", $groupConditions) . ')';
                    }
                }
            }

            if (!empty($whereConditions)) {
                $wherePart = " WHERE " . implode(" $topLevelOperator ", $whereConditions);
            }
        }

        return ['wherePart' => $wherePart, 'whereValues' => $whereValues];
    }
    public static function getDBConnection()
    {
        $conn = static::getDB();
        return $conn;
        }

}