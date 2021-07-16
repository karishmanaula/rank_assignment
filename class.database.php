<?php

class Database {

    private $db_host;
    private $db_port;
    private $db_name;
    private $db_user;
    private $db_pass;
    private $db_char;
    private $db_opts;

    private $_connection;

    private $stmt;

    private $total_rows = 1000000;
    private $batch_id = 1;
    private $insert_limit = 1000;

    /**
    * Constructor function
    *
    * @param string $db_host database host name
    * @param string $db_name database name
    * @param string $db_user database user name
    * @param string $db_pass database password
    * @param array $db_opts database additional options
    * @param string $db_port database port number
    * @param string $db_char database character type
    */
    public function __construct(string $db_host, string $db_name, string $db_user, string $db_pass, array $db_opts = [], string $db_port="3306", string $db_char = "utf8mb4") {
        $this->db_host = $db_host;
        $this->db_port = $db_port;
        $this->db_name = $db_name;
        $this->db_user = $db_user;
        $this->db_pass = $db_pass;
        $this->db_char = $db_char;
        $this->db_opts = $db_opts;
        $this->stmt = null;
        $this->setConnection();
    }

    /**
    * setConnection function is a database connection setter
    *
    * @return void
    */
    private function setConnection() {
        if( $this->_connection ) { # prevent creation of additional connection
            $this->_connection;
        }
        $db_dsn = "mysql:host=".$this->db_host.";port=".$this->db_port.";dbname=".$this->db_name.";charset=".$this->db_char;
        $this->_connection = new PDO($db_dsn,$this->db_user,$this->db_pass,$this->db_opts);
    }

    /**
    * getConnection function is getter function for database connection object
    * 
    * @return object
    */
    public function getConnection() {
        return $this->_connection;
    }

    /**
    * Magic method clone is empty to prevent duplication of connection
    */
    private function __clone() { }

    /**
    * startTransaction function will begin the database transaction
    *
    * @return void
    */
    public function startTransaction() {
        $pdo = $this->getConnection();
        $pdo->beginTransaction();
    }

    /**
    * finishTransaction function commits the database changes or finish transaction
    *
    * @return void
    */
    public function finishTransaction() {
        $pdo = $this->getConnection();
        $pdo->commit();
    }

    /**
    * rollbackTransaction function
    *
    * @return void
    */
    public function rollbackTransaction() {
        $pdo = $this->getConnection();
        $pdo->rollback();
    }

