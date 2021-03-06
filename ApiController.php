<?php


class ApiController extends Controller
{
    // Members
    /**
     * Key which has to be in HTTP USERNAME and PASSWORD headers
     */
    Const APPLICATION_ID = 'YOUR_APP_ID';

    /**
     * Default response format
     */
    private $format = 'json';
    /**
     * @return array action filters
     */


    public function filters()
    {
        return array();
    }

    public function init()
    {
        parent::init();

        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }
        // Access-Control headers are received during OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }
        defined("CACHETIME") or define("CACHETIME", 60);
    }

    public function actionList()
    {
            // Get model instance
                switch ($_GET['model']) {
                    case 'MyModel':
                        $models = MyModel::model()->findAll();
                        break;
                    default:
                        $this->_sendResponse(501, sprintf(
                            'Error: Mode <b>list</b> is not implemented for model <b>%s</b>',
                            $_GET['model']), "text/html");
                        Yii::app()->end();
                }
            if (empty($models)) {
                $this->_sendResponse(200,
                    sprintf('No items where found for model <b>%s</b>', $_GET['model']), "text/html");
            } else {
                // Prepare response
                $rows = array();
                foreach ($models as $model)
                    $rows[] = $model->attributes;
                // Send the response
                $this->_sendResponse(200, CJSON::encode($rows));
            }
    }


    public function actionView()
    {
            //Check id-a from get
            if(!isset($_GET['id']))
                $this->_sendResponse(500, 'Error: Parameter <b>id</b> is missing' );

            switch($_GET['model'])
            {
                // Find respective model
                case 'MyModel':
                    $model = MyModel::model()->findByPk($_GET['id']);
                    break;
                default:
                    $this->_sendResponse(501, sprintf(
                        'Mode <b>view</b> is not implemented for model <b>%s</b>',
                        $_GET['model']) );
                    Yii::app()->end();
            }
            if(is_null($model))
                $this->_sendResponse(404, 'No Item found with id '.$_GET['id'], "text/html");
            else
                $this->_sendResponse(200, CJSON::encode($model));
    }


    public function actionCreate()
    {
            switch($_GET['model'])
            {
                // Get respective model
                case 'MyModel':
                    $model = new MyModel;
                    break;
                default:
                    $this->_sendResponse(501,
                        sprintf('Mode <b>create</b> is not implemented for model <b>%s</b>',
                            $_GET['model']), "text/html");
                    Yii::app()->end();
            }
            // Try to assign POST values to attributes
            foreach($_POST as $var=>$value) {
                if($model->hasAttribute($var))
                    $model->$var = $value;
                else
                    $this->_sendResponse(500,
                        sprintf('Parameter <b>%s</b> is not allowed for model <b>%s</b>', $var,
                            $_GET['model']), "text/html");
            }
            // Try to save the model
            if($model->save()){
                $this->_sendResponse(200, CJSON::encode($model));
            }
            else {
                // Errors occurred
                $msg = "<h1>Error</h1>";
                $msg .= sprintf("Couldn't create model <b>%s</b>", $_GET['model']);
                $msg .= "<ul>";
                foreach($model->errors as $attribute=>$attr_errors) {
                    $msg .= "<li>Attribute: $attribute</li>";
                    $msg .= "<ul>";
                    foreach($attr_errors as $attr_error)
                        $msg .= "<li>$attr_error</li>";
                    $msg .= "</ul>";
                }
                $msg .= "</ul>";
                $this->_sendResponse(500, $msg, "text/html");
            }
    }

    public function actionUpdate()
    {
            $json = file_get_contents('php://input');
            $put_vars = CJSON::decode($json,true);
            switch($_GET['model'])
            {
                // Find respective model
                case 'MyModel':
                    $model = MyModel::model()->findByPk($_GET['id']);
                    break;
                default:
                    $this->_sendResponse(501,
                        sprintf( 'Error: Mode <b>update</b> is not implemented for model <b>%s</b>',
                            $_GET['model']), "text/html");
                    Yii::app()->end();
            }

            if($model === null)
                $this->_sendResponse(400,
                    sprintf("Error: Didn't find any model <b>%s</b> with ID <b>%s</b>.",
                        $_GET['model'], $_GET['id']), "text/html");

            // assign PUT parameters to attributes
            foreach($put_vars as $var=>$value) {
                if($model->hasAttribute($var))
                    $model->$var = $value;
                else {
                    $this->_sendResponse(500,
                        sprintf('Parameter <b>%s</b> is not allowed for model <b>%s</b>',
                            $var, $_GET['model']), "text/html");
                }
            }
            if($model->save()) $this->_sendResponse(200, CJSON::encode($model));
            else $this->_sendResponse(500, "error", "text/html");
    }

    public function actionDelete()
    {
            switch($_GET['model'])
            {
                // Load the model
                case 'MyModel':
                    $model = MyModel::model()->findByPk($_GET['id']);
                    break;
                default:
                    $this->_sendResponse(501,
                        sprintf('Error: Mode <b>delete</b> is not implemented for model <b>%s</b>',
                            $_GET['model']), "text/html");
                    Yii::app()->end();
            }
            if($model === null)
                $this->_sendResponse(400,
                    sprintf("Error: Didn't find any model <b>%s</b> with ID <b>%s</b>.",
                        $_GET['model'], $_GET['id']), "text/html");

            // Delete the model
            $num = $model->delete();
            if($num>0)
                $this->_sendResponse(200, $num);
            else
                $this->_sendResponse(500,
                    sprintf("Error: Couldn't delete model <b>%s</b> with ID <b>%s</b>.",
                        $_GET['model'], $_GET['id']), "text/html");
    }




    private function _getStatusCodeMessage($status)
    {
        $codes = Array(
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
        );
        return (isset($codes[$status])) ? $codes[$status] : '';
    }



    private function _sendResponse($status = 200, $body = '', $content_type = 'application/json')
    {
        // set the status
        $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->_getStatusCodeMessage($status);
        header($status_header);
        // and the content type
        header('Content-type: ' . $content_type);

        if($body != '')
        {
            // send the body
            echo $body;
        }
        else{

            $message = '';

            switch($status)
            {
                case 401:
                    $message = 'You must be authorized to view this page.';
                    break;
                case 404:
                    $message = 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found.';
                    break;
                case 500:
                    $message = 'The server encountered an error processing your request.';
                    break;
                case 501:
                    $message = 'The requested method is not implemented.';
                    break;
            }

            $signature = ($_SERVER['SERVER_SIGNATURE'] == '') ? $_SERVER['SERVER_SOFTWARE'] . ' Server at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];

            // this should be templated
             $body = '
            <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
                <title>' . $status . ' ' . $this->_getStatusCodeMessage($status) . '</title>
            </head>
            <body>
                <h1>' . $this->_getStatusCodeMessage($status) . '</h1>
                <p>' . $message . '</p>
                <hr />
                <address>' . $signature . '</address>
            </body>
            </html>';

                        echo $body;
        }
        Yii::app()->end();
    }


    //For later authorization
    // --- use $this->_checkAuth(); in if statement to check validity
    private function _checkAuth()
    {
        // Check if we have the USERNAME and PASSWORD HTTP headers set
        if(!(isset($_SERVER['X_USERNAME']) && isset($_SERVER['X_PASSWORD']))) {
            // Error: Unauthorized:
            $this->_sendResponse(401);
        }
        $username = $_SERVER['X_USERNAME'];
        $password = $_SERVER['X_PASSWORD'];
        // Find the user
        $user=User::model()->find('LOWER(username)=?',array(strtolower($username)));
        if($user===null) {
            // Error: Unauthorized
            $this->_sendResponse(401, 'Error: Name is invalid');
        } else if(!$user->validatePassword($password)) {
            // Error: Unauthorized
            $this->_sendResponse(401, 'Error: Password is invalid');
        }
    }


}