<?php

require_once('./config.php');
require_once('./class.database.php');

// get endpoint from request
$endpoint = isset($_REQUEST['endpoint']) ? getSanitizedData($_REQUEST['endpoint']) : '';

if (!empty($endpoint)) {
    switch ($endpoint) {
        case 'add_new_user':
            // get data if available
            $name       = isset($_REQUEST['name']) ? getSanitizedData($_REQUEST['name']) : '';
            $age        = isset($_REQUEST['age']) ? getSanitizedData($_REQUEST['age']) : '';
            $address    = isset($_REQUEST['address']) ? getSanitizedData($_REQUEST['address']) : '';

            // check for required fields and send response if required fields are missing
            if(empty($name) && empty($age) && empty($address)){
                $arrResponse = [
                    'message'   =>  'required parameter are missing',
                    'success'   =>  true,
                    'error'     =>  null,
                    'data'      =>  null,
                    'code'      =>  '001',
                ];
                header($_SERVER['SERVER_PROTOCOL'] . " 400 BAD REQUEST");
                header('Content-Type: application/json');
                echo encodeJavaScriptString($arrResponse);
                exit();
            }

            // insert data in database
            $db = new Database($GLOBALS['db_host'],$GLOBALS['db_name'],$GLOBALS['db_user'],$GLOBALS['db_pass'],$GLOBALS['db_opt']);
            $arrUser=[
               'name'=> $name,
               'age'=> $age,
               'address'=> $address,
               'points' => 0,
               'status' => 1,
            ];  
            $param = [
                'table' => 'users',
                'columns' =>  array_keys($arrUser),
                'data'=> array_values($arrUser),
            ];
            $inserted = $db->insert($param);

            // API response
            if ( $inserted['success'] ) {
                $arrResponse = [
                    'message'   => $name.' added successfully',
                    'success'   =>  true,
                    'error'     =>  null,
                    'data'      =>  null,
                    'code'      =>  '002',
                ];
                header($_SERVER['SERVER_PROTOCOL'] . " 200 OK");
                header('Content-Type: application/json');
                echo encodeJavaScriptString($arrResponse);
                exit();
            } else {
                $arrResponse = [
                    'message'   =>  'Fails to add user, try again.',
                    'success'   =>  false,
                    'error'     =>  null,
                    'data'      =>  null,
                    'code'      =>  '003',
                ];
                header($_SERVER['SERVER_PROTOCOL'] . " 503 Service Unavailable");
                header('Content-Type: application/json');
                echo encodeJavaScriptString($arrResponse);
                exit(); 
            }
            
            break;
        case 'view_user':
            // receive value from request
            $user_id = isset($_REQUEST['user_id']) ? getSanitizedData($_REQUEST['user_id']) : '';

            // check for required fields and send response if required fields are missing
            if(empty($user_id)){
                $arrResponse = [
                    'message'   =>  'required parameter are missing',
                    'success'   =>  true,
                    'error'     =>  null,
                    'data'      =>  null,
                    'code'      =>  '001',
                ];
                header($_SERVER['SERVER_PROTOCOL'] . " 400 Bad Request");
                header('Content-Type: application/json');
                echo encodeJavaScriptString($arrResponse);
                exit();
            }
            
            // fetch data for given user from API
            $db = new Database($GLOBALS['db_host'],$GLOBALS['db_name'],$GLOBALS['db_user'],$GLOBALS['db_pass'],$GLOBALS['db_opt']);
            $qry="SELECT * from `users` WHERE `id`=? AND `status`=1";
            $data = $db->rawFetch([
                'query' => $qry, // raw query
                'arrData' => [$user_id] // data array
            ]);
            
            if ($data['success']) {
                $arrResponse = [
                    'message'   =>  'User records',
                    'success'   =>  true,
                    'error'     =>  null,
                    'data'      =>  $data['data'][0],
                    'code'      =>  '002',
                ];
                header($_SERVER['SERVER_PROTOCOL'] . " 200 OK");
                header('Content-Type: application/json');
                echo encodeJavaScriptString($arrResponse);
                exit();
            } else {
                $arrResponse = [
                    'message'   =>  'No records are found',
                    'success'   =>  false,
                    'error'     =>  null,
                    'data'      =>  null,
                    'code'      =>  '003',
                ];
                header($_SERVER['SERVER_PROTOCOL'] . " 503 Service Unavailable");
                header('Content-Type: application/json');
                echo encodeJavaScriptString($arrResponse);
                exit();
            }
            break;
        case 'add_point':
            // fetch data from request
            $user_id = isset($_REQUEST['user_id']) ? getSanitizedData($_REQUEST['user_id']) : '';

            // check for required fields and send response if required fields are missing
            if(empty($user_id)){
                $arrResponse = [
                    'message'   =>  'required parameter are missing',
                    'success'   =>  true,
                    'error'     =>  null,
                    'data'      =>  null,
                    'code'      =>  '001',
                ];
                header($_SERVER['SERVER_PROTOCOL'] . " 400 Bad Request");
                header('Content-Type: application/json');
                echo encodeJavaScriptString($arrResponse);
                exit();
            }

            // 
            $db = new Database($GLOBALS['db_host'],$GLOBALS['db_name'],$GLOBALS['db_user'],$GLOBALS['db_pass'],$GLOBALS['db_opt']);

            $qrySession = "SELECT * FROM `users` WHERE `id` = ? AND`status`=1";
            $params = [
                'query'     =>  $qrySession,
                'arrData'   =>  [$user_id],
            ];
            $res = $db->rawFetch($params);

            if (empty($res)) {
                $arrResponse = [
                    'success'   =>  'success',
                    'message'   =>  'user does not exist',
                    'error'     =>  null,
                    'data'      =>  null,
                    'code'      =>  '002',
                ];
                header($_SERVER['SERVER_PROTOCOL'] . " 204 No Content");
            } else {
                // update score by incrementing it by one
                $score=$res['data'][0]['points']+1;
                $param = [
                    'table' => 'users', // name of table
                    'data' => ['points' => $score], // array of columns to be enter
                    'condition' => [['id', '=', $user_id]], // multi dimensional array ['key','conditional statement like = or LIKE or <>','value']
                ];
                $db->update($param);

                // get all users order by points
                $qry="SELECT * FROM `users` WHERE `status`=1 ORDER BY `points` desc;";
                $params = [
                    'query'     =>  $qry,
                    'arrData'   =>  [],
                ];
                $res = $db->rawFetch($params);

                // respond to API
                if (!empty($res)) {
                    $arrResponse = [
                        'success'   =>  'success',
                        'message'   =>  'Ordered points',
                        'error'     =>  null,
                        'data'      =>  $res['data'],
                        'code'      =>  '003',
                    ];
                    header($_SERVER['SERVER_PROTOCOL'] . " 200 OK");
                }else{
                    $arrResponse = [
                        'success'   =>  'success',
                        'message'   =>  'No record found',
                        'error'     =>  null,
                        'data'      =>  null,
                        'code'      =>  '004',
                    ];
                    header($_SERVER['SERVER_PROTOCOL'] . " 503 Service Unavailable");
                }         
            }
            header('Content-Type: application/json');
            echo encodeJavaScriptString($arrResponse);
            exit();
            
            break;
        case 'substract_point':
            // fetch data from request
            $user_id = isset($_REQUEST['user_id']) ? getSanitizedData($_REQUEST['user_id']) : '';

            // check for required fields and send response if required fields are missing
            if(empty($user_id)){
                $arrResponse = [
                    'message'   =>  'required parameter are missing',
                    'success'   =>  true,
                    'error'     =>  null,
                    'data'      =>  null,
                    'code'      =>  '001',
                ];
                header($_SERVER['SERVER_PROTOCOL'] . " 400 BAD REQUEST");
                header('Content-Type: application/json');
                echo encodeJavaScriptString($arrResponse);
                exit();
            }

            // get user from database
            $db = new Database($GLOBALS['db_host'],$GLOBALS['db_name'],$GLOBALS['db_user'],$GLOBALS['db_pass'],$GLOBALS['db_opt']);
            $qrySession = "SELECT * FROM `users` WHERE `id` = ? AND `status`=1";
            $params = [
                        'query'     =>  $qrySession,
                        'arrData'   =>  [$user_id],
            ];
            $res = $db->rawFetch($params);
            
            if (empty($res)) {
                $arrResponse = [
                    'success'   =>  'success',
                    'message'   =>  'user does not exist',
                    'error'     =>  null,
                    'data'      =>  null,
                    'code'      =>  '002',
                ];
                header($_SERVER['SERVER_PROTOCOL'] . " 204 No Content");
            } else {
                // decrement score by one and update in database
                $score=$res['data'][0]['points']-1;
                $param = [
                    'table' => 'users', // name of table
                    'data' => ['points' => $score], // array of columns to be enter
                    'condition' => [['id', '=', $user_id]], // multi dimensional array ['key','conditional statement like = or LIKE or <>','value']
                ];
                $db->update($param);

                // fetch users from database in order of points
                $qry="SELECT * FROM `users` WHERE `status`=1 ORDER BY `points` desc;";
                $params = [
                    'query'     =>  $qry,
                    'arrData'   =>  [],
                ];
                $res = $db->rawFetch($params);

                // respond to API
                if (!empty($res)) {
                    $arrResponse = [
                        'success'   =>  'success',
                        'message'   =>  'Ordered points',
                        'error'     =>  null,
                        'data'      =>  $res['data'],
                        'code'      =>  '003',
                    ];
                    header($_SERVER['SERVER_PROTOCOL'] . " 200 OK");
                }else{
                    $arrResponse = [
                        'success'   =>  'success',
                        'message'   =>  'No record found',
                        'error'     =>  null,
                        'data'      =>  null,
                        'code'      =>  '004',
                    ];
                    header($_SERVER['SERVER_PROTOCOL'] . " 503 Service Unavailable");
                }         
            }
            header('Content-Type: application/json');
            echo encodeJavaScriptString($arrResponse);
            exit(); 
        break;
        case'delete_user':
            $user_id  = isset($_REQUEST['user_id']) ? getSanitizedData($_REQUEST['user_id']) : '';
            $db = new Database($GLOBALS['db_host'],$GLOBALS['db_name'],$GLOBALS['db_user'],$GLOBALS['db_pass'],$GLOBALS['db_opt']);
            $qrySession = "SELECT * FROM `users` WHERE `id` = ? AND `status`=1";
            $params = [
                        'query'     =>  $qrySession,
                        'arrData'   =>  [$user_id],
            ];
            $res = $db->rawFetch($params);
            if (empty($res)) {
                $arrResponse = [
                    'success'   =>  'success',
                    'message'   =>  'user does not exist',
                    'error'     =>  null,
                    'data'      =>  null,
                    'code'      =>  '002',
                ];
                header($_SERVER['SERVER_PROTOCOL'] . " 204 No Content");
            } else {
                // dsable the status of user database
           
                $param = [
                    'table' => 'users', // name of table
                    'data' => ['status'=>0], // array of columns to be enter
                    'condition' => [['id', '=', $user_id]], // multi dimensional array ['key','conditional statement like = or LIKE or <>','value']
                ];
                $res=$db->update($param);
                if($res){
                    $arrResponse = [
                        'success'   =>  'success',
                        'message'   =>  'user deleted successfully',
                        'error'     =>  null,
                        'data'      =>  null,
                        'code'      =>  '003',
                    ];
                    header($_SERVER['SERVER_PROTOCOL'] . " 200 OK");
                    //exit();
                }
                else{
                    $arrResponse = [
                        'success'   =>  'success',
                        'message'   =>  'Something went wrong',
                        'error'     =>  null,
                        'data'      =>  null,
                        'code'      =>  '003',
                    ];
                    header($_SERVER['SERVER_PROTOCOL'] . " 200 OK");
                }
                header('Content-Type: application/json');
                echo encodeJavaScriptString($arrResponse);
                exit(); 
            }
            break;

        default:
            $arrResponse = [
                'message'   =>  'no such api endpoint found',
                'success'   =>  false,
                'error'     =>  null,
                'data'      =>  null,
                'code'      =>  '998',
            ];
            header($_SERVER['SERVER_PROTOCOL'] . " 400 Bad Request");
            header('Content-Type: application/json');
            echo encodeJavaScriptString($arrResponse);
            break;
    }
} else {
    $arrResponse = [
        'message'   =>  'bad request',
        'success'   =>  false,
        'error'     =>  null,
        'data'      =>  null,
        'code'      =>  '999',
    ];
    header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");
    header('Content-Type: application/json');
    echo encodeJavaScriptString($arrResponse);
    exit();
}


