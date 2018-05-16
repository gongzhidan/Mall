<?php

namespace Mall\Framework\Mail;

class Mail
{
    const PHP_QPRINT_MAXL=75;

    public $mailer,
        $delimiter,
        $charset,
        $from,
        $sign,
        $smtp_host,
        $smtp_port,
        $smtp_auth,
        $smtp_username,
        $smtp_password;

    protected $errno;

    protected $error;

    function __construct($mailer = 1, $delimiter = 1, $charset = 'utf-8', $from = null, $sign = null, $smtp_host = null, $smtp_port = 25, $smtp_auth = true, $smtp_username = null, $smtp_password = null)
    {
        $this->mailer = $mailer;
        $this->delimiter = $delimiter == 1 ? "\r\n" : ($delimiter == 2 ? "\r" : "\n");
        $this->charset = $charset;
        $this->from = $from;
        $this->sign = $sign;
        $this->smtp_host = $smtp_host;
        $this->smtp_port = $smtp_port ? $smtp_port : 25;
        $this->smtp_auth = $smtp_auth;
        $this->smtp_username = $smtp_username;
        $this->smtp_password = $smtp_password;
    }

    function execute($to, $subject, $message, $from = null)
    {
        $subject = '=?'.$this->charset.'?B?'.base64_encode(str_replace("\r", '', str_replace("\n", '', $subject))).'?=';
        $message .= $this->sign;
        $message = str_replace("\r\n.", " \r\n..", str_replace("\n", "\r\n", str_replace("\r", "\n", str_replace("\r\n", "\n", str_replace("\n\r", "\r", $message)))));
        $message = $this->leading_dot_fixed_php_quot_print_encode($message);
        $from = is_null($from) ? '=?'.$this->charset.'?B?'.base64_encode('MailGuang')."?= <$this->from>" : (preg_match('/^(.+?) \<(.+?)\>$/', $from, $m) ? '=?'.$this->charset.'?B?'.base64_encode($m[1])."?= <$m[2]>" : $from);
        if (strpos($to, ','))
        {
            foreach(explode(',', $to) as $touser)
            {
                $tousers[] = preg_match('/^(.+?) \<(.+?)\>$/', $touser, $m) ? '=?'.$this->charset.'?B?'.base64_encode($m[1])."?= <$m[2]>" : $touser;
            }
            $to = implode(',', $tousers);
        }
        $headers = "From: $from{$this->delimiter}X-Priority: 3{$this->delimiter}X-Mailer: MailGuang mcloud{$this->delimiter}MIME-Version: 1.0{$this->delimiter}Content-type: text/html; charset=$this->charset{$this->delimiter}";
        $headers .= "Content-Transfer-Encoding: quoted-printable{$this->delimiter}";

        if ($this->mailer == 1)
        {
            return mail($to, $subject, $message, $headers);
        }
        elseif ($this->mailer == 2)
        {
            return $this->smtp($to, $subject, $message, $headers, $from);
        }
        else
        {
            return $this->mail($to, $subject, $message, $headers, $from);
        }
    }

    function leading_dot_fixed_php_quot_print_encode($str)
    {
        $lp = 0;
        $ret = '';
        $hex = "0123456789ABCDEF";
        $length = strlen($str);
        $str_index = 0;

        while ($length--) {
            if ((($c = $str[$str_index++]) == "\015") && ($str[$str_index] == "\012") && $length > 0) {
                $ret .= "\015";
                $ret .= $str[$str_index++];
                $length--;
                $lp = 0;
            } else {
                if (ctype_cntrl($c)
                    || (ord($c) == 0x7f)
                    || (ord($c) & 0x80)
                    || ($c == '=')
                    || (($c == ' ') && ($str[$str_index] == "\015")))
                {
                    if (($lp += 3) > self::PHP_QPRINT_MAXL)
                    {
                        $ret .= '=';
                        $ret .= "\015";
                        $ret .= "\012";
                        $lp = 3;
                    }
                    $ret .= '=';
                    $ret .= $hex[ord($c) >> 4];
                    $ret .= $hex[ord($c) & 0xf];
                }
                else
                {
                    if ((++$lp) > self::PHP_QPRINT_MAXL)
                    {
                        $ret .= '=';
                        $ret .= "\015";
                        $ret .= "\012";
                        $lp = 1;
                    }
                    $ret .= $c;
                    if($lp == 1 && $c == '.') {
                        $ret .= '.';
                        $lp++;
                    }
                }
            }
        }

        return $ret;
    }

