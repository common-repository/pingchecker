=== Pingchecker ===
Contributors: majick777
Donate link: http://pingbackpro.com/pingchecker/
Tags: ping, pingchecker, pingback, trackback, backlink, incoming links, link exchange, refback, related posts, seo, blog commenting
Requires at least: 2.6
Tested up to: 3.1
Stable tag: 1.2.0

Scans post for links, checks if they are pingeable and sends pingbacks with results returned, improves chances of successful pings!

== Description ==

Pingchecker is a free plugin for WordPress that allows you to scan your post's content for
links, check the pingability of those resources you've linked to, and manually ping those 
pages. This improves upon the inbuilt fuctionality of WordPress by allowing you to receive 
the results of your attempted pings whereas WordPress doesn't. (With WordPress your ping 
either appears in the trackback list or it doesn't, with no explanation or error codes.)

Also included is a workaround for a bug in the Wordpress XML RPC server that prevents many
of your pingbacks from succeeding without you even knowing about it! When you ping another 
blogs server, it will check the page you linked, BUT because of this bug, sometimes it can't 
find the link at all. This workaround adds a hidden div to your footer with your links so 
they can be found, greatly improving your chances of a successful ping. 

== Installation ==

1. Upload 'pingchecker.php' to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Use the Pingchecker box on your post writing screen.

== Frequently Asked Questions ==

= What do the pingback error codes mean? =

While some of the pingback error codes are self-explanatory, others can be a little confusing.
There is a list of fault codes and some suggestions here:
http://pingbackpro.com/support/#faultcodes

= What if the resource I am linking to isn't pingeable? =

Unfortunately there isn't a great deal you can do about this, apart from sending an email
to the owner of the blog you are pinging, you could try to find a similar resource that
is pingeable.

= Will installing this plugin improve the success of my pings? =

Yes, actually. Pingchecker includes a workaround for a bug in the Wordpress XML RPC server that
can frequently return pingback fault 17, which basically says your post doesn't contain a link 
when it really does. A hidden div element is added to page containing the links in your post 
which makes them easier to find by the server code, allowing more pings to succeed.

== Screenshots ==

1. screenshot-1.png is a screenshot of the Pingchecker interface.

== Changelog ==

= 1.2.0 =
* Added the ability to check for pingback approvals.

= 1.1.0 =
* Fixes a major Wordpress XML RPC server bug with a workaround. See Note.

= 1.0.0 =
* Pingchecker Plugin released. WOOHOO!

== Upgrade Notice ==

= 1.1.0 =
Major update to include the new workaround for the XML RPC server bug, improving your ping success rate.

== Recommended Use ==

1. Before publishing your post, use Pingchecker to check the pingability of the resources 
you are linking to. If they aren't, you may wish to choose alternative similar resources 
that are pingable instead.
2. Then, publish your post and WordPress will attempt to ping the resources automatically 
as it normally would. Check the trackback list under your content box to see if your ping 
was successful as usual.
3. If the new trackback/pingback does not appear, use Pingchecker to ping the resource 
instead. The results of your attempted pings will be returned in an alert box. 

== The XML RPC Server Bug Workaround ==

While working on this plugin I noticed a large occurrence of the pingback fault 17:
"The source URL does not contain a link to the target URL, and so cannot be used as a source."
A really frustrating message given you are sending a pingback because the source DOES contain
a link to the target, yes? Might I point out that very few Wordpress users are aware of this 
even happening because nowhere does Wordpress actually return you these fault codes..!

Well, after a bit of testing I found the bug seems to be in the XML RPC server code for 
Wordpress, specifically the strip_tags function in PHP is just not reliable enough for getting
anchor links on the variety of Wordpress templates out there (IMHO). (Line 3422 in WP3.1)

Unfortunately, since the bug is in the server code itself, you can't fix it on someone elses
blog can you? That's why this is a workaround instead. The Pingchecker workaround will scan
your post content for links using regex instead, then echo a hidden div element containing
all the links (with an added nofollow tag so you aren't linking twice) in your blogs footer,
which is picked up much more easily by the strip_tags function in use by the server.