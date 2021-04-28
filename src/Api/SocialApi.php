<?php

namespace Grav\Plugin\SocialFeed\Api;

use Grav\Common\Grav;
use Grav\Plugin\SocialFeed\Model\Post;

/**
 * Class SocialApi.
 */
abstract class SocialApi
{
    /**
     * @var mixed
     */
    protected $api;

    /**
     * @var string
     */
    protected $providerName;

    /**
     * @param array $feed
     *
     * @return array[]|\stdClass[]
     */
    abstract public function getUserPosts($feed);

    /**
     * @param \stdClass|array $socialPost
     *
     * @return Post|false
     */
    abstract protected function getMappedPostObject($socialPost);

    /**
     * @param string $username
     *
     * @return Post[]
     */
    public function getUserPostObjects($feed)
    {
        $socialPosts = $this->getUserPosts($feed);
        $postObjectList = [];

        foreach ($socialPosts as $socialPost) {
            try {
                $postObject = $this->getMappedPostObject($socialPost);
                if (is_array($postObject) || is_object($postObject)) {
                    $postObjectList[] = $postObject;
                }
            } catch (\Exception $e) {
                Grav::instance()['log']->error(sprintf('An error occured during social posts array conversion. Error: %s', $e->getMessage()));
            }
        }

        return $postObjectList;
    }

    public function errorMail($errorMessage)
    {
        $grav = Grav::instance();
        $config = $grav['config']->get('plugins.social-feed');

        if(isset($config['mail']) && !empty($config['mail'])) {
            $content = "Problem on Social Feed Plugin: <br> ".get_current_user()."@".gethostname()." <br> " . __DIR__ . " <br> " . $errorMessage;
            $subject = 'Social Feed Problem';

            $message = $grav['Email']->message($subject, $content, 'text/html')
                ->setFrom($config['mail'])
                ->setTo($config['mail']);
            $sent = $grav['Email']->send($message);
        }
    }
}