    function mail($to, $subject, $message, $headers, $from)
    {
        ini_set('SMTP', $this->smtp_host);
        ini_set('smtp_port', $this->smtp_port);
        ini_set('sendmail_from', $from);
        return mail($to, $subject, $message, $headers);
    }

    function smtp($to, $subject, $message, $headers, $from)
    {
        if(!$fp = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 30))
        {
            $this->errno = $errno;
            $this->error = $errstr;
            return false;
        }

        stream_set_blocking($fp, true);
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != '220')
        {
            $this->errno = substr($lastmessage, 0, 3);
            $this->error = $lastmessage;
            return false;
        }

        fputs($fp, ($this->smtp_auth ? 'EHLO' : 'HELO')." MailGuang\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 220 && substr($lastmessage, 0, 3) != 250)
        {
            $this->errno = substr($lastmessage, 0, 3);
            $this->error = $lastmessage;
            return false;
        }

        while(1)
        {
            if((substr($lastmessage,0, 3) != 220 && substr($lastmessage, 3, 1) != '-') || empty($lastmessage)) break;
            $lastmessage = fgets($fp, 512);
        }

        if($this->smtp_auth)
        {
            fputs($fp, "AUTH LOGIN\r\n");
            $lastmessage = fgets($fp, 512);
            if(substr($lastmessage, 0, 3) != 334)
            {
                $this->errno = substr($lastmessage, 0, 3);
                $this->error = $lastmessage;
                return false;
            }

            fputs($fp, base64_encode($this->smtp_username)."\r\n");
            $lastmessage = fgets($fp, 512);
            if(substr($lastmessage, 0, 3) != 334)
            {
                $this->errno = substr($lastmessage, 0, 3);
                $this->error = $lastmessage;
                return false;
            }

            fputs($fp, base64_encode($this->smtp_password)."\r\n");
            $lastmessage = fgets($fp, 512);
            if(substr($lastmessage, 0, 3) != 235)
            {
                $this->errno = substr($lastmessage, 0, 3);
                $this->error = $lastmessage;
                return false;
            }
        }

        fputs($fp, "MAIL FROM: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $from).">\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 250)
        {
            fputs($fp, "MAIL FROM: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $from).">\r\n");
            $lastmessage = fgets($fp, 512);
            if(substr($lastmessage, 0, 3) != 250)
            {
                $this->errno = substr($lastmessage, 0, 3);
                $this->error = $lastmessage;
                return false;
            }
        }

        $email_tos = array();
        foreach(explode(',', $to) as $touser)
        {
            $touser = trim($touser);
            if($touser)
            {
                fputs($fp, "RCPT TO: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $touser).">\r\n");
                $lastmessage = fgets($fp, 512);
                if(substr($lastmessage, 0, 3) != 250)
                {
                    fputs($fp, "RCPT TO: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $touser).">\r\n");
                    $lastmessage = fgets($fp, 512);
                    $this->errno = substr($lastmessage, 0, 3);
                    $this->error = $lastmessage;
                    return false;
                }
            }
        }

        fputs($fp, "DATA\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 354)
        {
            $this->errno = substr($lastmessage, 0, 3);
            $this->error = $lastmessage;
            return false;
        }

        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } else if (isset($_SERVER['HOSTNAME'])) {
            $host = $_SERVER['HOSTNAME'];
        } else {
            $host = '';
        }

        $headers .= 'Message-ID: <'.gmdate('YmdHs').'.'.substr(md5($message.microtime()), 0, 6).rand(100000, 999999).'@'.$host.">{$this->delimiter}";

        fputs($fp, "Date: ".gmdate('r')."\r\n");
        fputs($fp, "To: ".$to."\r\n");
        fputs($fp, "Subject: ".$subject."\r\n");
        fputs($fp, $headers."\r\n");
        fputs($fp, "\r\n\r\n");
        fputs($fp, "$message\r\n.\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 250)
        {
            $this->errno = substr($lastmessage, 0, 3);
            $this->error = $lastmessage;
            return false;
        }
        fputs($fp, "QUIT\r\n");
        return true;
    }

    function error()
    {
        return $this->error;
    }

    function errno()
    {
        return $this->errno;
    }
}