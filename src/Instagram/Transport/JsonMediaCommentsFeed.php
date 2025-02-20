<?php

declare(strict_types=1);

namespace Instagram\Transport;

use Instagram\Exception\InstagramFetchException;
use Instagram\Exception\InstagramNotFoundException;
use Instagram\Utils\InstagramHelper;

class JsonMediaCommentsFeed extends AbstractDataFeed
{
    /**
     * @param string $code
     * @param int $limit
     *
     * @return \StdClass
     *
     * @throws InstagramFetchException
     * @throws InstagramNotFoundException
     */
    public function fetchData(string $code, int $limit): \StdClass
    {
        $variables = [
            'shortcode' => $code,
            'first'     => $limit,
        ];

        $endpoint = InstagramHelper::URL_BASE . 'graphql/query/?query_hash=' . InstagramHelper::QUERY_HASH_COMMENTS . '&variables=' . json_encode($variables);

        $data = $this->fetchJsonDataFeed($endpoint);

        return !empty($data->data->shortcode_media->edge_media_to_comment) ? $data->data->shortcode_media->edge_media_to_comment : new \StdClass;
    }

    /**
     * @param string $code
     * @param string $endCursor
     *
     * @return \StdClass
     *
     * @throws InstagramFetchException
     * @throws InstagramNotFoundException
     */
    public function fetchMoreData(string $code, string $endCursor, int $limit = InstagramHelper::PAGINATION_DEFAULT): \StdClass
    {
        $variables = [
            'shortcode' => $code,
            'first'     => $limit,
            'after'     => $endCursor,
        ];

        $endpoint = InstagramHelper::URL_BASE . 'graphql/query/?query_hash=' . InstagramHelper::QUERY_HASH_COMMENTS . '&variables=' . json_encode($variables);

        $data = $this->fetchJsonDataFeed($endpoint);

        return !empty($data->data->shortcode_media->edge_media_to_comment) ? $data->data->shortcode_media->edge_media_to_comment : new \StdClass;
    }
}
