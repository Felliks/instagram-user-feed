<?php

declare(strict_types=1);

namespace Instagram\Transport;

use GuzzleHttp\Exception\ClientException;
use Instagram\Exception\InstagramFetchException;
use Instagram\Utils\Endpoints;
use Instagram\Utils\OptionHelper;

class TimelineDataFeed extends AbstractDataFeed
{
    const IG_APP_ID = 1217981644879628;

    /**
     * @param string|null $maxId
     *
     * @return \StdClass
     * @throws \JsonException
     */
    public function fetchData(string $maxId = null): \StdClass
    {
        $newFeedPostExist = $this->fetchDataNewFeedPostExist();
        $timelineFeed     = $this->fetchDataTimelineFeed($maxId);
        $data = json_encode(array_merge($newFeedPostExist, $timelineFeed), JSON_THROW_ON_ERROR);

        return json_decode($data, false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array
     *
     * @throws InstagramFetchException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function fetchDataNewFeedPostExist(): array
    {
        $endpoint = 'https://i.instagram.com/api/v1/feed/new_feed_posts_exist/';

        $csrfToken = '';
        if (!empty($this->session->getCookies()->getCookieByName("csrftoken"))) {
            $csrfToken = $this->session->getCookies()->getCookieByName("csrftoken")->getValue();
        }

        $options = [
            'headers' => [
                'user-agent'  => OptionHelper::$USER_AGENT,
                'accept-language' => OptionHelper::$LOCALE,
                'x-csrftoken' => $csrfToken,
                'x-ig-app-id' => self::IG_APP_ID,
            ],
            'cookies' => $this->session->getCookies(),
        ];

        try {
            $res = $this->client->request('GET', $endpoint, $options);
        } catch (ClientException $exception) {
            throw new InstagramFetchException('New feed post exist fetch error');
        }

        return json_decode((string) $res->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param string|null $maxId
     *
     * @return array
     *
     * @throws InstagramFetchException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetchDataTimelineFeed(string $maxId = null): array
    {
        $endpoint = Endpoints::TIMELINE_URL;

        $csrfToken = '';
        if (!empty($this->session->getCookies()->getCookieByName("csrftoken"))) {
            $csrfToken = $this->session->getCookies()->getCookieByName("csrftoken")->getValue();
        }

        $options = [
            'headers' => [
                'user-agent'  => OptionHelper::$USER_AGENT,
                'accept-language' => OptionHelper::$LOCALE,
                'x-csrftoken' => $csrfToken,
                'x-ig-app-id' => self::IG_APP_ID,
            ],
            'cookies' => $this->session->getCookies(),
        ];

        $params = [
            'is_async_ads_rti'                 => 0,
            'is_async_ads_double_request'      => 0,
            'rti_delivery_backend'             => 0,
            'is_async_ads_in_headload_enabled' => 0,
        ];

        if ($maxId) {
            $params = array_merge($params, [
                'max_id' => $maxId,
            ]);
        }

        $options = array_merge($options, ['form_params' => $params]);

        try {
            $res = $this->client->request('POST', $endpoint, $options);
        } catch (ClientException $exception) {
            throw new InstagramFetchException('Timeline fetch error');
        }

        return json_decode((string) $res->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}