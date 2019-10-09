<?php
/**
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace Skyline\Mailer;


use PHPMailer\PHPMailer\PHPMailer;
use Skyline\Mailer\Account\Account;
use Skyline\Mailer\Account\EmailAddress;
use Skyline\Mailer\Exception\MailServiceException;
use Skyline\Mailer\Mail\AbstractMail;
use Skyline\Mailer\Mail\PlainMail;
use TASoft\Service\ConfigurableServiceInterface;

class MailService implements ConfigurableServiceInterface
{
    private $accounts = [];
    private $configuration;

    /**
     * @return mixed
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param mixed $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Getting account by name
     *
     * @param $name
     * @return Account|null
     */
    public function getAccount($name): ?Account {
        $acc = $this->accounts[$name] ?? NULL;
        if(!$acc) {
            $ai = $this->getConfiguration()["accounts"][$name] ?? NULL;
            if($ai) {
                $nm = $ai["name"] ?? NULL;
                $address = $ai["address"];
                if(!$address)
                    throw new MailServiceException("Can not create account $name without address");

                $mailAddress = new EmailAddress($address, $nm);
                $username = $ai["username"] ?? NULL;
                if(!$username) {
                    $username = $address;
                }
                $password = $ai["password"] ?? NULL;
                $host = $ai["host"] ?? NULL;
                $port = $ai["port"] ?? 587;

                $this->accounts[$name] = $acc = new Account($mailAddress, $username, $password, $host, $port);
            } else
                trigger_error("No SMTP account $name specified in mailer configuration", E_USER_WARNING);
        }
        return $acc;
    }

    /**
     * Adds an account
     *
     * @param Account $account
     * @param string|NULL $name
     */
    public function addAccount(Account $account, string $name = NULL) {
        if(NULL === $name)
            $name = $account->getName() ?: $account->getAddress();

        if(!isset($this->accounts[$name]))
            $this->accounts[$name] = $account;
    }

    /**
     * Removes an account
     *
     * @param string $name
     */
    public function removeAccount(string $name) {
        if(isset($this->accounts[$name]))
            unset($this->accounts[$name]);
    }

    /**
     * Gets the default account
     *
     * @return Account
     */
    public function getDefaultAccount(): Account {
        $defName = $this->getConfiguration()["defaultSender"] ?? NULL;
        if(!$defName) {
            throw new MailServiceException("No default name specified in mailer configuration");
        }

        $acc = $this->getAccount($defName);
        if(!$acc) {
            throw new MailServiceException("No default account specified for name $defName");
        }
        return $acc;
    }

    /**
     * Makes a new email from the defaut account
     *
     * @param string $mailClass
     * @return AbstractMail
     */
    public function makeMailFromDefaultAccount(string $mailClass = PlainMail::class): AbstractMail {
        $acc = $this->getDefaultAccount();
        return $this->makeMailFromAccount($acc, $mailClass);
    }

    /**
     * Makes a new email by a given account
     *
     * @param $account
     * @param string $mailClass
     * @return AbstractMail
     */
    public function makeMailFromAccount($account, string $mailClass = PlainMail::class): AbstractMail {
        if(is_string( $account )) {
            $account = $this->getAccount($account);
        }

        if($account instanceof Account) {
            $mail = new $mailClass();
            (function($acc) { $this->fromAccount = $acc; })->bindTo($mail, AbstractMail::class)($account);
            return $mail;
        }

        throw new MailServiceException("Can not create email for $account");
    }

    /**
     * Sends an email
     *
     * @param AbstractMail $mail
     * @return bool
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendMail(AbstractMail $mail): bool {
        $account = $mail->getFromAccount();

        $mailer = new PHPMailer(TRUE);
        $mailer->setFrom($account->getAddress(), $account->getName());

        if($account->getUsername() && $account->getPassword()) {
            // Use SMTP
            $mailer->isSMTP();
            $mailer->Host = $account->getHost();
            $mailer->SMTPAuth = true;
            $mailer->SMTPSecure = 'tls';
            $mailer->Port = $account->getPort();
            $mailer->Username = $account->getUsername();
            $mailer->Password = $account->getPassword();
        } else {
            $mailer->isMail();
        }

        (function($mailer){$this->setupMailer($mailer);})->bindTo($mail, get_class($mail))($mailer);

        return $mailer->send();
    }
}