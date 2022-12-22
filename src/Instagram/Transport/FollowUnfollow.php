<?php

declare(strict_types=1);

namespace Instagram\Transport;

use Instagram\Utils\Endpoints;
use Instagram\Exception\InstagramFetchException;
use Instagram\Exception\InstagramFollowException;
use Instagram\Exception\InstagramNotFoundException;

class FollowUnfollow extends AbstractDataFeed
{
    /**
     * @param int $accountId
     *
     * @return string
     *
     * @throws InstagramNotFoundException
     * @throws InstagramFollowException
     */
    public function follow(int $accountId): string
    {
        try {
            return $this->fetchData(Endpoints::getFollowUrl($accountId));
        } catch (InstagramFetchException $exception) {
            throw new InstagramFollowException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param int $accountId
     *
     * @return string
     *
     * @throws InstagramFetchException
     * @throws InstagramNotFoundException
     */
    public function unfollow(int $accountId): string
    {
        $endpoint = Endpoints::getUnfollowUrl($accountId);
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
