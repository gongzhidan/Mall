<?php

namespace Mall\Framework\Core;

class BaseImg
{
    // 上传文件名
    protected $saveName;
    
    // 上传Base64文件
    protected $file;

    // 上传的文件正则匹配后的信息
    protected $info;

    // 错误信息
    private $error = '';

    protected static $_instance;

    private function __construct(){}

    public static function getInstance()
    {
        $key = md5('baseImg64');

        if (!isset(self::$_instance[$key])) {
            self::$_instance[$key] = new self();
        }

        return self::$_instance[$key];
    }

    /**
     * @param string $file base64的图片
     * @param  bool $replace 同名文件是否覆盖
     * @param string $dirName 制定目录名称
     *
     * @return bool|File
     */
    public function move($file, $replace = true, $dirName = '')
    {
        $this->file = $file;
        $path = UPLOAD_FILE_PATH;

        // 检测合法性
        if (!$this->baseIsValid()) {
            $this->error = '非法上传文件';
            return false;
        }

        // 验证上传
        if (!$this->baseCheckImg($this->info[2])) {
            $this->error = '非法图像文件';
            return false;
        }

        $path = rtrim($path, DS) . DS;
        // 文件保存命名规则
        $saveName = $this->baseBuildSaveName($dirName);
        $filename = $path . $saveName;

        // 检测目录
        if (false === $this->checkPath(dirname($filename))) {
            return false;
        }

        /* 不覆盖同名文件 */
        if (!$replace && is_file($filename)) {
            $this->error = '存在同名文件' . $filename;
            return false;
        }

        if(!file_put_contents($filename, base64_decode(str_replace($this->info[1], '', $file)))){
            $this->error = '图像上传失败';
            return false;
        }

        // 返回 File对象实例
        $this->setSaveName($saveName);
        return $this;
    }

    protected function baseIsValid()
    {
        preg_match('/^(data:\s*image\/(\w+);base64,)/', $this->file, $result);

        $this->info = $result;

        return $result ? true : false;
    }

    protected function baseCheckImg($extension)
    {
        if (!in_array($extension, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'], true)) {
            return false;
        }

        return true;
    }

    protected function baseBuildSaveName($dirName = '')
    {
        if($dirName){
            return $dirName .DS. date('Ymd') . DS . md5(microtime(true)) . '.' . $this->info[2];
        }
        return date('Ymd') . DS . md5(microtime(true)) . '.' . $this->info[2];
    }

    /**
     * 检查目录是否可写
     * @param  string $path 目录
     * @return boolean
     */
    protected function checkPath($path)
    {
        if (is_dir($path)) {
            return true;
        }

        if (mkdir($path, 0755, true)) {
            return true;
        } else {
            $this->error = "目录 {$path} 创建失败！";
            return false;
        }
    }

    /**
     * 获取上传文件的文件名
     * @return string
     */
    public function getSaveName()
    {
        return $this->saveName;
    }

    /**
     * 设置上传文件的保存文件名
     * @param  string $saveName
     * @return $this
     */
    public function setSaveName($saveName)
    {
        $this->saveName = $saveName;
        return $this;
    }

    /**
     * 获取错误信息
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    public function baseImgMove($thumbs, $selfUploadUrl = '')
    {
        $actionImg = [];

        if (!empty($thumbs)) {
            if (is_array($thumbs) && !empty($thumbs)) {
                foreach ($thumbs as $thumb) {
                    if ($thumb) {
                        if ($path = self::_baseImgValidate($thumb, $selfUploadUrl)) {
                            $actionImg[] = $path;
                        }
                    }
                }
            } else {
                if (is_string($thumbs)) {
                    if ($path = self::_baseImgValidate($thumbs, $selfUploadUrl)) {
                        $actionImg[] = $path;
                    }
                }
            }
        }

        return json_encode($actionImg);
    }

    private function _baseImgValidate($thumb, $selfUploadUrl)
    {
        if(filter_var($thumb, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED)) {
            preg_match('#^'.preg_quote($selfUploadUrl).'#', $thumb, $myThumb);
            if ($myThumb) {
                $actionImg = str_replace($selfUploadUrl, '', $thumb);
            } else {
                $actionImg = $thumb;
            }
        } else {
            if ($file = self::move($thumb)) {
                $actionImg = $file->getSaveName();
            } else {
                $actionImg = $thumb;
            }
        }

        return $actionImg ? $actionImg : false;
    }
}