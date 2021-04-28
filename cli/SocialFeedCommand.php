<?php

namespace Grav\Plugin\Console;

use Grav\Common\Grav;
use Grav\Console\ConsoleCommand;
use Grav\Plugin\SocialFeed\Api\FacebookApi;
use Grav\Plugin\SocialFeed\Api\InstagramApi;
use Grav\Plugin\SocialFeed\Api\SocialApi;
use Grav\Plugin\SocialFeed\Api\TwitterApi;
use Grav\Plugin\SocialFeed\Manager\PostManager;

/**
 * SocialFeedCommand.
 */
class SocialFeedCommand extends ConsoleCommand
{
    static $grav;

    public function __construct($name = null)
    {
        parent::__construct($name);
        self::$grav = Grav::instance();
    }

    /**
     * {@inherit}.
     */
    protected function configure()
    {
        $this
            ->setName('fetch:posts')
            ->setDescription('This command fetch posts from Facebook, Twitter and Instagram, for all accounts listed in the plugin configuration form.')
        ;
    }

    /**
     * {@inherit}.
     */
    protected function serve()
    {
        require_once __DIR__.'/../vendor/autoload.php';

        $posts = array();
        $manager = new PostManager(true);
        $config = self::$grav['config']->get('plugins.social-feed');
        foreach ($this->getReadyApis() as $networkName => $api) {
            foreach ($config[$networkName.'_feeds'] as $feed) {
                try {
                    foreach ($api->getUserPostObjects($feed) as $post) {
                        $manager->storeAttachments($post);
                        $posts[] = $post;
                    }
                } catch (\Exception $e) {
                    $this->error(sprintf('Fetching posts from %s failed. Error: %s', $networkName, $e->getMessage()));
                }
            }
        }
        $manager->savePosts($posts);
        $this->success('Social network post fetching has finished.');
    }

    /**
     * Get apis ready to be used.
     *
     * @return SocialApi[]
     */
    protected function getReadyApis()
    {
        $apis = array();
        $config = self::$grav['config']->get('plugins.social-feed');
        // Facebook api
        if (
            isset($config['facebook_feeds']) && count($config['facebook_feeds']) > 0
        ) {
            $apis['facebook'] = new FacebookApi();
        }
        // Twitter api
        if (
            isset($config['twitter_consumer_key']) && $config['twitter_consumer_key'] &&
            isset($config['twitter_consumer_secret']) && $config['twitter_consumer_secret'] &&
            isset($config['twitter_feeds']) && count($config['twitter_feeds']) > 0
        ) {
            $apis['twitter'] = new TwitterApi([
                'consumer_key' => $config['twitter_consumer_key'],
                'consumer_secret' => $config['twitter_consumer_secret'],
            ]);
        }
        // Instagram api
        if (
            isset($config['instagram_feeds']) && count($config['instagram_feeds']) > 0
        ) {
            foreach ($config['instagram_feeds'] as $instagram_feed) {
                $apis['instagram'] = new InstagramApi();
            }
        }

        return $apis;
    }

    /**
     * Notify error.
     *
     * @param string $message
     */
    protected function error($message)
    {
        self::$grav['log']->error($message);
        $this->output->writeln('<red>'.$message.'</red>');
    }

    /**
     * Notify success.
     *
     * @param string $message
     */
    protected function success($message)
    {
        $this->output->writeln('<green>'.$message.'</green>');
    }
}
