<?php

namespace Grav\Plugin\SocialFeed\Model;

/**
 * Post represents a social post.
 */
class Post
{
    /**
     * @var string
     */
    private $provider;

    /**
     * @var string
     */
    private $postId;

    /**
     * @var string
     */
    private $authorUsername;

    /**
     * @var string
     */
    private $authorName;

    /**
     * @var string
     */
    private $authorFileUrl;

    /**
     * @var string
     */
    private $headline;

    /**
     * @var string
     */
    private $body;

    /**
     * @var string
     */
    private $tags;

    /**
     * @var string
     */
    private $fileUrl;

    /**
     * @var string
     */
    private $link;

    /**
     * @var \DateTime
     */
    private $publishedAt;

    /**
     * @var boolean
     */
    private $duplicated;

    /**
     * @var string
     */
    private $originalPostId;

    /**
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param string $provider
     *
     * @return Post
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * @return string
     */
    public function getPostId()
    {
        return $this->postId;
    }

    /**
     * @param string $postId
     *
     * @return Post
     */
    public function setPostId($postId)
    {
        $this->postId = $postId;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorUsername()
    {
        return $this->authorUsername;
    }

    /**
     * @param string $authorUsername
     *
     * @return Post
     */
    public function setAuthorUsername($authorUsername)
    {
        $this->authorUsername = $authorUsername;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorName()
    {
        return $this->authorName;
    }

    /**
     * @param string $authorName
     *
     * @return Post
     */
    public function setAuthorName($authorName)
    {
        $this->authorName = $authorName;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorFileUrl()
    {
        return $this->authorFileUrl;
    }

    /**
     * @param string $authorFileUrl
     *
     * @return Post
     */
    public function setAuthorFileUrl($authorFileUrl)
    {
        $this->authorFileUrl = $authorFileUrl;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * @param string|null $headline
     *
     * @return Post
     */
    public function setHeadline($headline)
    {
        $this->headline = $headline;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string|null $body
     *
     * @return Post
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param string|null $tags
     *
     * @return Post
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFileUrl()
    {
        return $this->fileUrl;
    }

    /**
     * @param string|null $fileUrl
     *
     * @return Post
     */
    public function setFileUrl($fileUrl)
    {
        $this->fileUrl = $fileUrl;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string|null $link
     *
     * @return Post
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * @param \DateTime $publishedAt
     *
     * @return Post
     */
    public function setPublishedAt(\DateTime $publishedAt)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorLink()
    {
        switch ($this->getProvider()) {
            case 'twitter':
                return 'https://twitter.com/'.$this->getAuthorUsername();
                break;
            case 'facebook':
                return 'https://facebook.com/'.$this->getAuthorUsername();
                break;
            case 'instagram':
                return 'https://instagram.com/'.$this->getAuthorUsername();
                break;
        }
    }

    /**
     * @return boolean
     */
    public function getDuplicated()
    {
        return $this->duplicated;
    }

    /**
     * @param boolean $duplicated
     *
     * @return Post
     */
    public function setDuplicated($duplicated)
    {
        $this->duplicated = $duplicated;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalPostId()
    {
        return $this->originalPostId;
    }

    /**
     * @param string $originalPostId
     *
     * @return Post
     */
    public function setOriginalPostId($originalPostId)
    {
        $this->originalPostId = $originalPostId;

        return $this;
    }
}
