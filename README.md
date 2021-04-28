# Social Feed Plugin

The **Social Feed** Plugin is for [Grav CMS](http://github.com/getgrav/grav). This plugin allows you to easily fetch feeds from Facebook, Twitter and Instagram, and use them in your website, to make a social wall for example.

## Installation

Installing the Social Feed plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type:

    bin/gpm install social-feed

This will install the Social Feed plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/social-feed`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then, rename the folder to `social-feed`. You can find these files on [GitHub](https://github.com/moduleon/grav-plugin-social-feed) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/social-feed

> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error) and [Problems](https://github.com/getgrav/grav-plugin-problems) to operate.

## Configuration

### Set up apis configuration:

In your dashboard, go to `Plugins > Social Feed`, and make sure the plugin is enabled. Then, fill out in the form the api informations and usernames to follow regarding to the social network(s) you want to fetch posts from.

### Absolute urls:

By default, all images are downloaded in your user folder, in a media subfolder. All media will have a relative url like '/user/media/filename.ext'. If you want absolute urls, set `absolute_urls` to true in `user/config/system.yaml`.

## Usage

### Cli commands :

This plugin offers you a CLI. You must use it to fetch posts locally. In order to do that, after having fully configured the plugin in the dashboard, run in your terminal the following command:

`$ bin/plugin social-feed fetch:posts`

The better is, in our sense, to make a crontab about it, and let your server update your local feeds itself.

```
$ crontab -e
```

Press `i`, paste the following line for running the script every 15 minutes:

```
*/15 * * * * php bin/plugin social-feed fetch:posts
```

Press `escape`, then enter `:x`, press `enter`. It's done.

### Twig function:

To get posts from your twig templates, you can use the following function:

```
{% set postData = socialPosts() %}
<ul>
    {% for post in postData.posts %}
    <li>{{ post.body }}</li>
    {% endfor %}
</ul>
```

You can even pass parameters to the `socialPosts()` function:

| name      | type    | default       |
|-----------|---------|---------------|
| order_by  | string  | "publishedAt" |
| order_dir | string  | "DESC"        |
| limit     | integer | 10            |
| page      | integer | 1             |
| usernames | array   | empty         |
| providers | array   | empty         |
| duplicated| boolean | true          |
| tags      | array   | empty         |

For example:

```
{%
    set postData = socialPosts({
        order_by: 'provider',
        order_dir: 'ASC',
        limit: 50,
        providers:['facebook', 'twitter']
    })
%}
```

#### Tags

Tags for now only works for facebook posts. Using the Graph API Explorer from facebook u can check your tags in the message_tags field.

### Api entrypoint:

You can call the api entrypoint '/social-posts', and using the parameters above as query parameters.

For example:

```
GET /social-posts?order_by=provider&order_dir=ASC&limit=50&providers[]=facebook&providers[]=twitter
```

This will return a response like this:

```
{
    "total": 1,
    "limit": 10,
    "page": 1,
    "posts": [
        {
            "provider": "twitter",
            "postId": "794443718241775616",
            "authorUsername": "moduleon",
            "authorName": "moduleon",
            "authorFileUrl": "/user/media/twitter_moduleon.jpeg",
            "headline": "A Todo MVC example of Nucleon is out!\nhttps://t.co/lJEZ4AJbeG",
            "body": "A Todo MVC example of Nucleon is out!\n<a href=\"https://github.com/moduleon/todomvc/tree/develop/examples/nucleon\" target=\"_blank\">https://github.com/moduleon/todomvc/tree/develop/examples/nucleon</a>",
            "fileUrl": null,
            "link": "https://twitter.com/moduleon/status/794443718241775616",
            "publishedAt": "2016-11-04 07:38:37"
        }
    ]
}
```

### Manipulate posts :

Before saving a post internally, the CLI fires the event "onSocialPostBeforeSave". You can listen to it in a custom plugin, manipulate the post attributes, before it to be saved.

```php
<?php

namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class CustomPlugin extends Plugin
{
    /**
     * Subscribe to app events.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onSocialPostBeforeSave' => ['onSocialPostBeforeSave', 0],
        ];
    }

    /**
     * Pre save callback.
     */
    public function onSocialPostBeforeSave(Event $event)
    {
        $post = event->offsetGet('post');
        $post['myCustomAttribute'] = true;
        $event->offsetSet('post', $post);
    }
}
```

## Networks

### Instagram
To Fetch Posts form Instagram you need an user id (not the username) and an access token (preferably an long lived access token)
There can be found many instructions on the internet. But most are outdated and not written for the new Instagram API.
You need to create an app and keys on Facebook for Developers. This is a very good and actual manual:
https://levelup.gitconnected.com/getting-started-with-the-instagram-basic-display-api-5124c92c4935

### Facebook
1. Create an Facebook App on https://developers.facebook.com/
1. Create Access Token with manage_pages right with Graph API Explorer
1. Generate Long Lived Access Token with Access Token Debugger
1. Build Social Feed on your web page
1. Go through app review for manage_pages

## Credits

This plugin is mostly inspired by the symfony bundle
[GenjSocialFeedBundle](https://github.com/genj/GenjSocialFeedBundle)
