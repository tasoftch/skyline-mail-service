<?php
/**
 * Copyright (c) 2018 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Skyline\Mailer\Mail;


use PHPMailer\PHPMailer\PHPMailer;
use Skyline\Mailer\Account\Account;
use Skyline\Mailer\Account\EmailAddress;

abstract class AbstractMail
{
    /** @var Account */
    private $fromAccount;
    /** @var EmailAddress[] */
    private $to=[];
    /** @var EmailAddress[] */
    private $cc=[];
    /** @var EmailAddress[] */
    private $bcc = [];
    /** @var EmailAddress[] */
    private $reply=[];

    /** @var string */
    private $subject = "";

    private $encoding = PHPMailer::ENCODING_8BIT;
    private $charSet = PHPMailer::CHARSET_ISO88591;

    /**
     * @return string
     */
    public function getCharSet(): string
    {
        return $this->charSet;
    }

    /**
     * @param string $charSet
     */
    public function setCharSet(string $charSet): void
    {
        $this->charSet = $charSet;
    }

    /**
     * @return string
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * @param string $encoding
     */
    public function setEncoding(string $encoding): void
    {
        $this->encoding = $encoding;
    }


    /**
     * @return Account
     */
    public function getFromAccount(): Account
    {
        return $this->fromAccount;
    }

    public function addAddress(EmailAddress $address) {
        $this->to[] = $address;
    }

    public function addCC(EmailAddress $address) {
        $this->cc[] = $address;
    }
    public function addBCC(EmailAddress $address) {
        $this->bcc[] = $address;
    }
    public function addReplyTo(EmailAddress $address) {
        $this->reply[] = $address;
    }

    abstract public function getBody();
    abstract public function getAlternativeBody();

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    protected function setupMailer(PHPMailer $mailer) {
        foreach($this->to as $address) {
            $mailer->addAddress($address->getAddress(), $address->getName());
        }
        foreach($this->cc as $address) {
            $mailer->addCC($address->getAddress(), $address->getName());
        }
        foreach($this->bcc as $address) {
            $mailer->addBCC($address->getAddress(), $address->getName());
        }
        foreach($this->reply as $address) {
            $mailer->addReplyTo($address->getAddress(), $address->getName());
        }
        $mailer->CharSet = $this->getCharSet();
        $mailer->Encoding = $this->getEncoding();

        $mailer->Subject = $this->getSubject();
    }
}