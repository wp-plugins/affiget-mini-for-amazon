=== AffiGet Mini for Amazon ===
Contributors: sarutole
Tags: Amazon,products,reviews,marketing,monetization,revenue,Amazon Associate,freemium,affiliate marketing,aws,custom post type
Requires at least: 3.5
Tested up to: 4.2.2
Stable tag: 1.0.5
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Browse Amazon. "Like" products. Earn money.

== Description ==

AffiGet Mini is a free plugin to **remotely add Amazon products to your blog**.

If you want to **grow your review site or niche blog**, you will find AffiGet indispensable! 

Here's how this works:

1. While browsing Amazon, you find a product that might be of interest to your readers.
2. You click a button, and AffiGet **automatically adds a detailed product review** to your WordPress site.
3. Your visitors go to Amazon via a link on your site, and you get 4-10% commissions from all their qualifying purchases.

AffiGet utilizes the official Amazon Product Advertising API; therefore, **all product details are always correct and up-to-date**. To make money for featuring/promoting some Amazon products on your site, you have to join the [Amazon Associates](https://affiliate-program.amazon.com/ "Join now!") programme.

To have a fine control over how your reviews are displayed, consider upgrading to [AffiGet Pro](http://affiget.com) -- a premium version of this plugin.

= Automatic reviews =
AffiGet Mini **automatically adds the following details** to every new review:

* **Post title** (initially the same as product title, but you can change that);
* **Post slug** (calculated from the post title);
* **Post date** (automatically scheduled for the next available time-slot);
* **Featured image** (corresponds to the primary product image);
* **Gallery** (all product images are automatically downloaded and attached to the post);
* **Review text** (initially the same as editorial description of the product);
* **Post category** (based on Product Group attribute);
* **Post tags** (based on Product Type Name and Department attributes);
* **Comment status** (inherited from the last saved review);

= Bookmarklet features =
Here are the actions you can **perform remotely, without having to leave Amazon** product page:

* **Change post title** (post slug is updated accordingly);
* **Preview a post** (opened in a separate tab);
* **Open review for editing** (presented in a separate tab);
* **Make draft review public** (initially reviews are stored as drafts);
* **Delete review**;
* If a review for a current product already exists, it will not get duplicated -- you will be *modifying the existing one* instead.

= Administrative features =
In addition to the standard WordPress functionality, AffiGet Mini adds these **administrative productivity features** to the dashboard:

* A new "Reviews" content type.
* List of reviews:
	* Main product image is presented.
	* Includes a link to the product details page on Amazon.
* Review editing page:
	* A link to a product details page.
	* A button-link to re-fetch product data from Amazon servers.
	* A metabox for introduction text (which will be used as a post excerpt).
	* A metabox for the main review text (initially populated with an editorial description of a product).
	* A metabox for the conclusion text.
	* A metabox to select rating stars.
	* A metabox with pricing details.
	* A metabox with a call-to-action (i.e. an image which will represent a link to Amazon product page).

= Presentation features =
On the front side, as well as in the RSS feed, the reviews created by AffiGet will show up alongside your regular posts.

There are some elements that AffiGet Mini **automatically adds these elements to the review page**:

* **Product details table**: to save you time, this table is front-end editable. Supported actions:
	* Show/hide particular item;
	* Drag item to change sorting order;
	* In-place editing of item label;
	* In-place editing of item value;
	* Note, the order and labels for items in the table get inherited from the latest review in category.

* **Rating stars** can also be modified in-place, i.e. without a need to open the review editing page.
* **Main review text**;
* **Review conclusion**;
* **Pricing details**;
* **Call-to-action** (an image representing a link to Amazon product page).

Please note, that the presentation of Featured image, gallery, excerpt/introduction is defined by your theme -- AffiGet has no effect on how these elements are presented on your site.  

= Technical notes =

AffiGet is **engineered to work nicely with all well-behaved WordPress plugins and themes**. 

AffiGet *works with all Amazon sites* (Amazon.co.uk, Amazon.de, etc.).

AffiGet *can be easily translated* to different languages (contributions would be most welcome!).

= AffiGet Pro =

[AffiGet Pro](http://affiget.com "Upgrade today!") is a premium version of this plugin: it comes with **professional email support, extended configuration settings, and a number of widgets** (that integrate beautifully with Page Builder by SiteOrigin plugin to provide a fine control over the front-end presentation of your reviews).

== Installation ==

1. Upload the 'affiget-mini-for-amazon' folder to the /wp-content/plugins directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the AffiGet Settings page in WordPress dashboard: 
	* input your *Amazon Associates* credentials;
	* drag the *Afg+ button* to your Bookmarklets toolbar.
	
After you activate AffiGet, a new menu item is added to your Dashboard, labelled "Reviews".

The plugin is thoroughly tested with the latest versions of Firefox, Chrome and Safari. 

== Frequently Asked Questions ==

= Can I change my reviews once they are created? =
Absolutely! AffiGet pre-populates reviews with the available product data, but you can open it for editing and change it to your liking by modifying any of the regular post elements (like title, slug, excerpt, featured image, tags/categories, publishing date, post status, comment status, etc.). In addition to that, you will see elements specific to AffiGet reviews (main review text, pricing details, review conclusion, call-to-action button). For your convenience, the rating stars element and the product attributes table are even *front-end editable*!    

= Will AffiGet work with my WordPress theme? =
Yes! AffiGet has been developed to work with any WordPress installation. You might be required to add some custom styling for the layouts to appear correctly should your theme have CSS code that conflicts. For starters we’ve made it fully compatible with the default WordPress themes.

= What if I want a more control over how my review is presented? =
Consider upgrading to [AffiGet Pro](http://affiget.com/ "Upgrade today!") -- a premium version of this plugin: it is designed to work with the free and wildly popular drag & drop layout management plugin [Page Builder by SiteOrigin](https://wordpress.org/plugins/siteorigin-panels/ "Install now!").

== Screenshots ==

1. The Afg+ bookmarklet as seen in Chrome's Bookmarks bar.
2. The AffiGet Mini infobar slides into view when you click the Afg+ bookmarklet.
3. The infobar shows current title of the review post. Initially it is the same as product's title.
4. You can modify the title right on the infobar.
5. After you submit your new title, the post's slug will be modified, too.
6. You can click View (Edit) to open your new review in a new tab for preview (editing).
7. You can click Publish (Delete) to remotely publish (trash) your review post.
8. A review constructed by AffiGet Mini (using the standard Twenty Twelve theme). Note, the rating stars and the product attributes table are **front-end editable**!
9. The AffiGet Settings page
10. The Reviews page (note the product image and a link to the Amazon product page; te automatically assigned category is also presented).
11. The Review editing page. Note, highlighted elements are resolved automatically.

== Changelog ==

= Version 1.0.5 (2015-6-4) =
* Improved the overall look and feel of the Product details table.
* New feature: upon creation, the order and labels of the items for the Product details table are inherited from the latest review in category.
* Fix: reviews are included alongside regular posts on the front-end and in the RSS feeds.
* Fix: reviews are properly separated from the regular posts in the Dashboard.
* Fix: eliminated a PHP notice which occured on the edit review page.
* Fix: now review title does not get overwritten with a product title on resync.
* Fix: when no featured image is attached, now product images get re-fetched upon resync.
* Throughly tested with Firefox, Chrome, and Safari.

= Version 1.0.0 (2015-5-15) =
Initial release.