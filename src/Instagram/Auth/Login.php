<?php

declare(strict_types=1);

namespace Instagram\Auth;

use GuzzleHttp\{ClientInterface, Cookie\CookieJar};
use GuzzleHttp\Exception\ClientException;
use Instagram\Auth\Checkpoint\{Challenge, ImapClient};
use Instagram\Exception\InstagramAuthException;
use Instagram\Exception\InstagramBlockAccountException;
use Instagram\Exception\InstagramBlockIpException;
use Instagram\Exception\InstagramCredentialsException;
use Instagram\Utils\{InstagramHelper, OptionHelper, CacheResponse};

class Login
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $password;

    /**
     * @var ImapClient|null
     */
    private $imapClient;

    /**
     * @var int
     */
    private $challengeDelay;

    /**
     * @param ClientInterface $client
     * @param string          $login
     * @param string          $password
     * @param ImapClient|null $imapClient
     * @param int|null        $challengeDelay
     */
    public function __construct(ClientInterface $client, string $login, string $password, ?ImapClient $imapClient = null, ?int $challengeDelay = 3)
    {
        $this->client         = $client;
        $this->login          = $login;
        $this->password       = $password;
        $this->imapClient     = $imapClient;
        $this->challengeDelay = $challengeDelay;
    }

    /**
     * @return CookieJar
     * @throws InstagramAuthException
     * @throws InstagramBlockIpException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws InstagramCredentialsException
     * @throws \JsonException
     */
    public function process(): CookieJar
    {
        $baseRequest = $this->client->request('GET', InstagramHelper::URL_BASE, [
            'headers' => [
                'user-agent' => OptionHelper::$USER_AGENT,
            ],
        ]);
        CacheResponse::setResponse($baseRequest);

        $html = (string) $baseRequest->getBody();

        preg_match('/\\\"csrf_token\\\":\\\"(.*?)\\\"/', $html, $matches);

        if (!isset($matches[1])) {
            throw new InstagramAuthException('Unable to extract JSON data');
        }

        $cookieJar = new CookieJar();

        try {
            $query = $this->client->request('POST', InstagramHelper::URL_AUTH, [
                'form_params' => [
                    'username'     => $this->login,
                    'enc_password' => '#PWD_INSTAGRAM_BROWSER:0:' . time() . ':' . $this->password,
                ],
                'headers'     => [
                    'cookie'           => 'ig_cb=1; csrftoken=' . $matches[1],
                    'referer'          => InstagramHelper::URL_BASE,
                    'x-csrftoken'      => $matches[1],
                    'user-agent'       => OptionHelper::$USER_AGENT,
                    'accept-language'  => OptionHelper::$LOCALE,
                ],
                'cookies'     => $cookieJar,
            ]);
        } catch (ClientException $exception) {
            CacheResponse::setResponse($exception->getResponse());

            if (str_contains($exception->getMessage(), 'Your account has been permanently disabled')) {
                throw new InstagramBlockAccountException();
            }

            if (str_contains($exception->getMessage(), 'Please wait a few minutes before you try again')) {
                throw new InstagramBlockIpException();
            }

            if (str_contains($exception->getMessage(), 'Your IP may be block from Instagram')) {
                throw new InstagramBlockIpException();
            }

            $data = json_decode((string)$exception->getResponse()->getBody(), false, 512, JSON_THROW_ON_ERROR);

            if ($exception->getResponse()->getStatusCode() === 429) {
                throw new InstagramBlockIpException();
            }

            if ($data && $data->message === 'checkpoint_required') {
                // @codeCoverageIgnoreStart
                return $this->checkpointChallenge($cookieJar, $data);
                // @codeCoverageIgnoreEnd
            }

            if (str_contains($exception->getMessage(), 'Sorry, your password was incorrect')) {
                throw new InstagramCredentialsException('Wrong login / password');
            }

            throw new InstagramAuthException('Unknown error, please report it with a GitHub issue. ' . $exception->getMessage());
        }

        CacheResponse::setResponse($query);

        $response = json_decode((string) $query->getBody(), false, 512, JSON_THROW_ON_ERROR);

        if (property_exists($response, 'authenticated') && $response->authenticated == true) {
            return $cookieJar;
        }

        if (property_exists($response, 'error_type') && $response->error_type === 'generic_request_error') {
            throw new InstagramBlockIpException('Generic error / Your IP may be block from Instagram. You should consider using a proxy.');
        }

        throw new InstagramCredentialsException('Wrong login / password');
    }

    /**
     * @param \array $session
     * 
     * @return CookieJar
     *
     * @throws InstagramAuthException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function withCookies(array $session): CookieJar
    {
        $cookies = new CookieJar(true, [$session]);

        $baseRequest = $this->client->request('GET', InstagramHelper::URL_BASE, [
            'headers' => [
                'user-agent' => OptionHelper::$USER_AGENT,
            ],
            'cookies' => $cookies
        ]);

        CacheResponse::setResponse($baseRequest);

        $html = (string) $baseRequest->getBody();

        preg_match('/<script type="text\/javascript">window\._sharedData\s?=(.+);<\/script>/', $html, $matches);

        if (isset($matches[1])) {
            $data = json_decode($matches[1], false, 512, JSON_THROW_ON_ERROR);

            if (!isset($data->config->viewer) && !isset($data->config->viewerId)) {
                throw new InstagramAuthException('Please login with instagram credentials.');
            }
        }

        return $cookies;
    }

    /**
     * @param CookieJar $cookieJar
     * @param \StdClass $data
     *
     * @return CookieJar
     *
     * @throws InstagramAuthException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @codeCoverageIgnore
     */
    private function checkpointChallenge(CookieJar $cookieJar, \StdClass $data): CookieJar
    {
        if (!$this->imapClient instanceof ImapClient) {
            throw new InstagramAuthException('Checkpoint required, please provide IMAP credentials to process authentication.');
        }

        $challenge = new Challenge($this->client, $cookieJar, $data->checkpoint_url, $this->challengeDelay);

        $challengeContent = $challenge->fetchChallengeContent();

        $this->imapClient->deleteAllEmails();

        //$challenge->sendSecurityCode($challengeContent);
        $challenge->reSendSecurityCode($challengeContent);

        $code = $this->imapClient->getLastInstagramEmailContent();
        //if (!$code = $this->imapClient->getLastInstagramEmailContent()) {
        //    $challenge->reSendSecurityCode($challengeContent);
        //}

        var_dump($code);

        return $challenge->submitSecurityCode($challengeContent, $code);
    }
}
