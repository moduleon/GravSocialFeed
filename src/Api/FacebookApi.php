<?php

namespace Grav\Plugin\SocialFeed\Api;

use Grav\Plugin\SocialFeed\Model\Post;
use Facebook\Facebook;

final class FacebookApi extends SocialApi
{
    /**
     * @var string
     */
    protected $providerName = 'facebook';

    /**
     * @var array
     */
    private $config;

    /**
     * {@inherit}.
     */
    public function getUserPosts($feed)
    {
        //save user accesstoken if is set
        if(isset($feed['username']) && !empty($feed['username'])) {
            $this->config['username'] = $feed['username'];
            $this->config['access_token'] = "&access_token=".$feed['accesstoken'];
        }

        $fields = '?fields=full_picture,from,message,id,permalink_url,created_time';
        $response = $this->requestGet('https://graph.facebook.com/'.$this->config['username'].'/posts' . $fields);
        return $response['data'];
    }

    /**
     * {@inherit}.
     */
    protected function getMappedPostObject($socialPost)
    {
        $post = new Post();

        //body
        $message = $this->getFormattedTextFromPost($socialPost['message']);
        if (!isset($message)) {
            return false;
        }
        $post->setBody($message);

        //creator username and image
        $fields = '?fields=username,picture';
        $userDetails = $this->requestGet('https://graph.facebook.com/'.$socialPost['from']['id'] . $fields);

        if (empty($userDetails)) {
            return false;
        }

        $post->setAuthorName($socialPost['from']['name']);
        $post->setAuthorUsername($userDetails['username']);
        $post->setAuthorFileUrl($userDetails['picture']['data']['url']);

        //post image
        $post->setFileUrl($socialPost['full_picture']);

        //other params
        $post->setHeadline(strip_tags($socialPost['message']));
        $post->setPostId($socialPost['id']);
        $post->setProvider($this->providerName);
        $post->setLink($socialPost['permalink_url']);

        $publishAt = new \DateTime($socialPost['created_time']);
        $post->setPublishedAt($publishAt);

        return $post;
    }

    /**
     * Format text
     *
     * @param string $text
     *
     * @return string
     */
    private function getFormattedTextFromPost($text)
    {
        // Add href for links prefixed with ***:// (*** is most likely to be http(s) or ftp
        $text = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", '\\1<a href="\\2" target="_blank">\\2</a>', $text);
        // Add href for links starting with www or ftp
        $text = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", '\\1<a href="http://\\2" target="_blank">\\2</a>', $text);
        // Add link to hashtags
        $text = preg_replace("/#(\w+)/", '<a href="https://www.facebook.com/hashtag/\\1" target="_blank">#\\1</a>', $text);

        return $text;
    }

    /**
     * Send a GET request.
     *
     * @param string $url
     *
     * @return getGraphNode
     */
    private function requestGet($url)
    {
        try {
            $response = @file_get_contents($url . $this->config['access_token']);
        }
        catch (Exception $e) {
            echo $e->getMessage();
            exit;
        }

        if($response == false) {
            echo "Something went wrong by getting the data of " . $this->providerName . " user: " . $this->config['username'];
            exit;
        }

        return json_decode($response, true);
    }
}