/**
 * Escapes HTML tags to protect against Cross-Site Scripting (XSS) attacks.
 *
 * @param $val
 * @return string
 */
function getSanitizedData($val){
    return isset($val) ? htmlspecialchars( trim($val), ENT_QUOTES, 'UTF-8') : '';
}


/**
 * Escapes user input that is to be interpolated into JavaScript code to
 * protect against Cross-Site Scripting (XSS) attacks.
 * 
 * @param $value
 * @return string
 */
function encodeJavaScriptString($value)
{
    return json_encode($value, JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS);
}

/**
 * Escapes a string in order protect against a SQL injection attack. If `$like` is true,
 * underscores and percent signs are also escaped for use in a`LIKE` clause.
 *
 * @param $value
 * @param \mysqli $mysqli
 * @param bool $like
 * @return string
 */
function escapeSqlString($value, \mysqli $mysqli, $like = false)
{
    return $like ? addcslashes($mysqli->real_escape_string($value), '%_')
        : $mysqli->real_escape_string($value);
}

// https://websitebeaver.com/php-pdo-prepared-statements-to-prevent-sql-injection
// global custom exception handler
set_exception_handler(function($e) {
    // error_log($e->getMessage());
    // echo 'STACH TRACE';
    // echo '<br>';
    // echo $e->getTraceAsString();
    // echo '<br>';

    exit('Something weird happened!'); //something a user can understand

});




