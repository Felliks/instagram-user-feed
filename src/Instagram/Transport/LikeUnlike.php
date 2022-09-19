<?php

declare(strict_types=1);

namespace Instagram\Transport;

use Instagram\Exception\InstagramFetchException;
use Instagram\Exception\InstagramNotFoundException;
use Instagram\Utils\Endpoints;

class LikeUnlike extends AbstractDataFeed
{
    /**
     * @param int $postId
     *
     * @return string
     *
     * @throws InstagramFetchException
     * @throws InstagramNotFoundException
     */
    public function like(int $postId): string
    {
        $endpoint = Endpoints::getLikeUrl($postId);
        return $this->fetchData($endpoint);
    }

    /**
     * @param int $postId
     *
     * @return string
     *
     * @throws InstagramFetchException
     * @throws InstagramNotFoundException
     */
    public function unlike(int $postId): string
    {
        $endpoint = Endpoints::getUnlikeUrl($postId);
        return $this->fetchData($endpoint);
    }

    /**
     * @param string $endpoint
     *
     * @return string
     *
     * @throws InstagramFetchException
     * @throws InstagramNotFoundException
     */
    private function fetchData(string $endpoint): string
    {
        $data = $this->postJsonDataFeed($endpoint);

        if (!$data->status) {
            throw new InstagramFetchException('Whoops, looks like something went wrong!');
        }

        return $data->status;
    }
}
