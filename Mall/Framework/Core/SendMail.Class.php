<?php

namespace Mall\Framework\Core;

use Mall\Framework\Mail\Mail;

class SendMail {

    private static $_instance;

    protected $error;

    static public function getInstance()
    {
        $key = md5('sendmail');

        if (!self::$_instance[$key] instanceof self) {
            self::$_instance[$key] = new self;
        }

        return self::$_instance[$key];
    }

    public function mail($title, $email, $html, $sign=null)
    {
        $key = md5('mail');

        if (!self::$_instance[$key] instanceof self) {
            $options = Config::getInstance()->get('mail');
            self::$_instance[$key] = new Mail($options['mailer'], $options['delimiter'], $options['charset'], $options['from'], $options['sign'], $options['smtp_host'], $options['smtp_port'], $options['smtp_auth'], $options['smtp_username'], $options['smtp_password']);
        }

        self::$_instance[$key]->execute($email, $title, $html);
        if ($this->error = self::$_instance[$key]->error()) {
            return false;
        }

        return true;
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * 邮箱验证码
     * @param $nickname
     * @param $code
     * @return string
     */
    public function emailCode($nickname, $code)
    {
        return "
             <div style='width:700px;height:240px;padding:0 30px;color:#444;font-size:15px;padding-top:20px;'>
                <p style='font-size: 16px;'>
                    亲爱的{$nickname}：
                </p>
                <div style='width:630px;margin-top:20px;color:#444;word-wrap:break-word;'>
                    <p style='margin-top:20px;'>您的验证码为：{$code}</p>
                </div>
            </div>
        ";
    }

    /**
     * 重置密码
     * @param $nickname
     * @param $url
     * @return string
     */
    public function userResetPw($nickname, $url)
    {
        return "
             <div style='width:700px;height:240px;padding:0 30px;color:#444;font-size:15px;padding-top:10px;'>
                <h4 style='font-size: 15px; padding:0; margin:0;'>亲爱的 {$nickname}，您好：</h4>
                <p style='font-size: 14px; margin: 3px 0; padding-left:30px;'>您的账号正在进行找回密码操作！</p>
                <div style='width:630px;margin-top:20px; padding-left:30px;color:#444;word-wrap:break-word;'>
                    <p style='text-align: center;width:630px;'>
                        <a href='" . $url . "' style='margin-left:220px;padding: 6px; color:white;text-decoration: none;width:130px;height:30px;display:block;background: #65A8FF;border-radius:10px;'>
                            <span style='vertical-align: middle;'>点击重置密码</span>
                        </a>
                    </p>
                    <div style='border-left: 5px solid #007AFF;padding-left:10px; font-size: 14px;'>
                        <p style='margin:0; padding:0;'>如果您点击上述链接无效，请将下面的链接复制到浏览器地址栏中访问：</p>
                        <p>{$url}</p>
                    </div>
                    <p style='color:grey;margin:0;'>
                        <span>为保障您的账号安全性，以上链接有效期为 3 天。</span><br/>
                    </p>
                </div>
            </div>
        ";
    }

    public function html($content, $sitename, $info)
    {
        $logo = $logo = PROJECT_IMG_DOMAIN . "assets/mail/img/logo.png";
        $copyright = $copyright = "Copyright © 2017 北京猫逛科技有限公司";
        $sitename = $sitename ?: '北京猫逛科技有限公司';


        return
            "<html xmlns='http://www.w3.org/1999/xhtml'>
            <head>
                <meta http-equiv='Content-Type' content='text/html; charset=3DUTF-8'/>
                <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
                <title>{$sitename}</title>
            </head>
            <body style='margin:0;padding:0;'>
            <table width='900' cellpadding='0' cellspacing='0' border='0'>
                <tr>
                    <td style='width:900px;background-color:#f5f3f0;'>
                        <div style='padding:50px 100px;'>
                            <div style='width:700px;min-height:600px; _height:600px;background-color:#fff;font-family:微软雅黑;overflow:hidden;'>
                                <div style='width:600px;height:80px;'>
                                    <div style='float:left;'>
                                        <img src='{$logo}' width='245' height='30' style='padding:25px 0 0 30px;' />
                                    </div>
                                    <div style='padding:32px 0 0 280px;'>
                                        <span>这里是占位符</span>
                                    </div>
                                </div>
                                <div style='width:700px;height:200px;padding:0 30px;'>
                                    <div style='width:640px;height:200px;background-image:url(http://www{$domainSuffix}/assets/mail/img/mail.png);'>
                                       <div style='width:545px;height:200px;padding-top:70px;margin-left:95px;overflow:hidden;'>
                                            <span style='padding-left:130px;font-size:32px;color:#fff;font-weight:bold;white-space:nowrap;'>{$sitename}</span>
            <br />
            <span style='padding-left:132px;font-size:18px;color:#feebeb;'>{$info}</span>
            </div>
            </div>
            </div>
            {$content}
            <div style='width:700px;height:30px;padding-left:500px;margin-top:20px;;color:#444;'>
                <span>{$sitename}团队</span>
            </div>
            </div>
            <div style='text-align:center;color:#444;font-size:12px;font-family:微软雅黑;margin-top:20px;padding-top:20px;'>
                {$copyright}
            </div>
            </div>
            </td>
            </tr>
            </table>
            </body>
            </html>";
    }
}