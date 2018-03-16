<?php
namespace Instagram;

use Instagram\Hydrator\Feed;
use Instagram\Hydrator\Media;

class Hydrator
{
    /**
     * @var array
     */
    private $data;

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return Feed
     */
    public function getHydratedData()
    {
        $feed = $this->generateFeed();

        foreach ($this->data['edge_owner_to_timeline_media']['edges'] as $node) {
            $node = $node['node'];

            $media = new Media();

            $media->setId($node['id']);
            $media->setTypeName($node['__typename']);

            $caption = isset($node['edge_media_to_caption']['edges'][0]['node']['text']) ? $node['edge_media_to_caption']['edges'][0]['node']['text'] : null;
            $media->setCaption($caption);

            $media->setHeight($node['dimensions']['height']);
            $media->setWidth($node['dimensions']['width']);

            $media->setThumbnailSrc($node['thumbnail_src']);
            $media->setDisplaySrc($node['display_url']);

            $resources = [];

            foreach ($node['thumbnail_resources'] as $resource) {
                $resources[] = [
                    'src'    => $resource['src'],
                    'width'  => $resource['config_width'],
                    'height' => $resource['config_height'],
                ];
            }

            $media->setThumbnailResources($resources);

            $media->setCode($node['shortcode']);
            $media->setLink('https://www.instagram.com/p/' . $node['shortcode'] . '/');

            $date = new \DateTime();
            $date->setTimestamp($node['taken_at_timestamp']);

            $media->setDate($date);

            $media->setComments($node['edge_media_to_comment']['count']);
            $media->setLikes($node['edge_liked_by']['count']);

            $feed->addMedia($media);
        }

        return $feed;
    }

    /**
     * @return Feed
     */
    private function generateFeed()
    {
        $feed = new Feed();

        $feed->setId($this->data['id']);
        $feed->setUserName($this->data['username']);
        $feed->setFullName($this->data['full_name']);
        $feed->setBiography($this->data['biography']);

        $feed->setIsVerified($this->data['is_verified']);
        $feed->setFollowers($this->data['edge_followed_by']['count']);
        $feed->setFollowing($this->data['edge_follow']['count']);

        $feed->setProfilePicture($this->data['profile_pic_url']);
        $feed->setProfilePictureHd($this->data['profile_pic_url_hd']);
        $feed->setExternalUrl($this->data['external_url']);

        $feed->setHasNextPage($this->data['edge_owner_to_timeline_media']['page_info']['has_next_page']);
        $feed->setMediaCount($this->data['edge_owner_to_timeline_media']['count']);

        return $feed;
    }
}
