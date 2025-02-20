<?php

declare(strict_types=1);

namespace Instagram\Auth\Checkpoint;

use Instagram\Exception\InstagramImapException;

class ImapClient
{
    /**
     * @var string
     */
    private $server;

    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $connectionType;

    /**
     * @param string $server
     * @param string $login
     * @param string $password
     * @param string $connectionType
     *
     * @throws InstagramImapException
     */
    public function __construct(string $server, string $login, string $password, string $connectionType = 'imap')
    {
        $this->server         = $server;
        $this->login          = $login;
        $this->password       = $password;
        $this->connectionType = $connectionType;

        $this->available();
    }

    /**
     * @return string
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getConnectionType(): string
    {
        return $this->connectionType;
    }

    /**
     * @throws InstagramImapException
     *
     * @codeCoverageIgnore
     */
    private function available(): void
    {
        // ext-imap is enabled?
        if (!extension_loaded('imap')) {
            throw new InstagramImapException('IMAP php extension must be enabled to bypass checkpoint_challenge.');
        }
    }

    /**
     * @throws InstagramImapException
     */
    public function deleteAllEmails(): void
    {
        $resource  = @imap_open('{' . $this->getServer() . '/' . $this->getConnectionType() . '/ssl}INBOX', $this->getLogin(), $this->getPassword());

        if (!$resource) {
            throw new InstagramImapException('Unable to open IMAP stream.');
        }

        $numberMax = imap_num_msg($resource);

        if ($numberMax > 0) {
            for ($i = 1; $i <= $numberMax; $i++) {
                imap_delete($resource, (string) $i);
            }
            imap_expunge($resource);
        }

        imap_close($resource);
    }

    /**
     * @param int $try
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getLastInstagramEmailContent(int $try = 1): string
    {
        $resource  = @imap_open('{' . $this->getServer() . '/' . $this->getConnectionType() . '/ssl}INBOX', $this->getLogin(), $this->getPassword());

        if (!$resource) {
            throw new InstagramImapException('Unable to open IMAP stream.');
        }

        $numberMax = imap_num_msg($resource);

        $foundCode = false;
        $code      = '';

        // $numberMax = 0 when mailbox is empty
        if ($numberMax > 0) {

            // check into the last 3 mails
            for ($i = $numberMax; $i > ($numberMax - 3) && $i > 0; $i--) {
                $body = imap_body($resource, $i);
                $body = quoted_printable_decode($body);

                $headers = imap_headerinfo($resource, $i, 0);

                preg_match('/<font size="6">([0-9]{6})<\/font>/s', $body, $match);

                $isMailFromInstagram = false;

                // confirm instagram is the mail sender
                if (
                    (property_exists($headers, 'fromaddress') &&
                        $headers->fromaddress === 'Instagram <security@mail.instagram.com>') ||
                    (isset($headers->from[0]) && property_exists($headers->from[0], 'host') &&
                        $headers->from[0]->host === 'mail.instagram.com')
                ) {
                    $isMailFromInstagram = true;
                }

                if ($isMailFromInstagram && isset($match[1])) {
                    imap_delete($resource, (string) $i);
                    imap_expunge($resource);

                    $foundCode = true;
                    $code      = $match[1];
                    break;
                }
            }
        }

        imap_close($resource);

        // retry imap check (6 times max)
        if (!$foundCode && $try <= 6) {
            sleep(10);
            $code = $this->getLastInstagramEmailContent($try + 1);
        }

        return $code;
    }
}
