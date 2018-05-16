<?php

namespace Mall\Framework\Core;

require_once ROOT_PATH . DS . 'Framework/Smarty/libs/Smarty.class.php';

use Mall\Framework\Core\Config;

class View {

    protected $template;
    protected $smartyConfig;

    private static $_instance;

    private function __construct($options = [])
    {
        $this->template = new \Smarty();
        $this->smartyConfig = $options ?: Config::getInstance()->get('smarty');

        $this->template
            ->setTemplateDir($this->smartyConfig['template_dir'])
            ->setCompileDir($this->smartyConfig['compile_dir'])
            ->setCacheDir($this->smartyConfig['cache_dir']);

        $this->template->caching = $this->smartyConfig['is_caching'];

        $this->template->left_delimiter="<{";
        $this->template->right_delimiter="}>";
    }

    public static function getInstance($options = [])
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self($options);
        }

        return self::$_instance;
    }

    public function assign($tpl_var, $value = null, $nocache = false)
    {
        $this->template->assign($tpl_var, $value, $nocache);
    }

    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        $template = explode('.', $template)[0] . $this->smartyConfig['tpl_type'];

        $this->template->display($template, $cache_id, $compile_id, $parent);
    }

    public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        $template = explode('.', $template)[0] . $this->smartyConfig['tpl_type'];

        return $this->template->fetch($template, $cache_id, $compile_id, $parent);
    }

}