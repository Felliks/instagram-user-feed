<?php

declare(strict_types=1);

namespace Instagram\Transport;

use Instagram\Exception\InstagramFetchException;
use Instagram\Exception\InstagramNotFoundException;
use Instagram\Utils\InstagramHelper;

class JsonStoriesDataFeed extends AbstractDataFeed
{
    /**
     * @param int $int
     *
     * @return \StdClass
     *
     * @throws InstagramFetchException
     * @throws InstagramNotFoundException
     */
    public function fetchData(int $int): \StdClass
    {
        $variables = [
            'reel_ids'                    => [(string)$int],
            'tag_names'                   => [],
            'location_ids'                => [],
            'highlight_reel_ids'          => [],
            'precomposed_overlay'         => false,
            'show_story_viewer_list'      => true,
            'story_viewer_fetch_count'    => 50,
            'story_viewer_cursor'         => '',
            'stories_video_dash_manifest' => false,
        ];

        $endpoint = InstagramHelper::URL_BASE . 'graphql/query/?query_hash=' . InstagramHelper::QUERY_HASH_STORIES . '&variables=' . json_encode($variables);

        return $this->fetchJsonDataFeed($endpoint)->data;
    }
}
