<?php

namespace Mall\Framework\Core;

class AutoLoad
{

    static function _autoLoad($class)
    {
        //var_dump($class);

        //xssCheck();
        $class_path = str_replace('\\', '/', $class) . '.Class.php';
        $real_path = substr($class_path, strpos($class_path, '/'));

        if (strpos($class_path, FRAMEWORK_NAME.'/') !== FALSE) {

            $file_path = ROOT_PATH . $real_path;

        } else if (strpos($class_path, 'Smarty') !== FALSE) {

            return false;

        } else if (strpos($class_path, 'Service') !== FALSE) {

            $file_path = ROOT_PATH . DS . '..' . DS . 'Service' . $real_path;

        } else {

            $file_path = PROJECT_PATH . $real_path;

        }

        loadFile($file_path);
    }


}