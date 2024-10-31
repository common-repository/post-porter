=== Post Porter ===
Contributors: weboccults
Tags: restapi, API, import export, import export custom post type
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 6.2 or higher
Tested up to: 6.5
Stable tag: 1.0.1
Version: 1.0.1
Requires PHP: 7.0


Post Porter enables seamless posts migration between WordPress sites via REST API, ensuring alignment with standard post principles.

== Description ==

Post Porter | Import any post type from one Wordpress site to another Wordpress site with one click using REST API. 

For the best results and ease of use, ensure that the content you want to import or export closely resembles the structure of standard posts or is based on similar principles.


== Features ==

- It allows users to import and export any post type including custom post types between their wordpress websites.
- It allows users to select from which post type to import data in which post type of current website.
- It also import posts with custom taxonomy(Custom Categories / Tags) if its defined in imorting website.
- It allows users to cancel importing process in while background process is running.
- Securely import and export data by key based authentication.
- Importing process done in background so, it will not affect your other processes.


== Steps to Use ==

- Install plugin in website from you want to import posts.
- After installation goto export key page of plugin and copy website url and copy export key.
- Install plugin in website where you want to import posts.
- After installation goto post porter page of plugin and paste copied website url and export key then click on submit.
- After submit there is two select box to select post types.
- After selection of post types click on save settings button to save settings.
- After that import post button is enabled click on import posts button to start importing process.
- For checking log details goto import logs page of website.
- By click on clear logs button all the logs details will be cleared.


== Note ==

- This plugin use [WP Background Processing Library](https://github.com/deliciousbrains/wp-background-processing) so if you have any active plugin which using the same library or plugin used to import data in background then deactivate it to avoide any conflicts.
- Post Porter is not compatible with all custom post types, especially those with highly customized structures or unique data fields. It is recommended to test the plugin thoroughly if you intend to use it with custom post types other than standard posts.

== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png
3. screenshot-3.png
4. screenshot-4.png
5. screenshot-5.png
6. screenshot-6.png

== Installation ==

= Wordpress =

Search for "Post Porter" and install with that slick **Plugins > Add New** back-end page.

 &hellip; OR &hellip;

Follow these steps:

 1. Download the archive.

 2. Upload the zip file via the Plugins > Add New > Upload page &hellip; OR &hellip; unpack and upload with your favorite FTP client to the /plugins/ folder.

 3. Activate the plugin on the Plugins page.

Done!