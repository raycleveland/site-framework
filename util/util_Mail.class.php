<?php

/**
 * Mail Mime Wrapper class for easy access
 * The main purpose is to properly include the mail mime package
 */

$mailMimePath = dirname(dirname(__FILE__)) 
    . DIRECTORY_SEPARATOR . 'PEAR' . DIRECTORY_SEPARATOR . 'Mail_Mime';
set_include_path(get_include_path() . PATH_SEPARATOR . $mailMimePath);

class_exists('Mail_mime', FALSE) || require($mailMimePath . DIRECTORY_SEPARATOR . 'mime.php');

class util_Mail extends Mail_mime
{    
    /**
     * util_Mail::send()
     * 
     * @param mixed $recipients - an array or a string with comma separated recipients.
     * @param array $headers - an associative array of headers. The header name is used as key 
     *      and the header value as value. If you want to override the envelope sender of the email,
     *      set the Return -Path header and that value will be used instead of the value of the From: header.
     * @param string $body - the body of the email. [optional will use self::get()]
     * @see http://pear.php.net/manual/en/package.mail.mail.send.php
     * @return void
     */
    public function send($recipients, $headers, $body = null)
    {
        class_exists('Mail', FALSE) || require 'Mail.php';
        
        if(empty($body)){
            $body = $this->get();
        }
        $mail =& Mail::factory('mail');
        $mail->send($recipients, $headers, $body);
    }
}