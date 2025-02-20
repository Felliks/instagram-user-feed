<?php

declare(strict_types=1);

namespace Instagram\Transport;

use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\ClientException;
use Instagram\Exception\InstagramFetchException;
use Instagram\Exception\InstagramNotFoundException;
use Instagram\Utils\{Endpoints, OptionHelper, CacheResponse};

class JsonProfileAlternativeDataFeed extends AbstractDataFeed
{
    const IG_APP_ID = 936619743392459;

    /**
     * @param int $userId
     * @param string|null $maxId
     *
     * @return \StdClass
     *
     * @throws InstagramFetchException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function fetchData(int $userId): \StdClass
    {
        $csrfToken = '';

        /** @var SetCookie $cookie */
        foreach ($this->session->getCookies() as $cookie) {
            if ($cookie->getName() === 'csrftoken') {
                $csrfToken = $cookie->getValue();
                break;
            }
        }

        $options = [
            'headers' => [
                'user-agent'       => OptionHelper::$USER_AGENT,
                'accept-language'  => OptionHelper::$LOCALE,
                'x-csrftoken'      => $csrfToken,
                'x-ig-app-id'      => self::IG_APP_ID,
            ],
            'cookies' => $this->session->getCookies(),
        ];

        try {
            $res = $this->client->request('GET', Endpoints::getProfileUrl($userId), $options);
        } catch (ClientException $exception) {
            CacheResponse::setResponse($exception->getResponse());
            throw new InstagramFetchException('Reels fetch error');
        }

        CacheResponse::setResponse($res);

        return json_decode((string) $res->getBody(), false, 512, JSON_THROW_ON_ERROR)->user;
    }
}