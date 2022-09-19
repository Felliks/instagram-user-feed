<?php

declare(strict_types=1);

namespace Instagram\Transport;

use Instagram\Exception\InstagramFetchException;
use Instagram\Exception\InstagramNotFoundException;
use Instagram\Utils\InstagramHelper;
use GuzzleHttp\Exception\ClientException;

class JsonProfileDataFeedV2 extends AbstractDataFeed
{
    /**
     * @param string $username
     *
     * @return \StdClass
     *
     * @throws InstagramFetchException
     * @throws InstagramNotFoundException
     */
    public function fetchData(string $username): \StdClass
    {
        $endpoint = InstagramHelper::URL_API_BASE . 'api/v1/users/web_profile_info/?username=' . $username;

        try {
            $data = $this->fetchJsonDataFeed($endpoint, [
                'x-ig-app-id' => 936619743392459,
            ]);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new InstagramNotFoundException('Response code 404.');
            }

            throw new InstagramFetchException('Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new InstagramFetchException('Error: ' . $e->getMessage());
        }

        if (!$data->data->user) {
            throw new InstagramNotFoundException('Instagram id ' . $username . ' does not exist.');
        }

        return $data->data->user;
    }
}
