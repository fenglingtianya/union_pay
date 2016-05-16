<?php

/**
 * 发邮件
 *
 */
class Helper_Mail
{

    private $subject = '';
    private $receipients = array();
    private $contents = '';
    private $senderName = '';
    private $senderEmail = '';
    private $returnPath = '';
    private $charset = 'utf8';
    private $textType = 'plain';
    private $mailData = '';

    public function __construct($senderEmailAddress, $senderName = '')
    {
        $this->setSender($senderEmailAddress, $senderName);
    }

    public function usePlainText()
    {
        $this->textType = 'plain';
    }

    public function useHtml()
    {
        $this->textType = 'html';
    }

    public function setMailContents($subject, $contents)
    {
        $this->setSubject($subject);
        $this->setContents($contents);
    }

    public function setSender($email, $name = '')
    {
        $email = $this->checkEmail($email);
        $this->senderEmail = $email;
        $this->senderName = trim($name);
        if (empty($this->senderName)) {
            $this->senderName = substr($this->senderEmail, 0, strpos($this->senderEmail, '@'));
        }
    }

    /**
     * @param multitype: $receipients
     */
    public function setReceipients($receipient)
    {
        if (!is_array($receipient)) {
            $this->receipients = array($this->checkEmail($receipient));
        } else {
            $emails = array();
            foreach ($receipient as $mailaddress) {
                $emails[] = $mailaddress;
            }
            $this->receipients = array_unique($emails);
        }
    }

    public function addReceipient($receipient)
    {
        $receipient = $this->checkEmail($receipient);
        $this->receipients[] = $receipient;
        $this->receipients = array_unique($this->receipients);
    }

    public function setCharSet($charset)
    {
        $this->charset = trim($charset);
        if (empty($this->charset))
            $this->charset = 'utf8';
    }

    public function send()
    {
        $this->buildMail();
        $this->sendMail();
    }

    public function setReturnPath($email)
    {
        $this->returnPath = $this->checkEmail($email);
    }

    /**
     * @return the $returnPath
     */
    public function getReturnPath()
    {
        if (empty($this->returnPath)) {
            $this->returnPath = $this->senderEmail;
        }
        return $this->returnPath;
    }

    public function getSenderName()
    {
        return $this->senderName;
    }

    public function getSenderEmail()
    {
        return $this->senderEmail;
    }

    /**
     * @return the $subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return array $receipients
     */
    public function getReceipients()
    {
        return $this->receipients;
    }

    /**
     * @return the $contents
     */
    public function getContents()
    {
        return $this->contents;
    }

    protected function getReceipientString()
    {
        return "<" . implode(">,<", $this->getReceipients()) . ">";
    }

    protected function buildMail()
    {
        $charset = $this->charset;

        $returnpath = $this->getReturnPath();
        $from = $this->getSenderEmail();
        $to = $this->getReceipientString();

        $fromname = base64_encode($this->getSenderName());

        $subject = base64_encode($this->getSubject());

        $contentType = $this->getMailContentType();

        $body = $this->buildMailBody();

        $this->mailData = <<<_HEADER_
Return-path: <{$returnpath}>
Content-Transfer-Encoding: base64
MIME-Version: 1.0
From: =?{$charset}?B?{$fromname}?= <{$from}>
To: {$to}
Subject: =?{$charset}?B?{$subject}?=
{$contentType}

{$body}

_HEADER_;

        $this->mailData = $this->removeTrailorWhitespaces($this->mailData);
    }

    /**
     * @return string
     */
    protected function getTextContentType()
    {
        return "Content-type: text/{$this->textType}; charset={$this->charset}";
    }

    protected function getMailContentType()
    {
        return $this->getTextContentType();
    }

    protected function buildMailBody()
    {
        return $this->toBase64($this->getContents());
    }

    protected function toBase64($content)
    {
        $content = base64_encode($content);
        $lines = ceil(strlen($content) / 76);
        $newcontent = "";
        for ($i = 0; $i < $lines; $i++) {
            $newcontent .= substr($content, $i * 76, 76) . "\n";
        }
        return $newcontent;
    }

    protected function removeTrailorWhitespaces($str)
    {
        return trim($str, ' ');
    }

    protected function checkEmail($email)
    {
        $email = trim($email);
        if (!$this->isValidEmail($email)) {
            throw new Exception("invalid receipient '{$email}'");
        }
        if (!$this->isAllowed($email)) {
            throw new Exception("receipient '{$email}' is not allowed");
        }
        return $email;
    }

    protected function isValidEmail($param)
    {
        return filter_var($param, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function isAllowed($email)
    {
        return true;
    }

    private function sendMail()
    {
        $handle = popen("/usr/sbin/sendmail " . implode(" ", $this->getReceipients()), "w");
        fwrite($handle, $this->mailData);
        pclose($handle);
    }

    /**
     * @param string $subject
     */
    private function setSubject($subject)
    {
        $this->subject = trim($subject);
    }

    /**
     * @param string $contents
     */
    private function setContents($contents)
    {
        $this->contents = $contents;
    }

}
