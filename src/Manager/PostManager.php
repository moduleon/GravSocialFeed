<?php

namespace Grav\Plugin\SocialFeed\Manager;

use Grav\Common\Grav;
use Grav\Common\File\CompiledYamlFile;
use Grav\Plugin\SocialFeed\Model\Post;
use RocketTheme\Toolbox\Event\Event;

/**
 * PostManager handles CRUD actions on posts.
 */
class PostManager
{
    /**
     * @var Grav
     */
    protected $grav;

    public function __construct($cli = false)
    {
        $this->grav = Grav::instance();
        if ($cli) {
            $this->grav->fireEvent('onPluginsInitialized');
        }
    }

    /**
     * Get posts according to given parameters.
     *
     * @param array $params
     *
     * @return array
     */
    public function getPosts(array $params = array())
    {
        $file = $this->getContentFile();
        // Filter posts
        $posts = array();
        foreach ($file->content() as $post) {
            if (isset($params['providers'])) {
                if (!in_array($post['provider'], $params['providers'])) {
                    continue;
                }
            }
            if (isset($params['usernames'])) {
                if (!in_array($post['authorUsername'], $params['usernames'])) {
                    continue;
                }
            }
            if (isset($params['tags'])) {
                if(!$post['tags']) {
                    continue;
                }
                $hasTag=0;
                $postTags = json_decode($post['tags']);

                foreach ($params['tags'] as $tag) {
                    if (in_array(strtolower($tag), $postTags)) {
                        $hasTag=1;
                    }
                }

                if($hasTag==0) {
                    continue;
                }
            }
            $posts[] = $post;
        }
        // Sort posts
        $orderBy = isset($params['order_by']) ? (string) $params['order_by'] : 'publishedAt';
        $orderDir = isset($params['order_dir']) ? (string) $params['order_dir'] : SORT_DESC;
        usort($posts, function ($a, $b) use ($orderBy, $orderDir) {
            if ($a[$orderBy] === $b[$orderBy]) {
                return 0;
            } else {
                $result = $a[$orderBy] < $b[$orderBy] ? -1 : 1;
                if (SORT_DESC === $orderDir) {
                    $result *= -1;
                }

                return $result;
            }
        });
        // Paginate
        $total = count($posts);
        $limit = isset($params['limit']) ? (int) $params['limit'] : 10;
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $duplicated = isset($params['duplicated']) ? (boolean) $params['duplicated'] : true;
        $offset = ($page * $limit) - $limit;

        //remove duplicated posts
        if($duplicated == false) {
            $tmpPosts = $posts;
            unset($posts);
            $posts = [];

            foreach ($tmpPosts as $key => $post) {
                if($post['duplicated'] != true) {
                    $posts[$post['provider'].'_'.$post['postId']] = $post;
                }
            }
        }

        $posts = array_splice($posts, $offset, $limit);

        // Absolute url
        if ($this->grav['config']->get('system.absolute_urls')) {
            $baseUrl = $this->grav['pages']->baseUrl();
            foreach ($posts as $key => $post) {
                $posts[$key]['fileUrl'] = $post['fileUrl'] ? $baseUrl.$post['fileUrl'] : null;
                $posts[$key]['authorFileUrl'] = $post['authorFileUrl'] ? $baseUrl.$post['authorFileUrl'] : null;
            }
        }

        return array(
            'total' => $total,
            'limit' => $limit,
            'page' => $page,
            'posts' => $posts,
        );
    }

    /**
     * Save posts locally.
     *
     * @param array $posts
     */
    public function savePosts(array $posts)
    {
        $file = $this->getContentFile();
        $content = $file->content();
        $updated = false;
        /** @var Post $post */
        foreach ($posts as $post) {
            if (!isset($content[$post->getProvider().'_'.$post->getPostId()])) {
                $updated = true;
                $arrayPost = [
                    'provider' => $post->getProvider(),
                    'postId' => $post->getPostId(),
                    'authorUsername' => $post->getAuthorUsername(),
                    'authorName' => $post->getAuthorName(),
                    'authorFileUrl' => $post->getAuthorFileUrl(),
                    'headline' => $post->getHeadline(),
                    'body' => $post->getBody(),
                    'tags' => $post->getTags(),
                    'fileUrl' => $post->getFileUrl(),
                    'link' => $post->getLink(),
                    'publishedAt' => $post->getPublishedAt()->format('Y-m-d H:i:s'),
                    'duplicated' => false,
                    'originalPostId' => ''
                ];

                //check if posts already exist from other provider
                foreach ($content as &$storedPost) {
                    if((strlen($post->getHeadline()) > 0 && $storedPost['headline'] == $post->getHeadline()) || (strlen($post->getBody()) > 0 && $storedPost['body'] == $post->getBody())) {
                        //check if stored posts is already duplicate from this post
                        if($storedPost['originalPostId'] != $arrayPost['postId']) {
                            $arrayPost['duplicated'] = true;
                            $arrayPost['originalPostId'] = $storedPost['postId'];
                            break;
                        }
                    }
                }

                $event = new Event(['post' => $arrayPost]);
                $this->grav->fireEvent('onSocialPostBeforeSave', $event);
                $arrayPost = $event->offsetGet('post');
                $content[$post->getProvider().'_'.$post->getPostId()] = $arrayPost;
            }
        }

        if ($updated) {
            $file->content($content);
            $file->save();
        }
    }

