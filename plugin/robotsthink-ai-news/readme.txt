=== RobotsThink AI News ===
Contributors: rasdo11
Tags: ai, news, automation, claude, anthropic
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 1.0.0
License: MIT

Automatically fetches AI news and publishes a Claude-written think piece every two days.

== Description ==

This plugin powers RobotsThink.com by:

1. Fetching the latest AI news headlines from NewsAPI every two days
2. Publishing a News Roundup post with links and summaries
3. Using Claude AI (Anthropic) to write an original think piece based on those headlines
4. Publishing the think piece automatically

== Installation ==

1. Upload the `robotsthink-ai-news` folder to `/wp-content/plugins/`
2. Activate the plugin in WordPress Admin → Plugins
3. Go to Settings → RobotsThink AI to configure your API keys
4. Enter your NewsAPI key (free at newsapi.org)
5. Enter your Claude API key (from console.anthropic.com)
6. Click "Run Now" to test, then let WP-Cron handle it every two days

== Frequently Asked Questions ==

= Does this cost money? =

NewsAPI has a free tier (100 requests/day — more than enough).
Claude API has a small cost per article (usually a few cents per think piece).

= How do I make sure WP-Cron runs reliably? =

BargainHost and most shared hosts run WP-Cron on page load. For reliability,
add this to your crontab via cPanel:
  */30 * * * * curl -s https://robotsthink.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1

== Changelog ==

= 1.0.0 =
* Initial release
