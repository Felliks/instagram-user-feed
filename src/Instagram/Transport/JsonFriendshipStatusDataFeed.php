<?php

declare(strict_types=1);

namespace Instagram\Transport;

use Instagram\Exception\InstagramFetchException;
use Instagram\Exception\InstagramNotFoundException;
use Instagram\Utils\InstagramHelper;
use GuzzleHttp\Exception\ClientException;

class JsonFriendshipStatusDataFeed extends AbstractDataFeed
{
    public function fetchData(int $userId): array
    {
        $endpoint = InstagramHelper::URL_API_BASE . "api/v1/friendships/show/{$userId}/";

        $data = $this->fetchJsonDataFeed($endpoint, [
            'x-ig-app-id' => 936619743392459,
        ]);

        dump($data);

        return json_decode(json_encode($data), true, 512, JSON_THROW_ON_ERROR);
    }
}