    /**
     * Store post attachments.
     *
     * @param Post $post
     */
    public function storeAttachments(Post $post)
    {
        $locator = $this->grav['locator'];
        $uploadDir = $locator->findResource('user://media', true, true);
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir);
        }

        // Author picture - Update every 24h
        if ($post->getAuthorFileUrl()) {
            $basename = $post->getProvider().'_'.$post->getAuthorUsername();
            $files = glob($uploadDir.'/'.$basename.'.*');
            if (0 === count($files) || 86400 < (time() - filemtime($files[0]))) {
                $filename = $this->downloadFile($post->getAuthorFileUrl(), $basename, $uploadDir);
                if ($filename) {
                    $post->setAuthorFileUrl($this->getMediaUrl().'/'.$filename);
                } else {
                    $post->setAuthorFileUrl(null);
                }
            } else {
                // set file if found locally
                $filepath = explode('/', $files[0]);
                $post->setAuthorFileUrl($this->getMediaUrl().'/'.$filepath[count($filepath)-1]);
            }
        }

        // Post file - Downloaded if not found locally.
        if ($post->getFileUrl()) {
            $basename = $post->getProvider().'_'.$post->getPostId();
            $files = glob($uploadDir.'/'.$basename.'.*');
            if (0 === count($files)) {
                $filename = $this->downloadFile($post->getFileUrl(), $basename, $uploadDir);
                if ($filename) {
                    $post->setFileUrl($this->getMediaUrl().'/'.$filename);
                } else {
                    $post->setFileUrl(null);
                }
            } else {
                // set file if found locally
                $filepath = explode('/', $files[0]);
                $post->setFileUrl($this->getMediaUrl().'/'.$filepath[count($filepath)-1]);
            }
        }
    }

    /**
     * Download a file.
     *
     * @param string $url      is the url where file is currently stored
     * @param string $basename is the base name to give to the file without the extension
     * @param string $dir      is the absolute path where to store the file
     *
     * @return string|null
     */
    private function downloadFile($url, $basename, $dir)
    {
        $arrContextOptions = [];

        $config = $this->grav['config']->get('plugins.social-feed');
        if (isset($config['enablessl']) && $config['enablessl'] == false) {
            $arrContextOptions['ssl']['verify_peer'] = 0;
            $arrContextOptions['ssl']['verify_peer_name'] = 0;
        }

        if (isset($config['certpath']) && $config['certpath']) {
            $arrContextOptions['ssl']['cafile'] = $config['certpath'];
        }

        $fileContents = @file_get_contents($url, false, stream_context_create($arrContextOptions));
        if (!$fileContents || strstr($fileContents, '<!DOCTYPE html>')) {
            return;
        }

        $storageFile = tempnam(sys_get_temp_dir(), 'SocialFeed');
        file_put_contents($storageFile, $fileContents);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $storageFile);
        $ext = strtolower(explode('/', $mime)[1]);
        if (true === in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $filename = $basename.'.'.$ext;
            $destination = $dir.'/'.$filename;
            copy($storageFile, $destination);
            unlink($storageFile);

            return $filename;
        }
    }

    /**
     * Get media folder public url.
     *
     * @return string
     */
    private function getMediaUrl()
    {
        return '/user/media';
    }

    /**
     * Get content file.
     *
     * @return CompiledYamlFile
     */
    private function getContentFile()
    {
        $locator = $this->grav['locator'];
        $filename = $locator->findResource('user://data/social-feed.yaml', true, true);

        return CompiledYamlFile::instance($filename);
    }
}