    /**
    * insert function inserts record in a given table under given column(s)
    *
    * @param array $param
    * @return void
    */
    public function insert(array $param = array('table'=>'','columns'=>[],'data'=>[])) {
        //print_r($param);die;
        // (REQUIRED) name of table
        $tableName  = isset($param['table']) ? $param['table'] : '';
        // (REQUIRED) array of column e.g. ['column','column',...]
        $arrColumns = isset($param['columns']) ? $param['columns'] : [];
        // (REQUIRED) multi dimensional array e.g. ['data','data',...]
        $arrData    = isset($param['data']) ? $param['data'] : [];

        if( count( $arrColumns ) < 1 || count( $arrData ) < 1 ) {
            echo 'columns or data or both are missing';
            die;
        }

        if( empty($tableName) ) {
            echo 'please provide table name';
            die;
        }

        if( count($arrData) != count($arrColumns) ) {
            echo "column count doesn't match with data count";
            die;
        }

        $insert_values = [];

        foreach($arrData as $d){
            $question_marks[] = '?';
            array_push($insert_values, $d);
        }

        $strQry = "INSERT INTO `".$tableName."` (" . implode(",", $arrColumns ) . ") VALUES (" . implode(',', $question_marks) . ");";

        // print_r( $strQry ); die;

        $pdo = $this->getConnection();
        $this->stmt = $pdo->prepare($strQry);
        $executed = $this->stmt->execute($insert_values);
        if( $executed ) {
            $count = $this->stmt->rowCount(); // effected rows if updated with same value
            if( $count > 0 ) {
                return [
                    'success' => true,
                    'message' => $count . ' record(s) inserted successfully.',
                    'data'    => ['id' => $pdo->lastInsertId()],
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'fails to get record(s)',
                    'data'    => null,
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'fails to get record(s)',
                'data'    => null,
            ];
        }
        
    }

    /**
    * insertMultiple function inserts record(s) in a given table under given column(s)
    *
    * @param array $param
    * @return void
    */
    public function insertMultiple(array $param = array('table'=>'','columns'=>[],'data'=>[])) {

        // $db = new PDO(...);
        // $db->beginTransaction();
        // $stmt = $db->prepare('INSERT INTO `mytable` (a, b) VALUES (?, ?)');

        // foreach ($entries as $entry) {
        //     $stmt->execute(array($entry['a'], $entry['b']));
        //     $id = $db->lastInsertId();
        // }

        // $db->commit();

        // (REQUIRED) name of table
        $tableName  = isset($param['table']) ? $param['table'] : '';
        // (REQUIRED) array of column e.g. ['column','column',...]
        $arrColumns = isset($param['columns']) ? $param['columns'] : [];
        // (REQUIRED) multi dimensional array e.g. [['data','data',...],['data','data',...],...]
        $arrData    = isset($param['data']) ? $param['data'] : [];

        if( count( $arrColumns ) < 1 || count( $arrData ) < 1 ) {
            echo 'columns or data or both are missing';
            die;
        }

        if( empty($tableName) ) {
            echo 'please provide table name';
            die;
        }

        foreach( $arrData as $d ) {
            if( count( $arrColumns ) != count( $d ) ) {
                echo "row count doesn't match with column count";
                die;
            }
        }

        $insert_values = [];
        $question_marks = [];

        foreach($arrColumns as $c){
            array_push($question_marks,'?');
        }

        foreach($arrData as $d){
            array_push($insert_values, array_values($d));
        }

        $strQry = "INSERT INTO `".$tableName."` (`" . implode("`,`", $arrColumns ) . "`) VALUES (" . implode(',', $question_marks) . ");";

        $pdo = $this->getConnection();
        $arrIds = [];
        $arrInserted = [];
        $arrFails = [];
        $recordSuccessCount = 0;
        
        foreach( $insert_values as $arrVal ) {
            $this->stmt = $pdo->prepare($strQry);
            $executed = $this->stmt->execute($arrVal);
            if( $executed ) {
                array_push( $arrIds, $pdo->lastInsertId() );
                array_push( $arrInserted, $arrVal );
                $recordSuccessCount++;
            } else {
                array_push( $arrFails, $arrVal );
            }
            $this->stmt = null;
        }

        $recordFailsCount = count($insert_values) - $recordSuccessCount;

        $res = [
            'ids' => $arrIds,
            'inserted-records' => $arrInserted,
            'fails-records' => $arrFails,
            'success-count' => $recordSuccessCount,
            'fails-count' => $recordFailsCount
        ];

        echo '<pre>'; print_r( $res ); die;
       
    }

    /**
    * get function use to fetch all records from database based on given parameters
    *
    * @param array $param
    * @return void
    */
    public function get(array $param = array('table'=>'','columns'=>0,'condition'=>[],'orderBy'=>'','direction'=>'','offset'=>0,'limit'=>0)) {

        // (REQUIRED) name of table
        $tableName      = isset($param['table']) ? $param['table'] : '';
        // (OPTIONAL) array of columns to be enter e.g. ['column','column',...]
        $arrColumns     = isset($param['columns']) ? $param['columns'] : 0;
        // (OPTIONAL) multi dimensional array e.g. [['key','operator','value'],['key','operator','value'],...]
        $arrCondition   = isset($param['condition']) ? $param['condition'] : [];
        // (OPTIONAL) name of column to be order with
        $orderBy        = isset($param['orderBy']) ? $param['orderBy'] : '';
        // (OPTIONAL) 'asc' or 'desc' for ascending or defending
        $direction      = isset($param['direction']) ? $param['direction'] : '';
        // (OPTIONAL) offset
        $offset         = isset($param['offset']) ? $param['offset'] : 0;
        // (OPTIONAL) limit, REQUIRED with offset
        $limit          = isset($param['limit']) ? $param['limit'] : 0;

        // $columns = ( count( $arrColumns ) > 0 ) ? "`" . implode("`,`", $arrColumns ) . "`" : "*";
        $columns = ( count( $arrColumns ) > 0 ) ? implode(",", $arrColumns ) : "*";
        $whereCondition = "";
        $arrConditionValues = [];

        if( count($arrCondition) > 0 ) {
            foreach( $arrCondition as $col ) {
                if( count( $col ) == 2 ) {
                    array_push( $arrConditionValues, $col[1] );
                    $whereCondition .= "`" . $col[0] . "` = ?";
                } else if( count( $col ) == 3 ) {
                    array_push( $arrConditionValues, $col[2] );
                    $whereCondition .= "`" . $col[0] . "` " . $col[1] . " ?";
                } else {
                    // error
                }
                $whereCondition .= " AND ";
            }
        }

        if( $whereCondition != "" ) {
            $whereCondition = " WHERE " . substr($whereCondition, 0, -4);
        }

        $strOrder = "";

        if( !empty($orderBy) && !empty($direction) ) {
            $strOrder = " ORDER BY `".$orderBy."` ".$direction." ";
        }

        $strLimit = "";
        $strOffset = "";

        if( !empty($limit) ) {
            $strLimit = " LIMIT ".$limit;
        }

        if( !empty($limit) && !empty($offset) ) {
            $strOffset = " OFFSET ".$offset;
        }

        $strQry = "SELECT " . $columns . " FROM `".$tableName."`" . $whereCondition . $strOrder . $strLimit . $strOffset;

        // print_r( $strQry ); die;

        $insert_values = $arrConditionValues;

        $pdo = $this->getConnection();
        $this->stmt = $pdo->prepare($strQry);
        $executed = $this->stmt->execute($insert_values);
        if( $executed ) {
            $count = $this->stmt->rowCount(); // effected rows if updated with same value
            $result = $this->stmt->fetchAll();
            
            if( count($result) > 0 ) {
                // echo count($result) . ' record(s) found.<br>';
                return [
                    'success' => true,
                    'message' => $count . ' record(s) found.',
                    'data'    => $result,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'fails to get record(s)',
                    'data'    => null,
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'fails to get record(s)',
                'data'    => null,
            ];
        }

        $this->stmt = null;
        die;
    }

    /**
    * update function use to update record(s) in database based on given parameters and optionally conditions.
    *
    * @param array $param
    * @return void
    */
    public function update(array $param = array('table'=>'','data'=>[],'condition'=>[])) {

        // (REQUIRED) name of table
        $tableName      = isset($param['table']) ? $param['table'] : '';
        // (REQUIRED) associated array of columns and data e.g. ['column'=>'value','column'=>'value',...]
        $arrData        = isset($param['data']) ? $param['data'] : [];
        // (OPTIONAL) multi dimensional array e.g. [['key','operator','value'],['key','operator','value'],...]
        $arrCondition   = isset($param['condition']) ? $param['condition'] : [];

        if( empty( $tableName ) ) {
            echo "table name missing";
            die;
        }

        if( count( $arrData ) < 1 ) {
            echo "data missing";
            die;
        }

        $insert_values = [];
        $strColumns = "";
        $strWhere = "";

        if( count( $arrData ) > 0 ) {
            foreach($arrData as $key => $val){
                $strColumns .= " `".$key."` = ?, ";
                array_push($insert_values,$val);
            }
            $strColumns = substr($strColumns, 0, -2);
        }

        if( count($arrCondition) > 0 ) {
            foreach( $arrCondition as $col ) {
                if( count( $col ) == 2 ) {
                    array_push( $insert_values, $col[1] );
                    $strWhere .= "`" . $col[0] . "` = ?";
                } else if( count( $col ) == 3 ) {
                    array_push( $insert_values, $col[2] );
                    $strWhere .= "`" . $col[0] . "` " . $col[1] . " ?";
                } else {
                    // error
                }
                $strWhere .= " AND ";
            }
            $strWhere = ' WHERE ' . substr($strWhere, 0, -4);
        }
        

        $strQry = "UPDATE `".$tableName."` SET " . $strColumns . $strWhere . ";";

        // print_r( $strQry ); 
        // print_r( $insert_values ); die;

        $pdo = $this->getConnection();
        $this->stmt = $pdo->prepare($strQry);
        $executed = $this->stmt->execute($insert_values);
        if( $executed ) {
            // $count = $this->stmt->rowCount(); // effected rows if updated with same value you will get zero
            // if( $count > 0 ) {
                // echo $count . ' record(s) updated successfully.';
                return [
                    'success' => true,
                    'message' => 'Record(s) updated successfully.',
                    'data'    => null,
                    'code'    => '001'
                ];
            // } else {
            //     return [
            //         'success' => false,
            //         'message' => 'fails to get record(s)',
            //         'data'    => null,
            //         'code'    => '002'
            //     ];
            // }
        } else {
            return [
                'success' => false,
                'message' => 'fails to get record(s)',
                'data'    => null,
                'code'    => '003'
            ];
        }
        $this->stmt = null;
    }

    /**
    * delete function use to delete record(s) in database based on given parameters and optionally conditions.
    * 
    * @param array $param
    * @return void
    */
    public function delete(array $param = array('table'=>'','condition'=>[])) {
        // (REQUIRED) name of table
        $tableName      = isset($param['table']) ? $param['table'] : '';
        // (OPTIONAL) multi dimensional array e.g. [['key','operator','value'],['key','operator','value'],...]
        $arrCondition   = isset($param['condition']) ? $param['condition'] : [];

        if( empty( $tableName ) ) {
            echo "table name missing";
            die;
        }

        $insert_values = [];
        $strWhere = "";

        if( count($arrCondition) > 0 ) {
            foreach( $arrCondition as $col ) {
                if( count( $col ) == 2 ) {
                    array_push( $insert_values, $col[1] );
                    $strWhere .= "`" . $col[0] . "` = ?";
                } else if( count( $col ) == 3 ) {
                    array_push( $insert_values, $col[2] );
                    $strWhere .= "`" . $col[0] . "` " . $col[1] . " ?";
                } else {
                    // error
                }
                $strWhere .= " AND ";
            }
            $strWhere = ' WHERE ' . substr($strWhere, 0, -5);
        }

        $strQry = "DELETE FROM `".$tableName."`" . $strWhere . ";";

        $pdo = $this->getConnection();
        $this->stmt = $pdo->prepare($strQry);
        $executed = $this->stmt->execute($insert_values);
        if( $executed ) {
            $count = $this->stmt->rowCount(); // effected rows if deleted records
            if( $count > 0 ) {
                echo $count . ' record(s) deleted successfully.';
            } else {
                echo 'fails to delete record(s)';
            }
        } else {
            echo 'fails to update record(s)';
        }
        $this->stmt = null;
    }

    /**
    * truncate function to truncate given table
    *
    * @param array $param
    * @return void
    */
    public function truncate(array $param = array('table'=>'')) {
        // (REQUIRED) name of table
        $tableName = isset($param['table']) ? $param['table'] : '';

        if( empty( $tableName ) ) {
            echo "table name missing";
            die;
        }

        $strQry = "TRUNCATE TABLE `".$tableName."`;";

        $pdo = $this->getConnection();
        $this->stmt = $pdo->prepare($strQry);
        $executed = $this->stmt->execute();
        if( $executed ) {
            echo 'table truncated successfully.';
        } else {
            echo 'fails to truncate table';
        }
        $this->stmt = null;
        die;
    }

    /**
    * rawExecute function to truncate given table
    *
    * @param array $param
    * @return void
    */
    public function rawExecute(array $param = array('query'=>'','arrData'=>[])) {
        $qry = isset($param['query']) ? $param['query'] : '';
        $arrData = isset($param['arrData']) ? $param['arrData'] : [];

        if( empty( $qry ) ) {
            echo "missing query";
            die;
        }
        
        $insert_values = [];

        if( !empty( $qry ) ) {
            $countQuestionMarks = 0;

            foreach( $qry AS $char ) {
                if( $char == "?" )
                    $countQuestionMarks++;
            }

            if( count($arrData) == $countQuestionMarks ) {
                echo "data count doesn't match with column count.";
                die;
            }

            if( count(  $arrData ) ) {
                $insert_values =  $arrData;
            }
        }

        $strQry = $qry;
        $pdo = $this->getConnection();
        $this->stmt = $pdo->prepare($strQry);

        if( count( $insert_values ) ) {
            $executed = $this->stmt->execute($insert_values);
        } else {
            $executed = $this->stmt->execute();
        }
        
        if( $executed ) {
            echo 'query executed successfully.';
        } else {
            echo 'fails to truncate table';
        }
        
        $this->stmt = null;
        die;
    }

    /**
    * rawFetch function to truncate given table
    *
    * @param array $param
    * @return void
    */
    public function rawFetch(array $param = array('query'=>'','arrData'=>[])) {
        $qry = isset($param['query']) ? $param['query'] : '';
        $arrData = isset($param['arrData']) ? $param['arrData'] : [];

        if( empty( $qry ) ) {
            echo "missing query";
            die;
        }
        
        $insert_values = [];
        
        $qry = trim(preg_replace('/\s+/', ' ', $qry));

        if( !empty( $qry ) ) {
            $countQuestionMarks = substr_count($qry,"?");

            if( count($arrData) != $countQuestionMarks ) {
                echo "data count doesn't match with column count.";
                die;
            }

            if( count(  $arrData ) ) {
                $insert_values =  $arrData;
            }
        }

        $strQry = $qry;
        $pdo = $this->getConnection();
        $this->stmt = $pdo->prepare($strQry);

        if( count( $insert_values ) ) {
            $executed = $this->stmt->execute($insert_values);
        } else {
            $executed = $this->stmt->execute();
        }
        
        if( $executed ) {
            $count = $this->stmt->rowCount(); // effected rows if updated with same value
            $result = $this->stmt->fetchAll();
            
            if( count($result) > 0 ) {
                // echo count($result) . ' record(s) found.<br>';
                // echo $count . ' record(s) found.<br>';
                // print_r( $result );
                return [
                    'success' => true,
                    'message' => $count . ' record(s) found.',
                    'data'    => $result,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'fails to get record(s)',
                    'data'    => null,
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'fails to get record(s)',
                'data'    => null,
            ];
        }

        $this->stmt = null;
        die;
    }

    /**
    * placeholders function is a helper function to generate question marks for prepare statements
    * 
    * @param string contains placeholder value, usually a question mark symbol "?"
    * @param int 
    * @param string
    */
    private function placeholders(string $text, int $count=0, string $separator=","){
        $result = [];
        if($count > 0){
            for($x=0; $x<$count; $x++){
                $result[] = $text;
            }
        }

        return implode($separator, $result);
    }

    public function vendorSpecificError($vendor,$errorCode) {
        return $errorCode;
    }

}


/**
 * Usage
 */

// $db_opt = array(
//     PDO::ATTR_EMULATE_PREPARES   => false, // turn off emulation mode for "real" prepared statements
//     PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // turn on errors in the form of exceptions
//     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // make the default fetch be an associative array
//     PDO::MYSQL_ATTR_FOUND_ROWS   => true, // 
// );

// $db = new Database("127.0.0.1","testpdo","root","",$db_opt);

// START TRANSECTION
// $db->startTransaction();

// FINISH TRANSECTION
// $db->finishTransaction();

// ROLLBACK TRANSECTION
// $db->rollbackTransaction();

// CREATE
// $param = [
//     'table' => 'test_table',
//     'columns' => ['name','email'],
//     'data' => ['hitesh6','email6@gmail.com'],
// ];
// $db->insert($param);


// CREATE MULTIPLE
// $param = [
//     'table' => 'test_table',
//     'columns' => ['name','email'],
//     'data' => [['hitesh2','email2@gmail.com'],['hitesh3','email3@gmail.com']],
// ];
// $db->insertMultiple($param);


// READ
// $param = [
//     'table' => 'test_table', // name of table
//     'columns' => [], // array of columns to be enter
//     'condition' => [], // multi dimensional array ['key','conditional statement like = or LIKE or <>','value']
//     'orderBy' => '',
//     'direction' => '', //  asc or desc
//     'offset' => 0, // default 0
//     'limit' => 0 // default 0
// ];
// $db->get($param);


// UPDATE
// $param = [
//     'table' => 'test_table', // name of table
//     'data' => ['name'=>'pppp','email'=>'pppp@pppp.pp'], // array of columns to be enter
//     'condition' => [['name','=','shubh']], // multi dimensional array ['key','conditional statement like = or LIKE or <>','value']
// ];
// $db->update($param);

// DELETE
// $param = [
//     'table' => 'test_table', // name of table
//     'condition' => [['name','=','hitesh2']], // multi dimensional array ['key','conditional statement like = or LIKE or <>','value']
// ];
// $db->delete($param);

// TRUNCATE
// $param = [
//     'table' => 'test_table'
// ];
// $db->truncate($param);

// RAW EXECUTE
// $param = [
//     'query' => '',
//     'arrData' => []
// ];
// $db->rawExecute($param);

// RAW FETCH
// $param = [
//     'query' => '',
//     'arrData' => []
// ];
// $db->rawFetch($param);