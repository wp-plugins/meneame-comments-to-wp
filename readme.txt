=== Meneame Comments to WP ===
Contributors: blogestudio
Tags: comments meneame rss
Tested up to: 2.6.2
Stable tag: 0.0.9
Requires at least: 2.5.1

You can now import meneame user comments from your blog's posts that have been sent
to meneame.

== Description ==

This plugin adds comments from your posts sent to "[Menéame](http://meneame.net/)" to
your blog's comments list automatically.

The system detects trackbacks from "Menéame" and starts a 7-day task that downloads 
the new comments, every 30 minutes, adding them to your blog.

See the [Meneame Comments to WP page](http://blogestudio.com/plugins/meneame-comments/) for further information.

== Installation ==

1. Upload the plugin directory to your "plugins", or "mu-plugins", directory.

2. Activate the plugin, if you've installed it in the "mu-plugins" it's not necessary.

3. Now go to Settings->Menéame Comments to WP and click "Menéame Comments First Load". 
This will download all the comments of every post sent to meneame and it will activate
a system to check for new comments in meneame for posts sent less than 7 days ago.

4. This system works automatically, so there is no need to intervene.

== Frequently Asked Questions ==

By default your posts comments and meneame comments will be mixed in your comments template.
If you want to have them seperately follow these instructions:

= How can I list only comments of "Menéame"? =

We have included a function that returns the list of the comments, called "meneame_comments__only_meneame."

*   meneame_comments__only_meneame(`post_id=[ID]`);

= How can I list comments without including the meneame comments? =

We have included a function that returns the list of the comments, called "meneame_comments__without_meneame."

*   meneame_comments__without_meneame(`post_id=[ID]&comments=[0|(1)]&trackbacks=[0|(1)]`);

== Updates ==

Plugin updates will be posted here [Blogestudio](http://blogestudio.com/plugins/meneame-comments/) and it will always link to the newest version.

== Thanks ==

We would sincerely like to thank [Enrique Dans](http://www.enriquedans.com/) for giving us the idea, 
[Ricardo Galli](http://ricardogalli.com/) for helping us with Menéame, and last but not least ;-)
all the betatesters who helped us http://blogestudio.com/2008/09/22/plugin-wordpress-comentarios-de-meneame-en-wordpress/
