=== AffiGet Mini for Amazon ===
Contributors: sarutole
Tags: Amazon,products,reviews,review,book review,automatic,content,curation,online marketing,affiliate,affiliate marketing,ads,advertising,monetization,revenue,Amazon affiliates,Amazon Associate,review sites,niche blog,posting,post
Requires at least: 3.5
Tested up to: 4.2.2
Stable tag: 1.1.5
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Browse Amazon. "Like" products. Make money. (Or just create reviews for your blog with a single click.)

== Description ==

AffiGet Mini is a free plugin to **remotely add Amazon products to your blog**.

Use AffiGet to **build profitable review sites** and to **monetize your blog**!

Here's how this works:

1. While browsing Amazon, you find a product that might be of interest to your readers.
2. You click a button, and AffiGet **automatically adds a detailed product review** to your self-hosted WordPress site.
3. Your visitors go to Amazon via a link on your site, and you get 4-10% commissions from all their qualifying purchases.

AffiGet utilizes the official Amazon Product Advertising API; therefore, **all product details are always correct and up-to-date**. 

To make money for featuring/promoting some Amazon products on your site, you have to register for the [Amazon Associates](https://affiliate-program.amazon.com/ "Join now!") programme -- it's free and easy to join! With AffiGet, you can create reviews for products *from all international Amazon sites* (Amazon.co.uk, Amazon.de, etc.).

= Automatic content creation =

AffiGet Mini **automatically populates your review posts** with all the relevant product data and post meta information:

* post title
* auto-scheduled/current date
* category, tags
* product images
* product description
* product attributes
* product prices
* [... and more](https://wordpress.org/plugins/affiget-mini-for-amazon/other_notes "for more details see the Other notes section").

= Remote-editing capabilities =
 
AffiGet Mini comes with a powerful bookmarklet, which allows you to **remotely create and modify** the most important aspects of your reviews.
This means you can easily move from product to product, **effortlessly creating reviews as you go**.

= Useful administrative features =

AffiGet Mini seemlessly extends the admin Dashboard with **handy productivity features**:

* a thumb image and a link to the Amazon product page in the Reviews list; 
* a button-link to instantly resync product data; 
* metaboxes to modify the most relevant aspects of your review; 
* [... and more](https://wordpress.org/plugins/affiget-mini-for-amazon/other_notes "for more details see the Other notes section").

= Effective design without effort =

AffiGet does not force its design decisions on you -- the presentation of reviews takes cue from your WordPress theme. 

The reviews created with AffiGet will show up alongside your regular posts.

For more details, please see the [Other notes](https://wordpress.org/plugins/affiget-mini-for-amazon/other_notes "click for more details") section.

= Built by professionals =

AffiGet is **engineered to work nicely with all well-behaved plugins and themes**. It can be easily extended with custom add-ons via strategically placed programmatic hooks.

The plugin is thoroughly tested with the latest versions of *Firefox, Chrome, and Safari*.

AffiGet *can be easily translated* to different languages (contributions most welcome!).

= AffiGet Pro =

[AffiGet Pro](http://affiget.com "Upgrade today!") is a premium version of this plugin: it comes with **professional email support, extended configuration settings, and a number of widgets** (that integrate beautifully with Page Builder by SiteOrigin plugin to provide a fine control over the front-end presentation of your reviews).

== Other Notes ==
= Automatic content creation =

AffiGet Mini **automatically adds the following details** to every new review:

* **Post title** (initially the same as product title, but you can change that);
* **Post slug** (calculated from the post title);
* **Post date** (published instantly, or auto-scheduled for the next available time-slot);
* **Featured image** (corresponds to the primary product image);
* **Gallery** (all product images are automatically downloaded and attached to the post);
* **Review text** (initially the same as editorial description of the product);
* **Post category** (based on Product Group attribute);
* **Post tags** (based on Product Type Name and Department attributes);
* **Comment status** (inherited from the last saved review);

= Remote-editing capabilities =

Here are the actions you can **perform remotely, without having to leave Amazon** product page:

* **Change post title** (post slug is updated accordingly);
* **Preview a post** (opened in a separate tab);
* **Open review for editing** (presented in a separate tab);
* **Make draft review public** (initially reviews are stored as drafts);
* **Delete review**;
* If a review for a current product already exists, it will not get duplicated -- you will be *modifying the existing one* instead.

= Useful administrative features =

AffiGet Mini adds these **productivity features** to the Dashboard:

* A new "Reviews" content type.
* List of reviews:
	* Main product image is presented.
	* Includes a link to the product details page on Amazon.
* Review editing page:
	* A link to a product details page.
	* A button-link to re-fetch product data from Amazon servers.
	* A panel to choose between scheduling options: Auto or Now.
	* A metabox for introduction text (which will be used as a post excerpt).
	* A metabox for the main review text (initially populated with an editorial description of a product).
	* A metabox for the conclusion text.
	* A metabox to select rating stars.
	* A metabox with pricing details.
	* A metabox with a call-to-action (i.e. an image which will represent a link to Amazon product page).
	* A metabox to select which review elements will be displayed to your visitors and in what order.

= Effective design without effort =

On the front side, as well as in the RSS feed, the reviews created by AffiGet will show up alongside your regular posts.

These are the elements that AffiGet Mini **automatically displays on the review page**:

* **Product details table**: to save you time, this table is front-end editable. Supported actions:
	* Show/hide particular item;
	* Drag item to change sorting order;
	* In-place editing of item label;
	* In-place editing of item value;
	* Note, the order and labels for items in the table get **inherited from the latest review in category**.

* **Rating stars** can also be modified in-place, i.e. without a need to open the review editing page.
* **Main review text**;
* **Review conclusion**;
* **Pricing details**;
* **Call-to-action** (an image representing a link to Amazon product page).

You can also choose to automatically show Featured image on your review post.
You can also customize what elements will go into the excerpt (which might be used by your theme to render reviews on Search/Archive page and in RSS feeds).

Please note, that the for the newly created reviews display format gets inherited from the latest review in category.

= Built by professionals =

AffiGet is **engineered to work nicely with all well-behaved plugins and themes**. It can be easily extended with custom add-ons via strategically placed programmatic hooks.

The plugin is thoroughly tested with the latest versions of *Firefox, Chrome, and Safari*.

AffiGet *can be easily translated* to different languages (contributions most welcome!).

== Installation ==

1. Upload the 'affiget-mini-for-amazon' folder to the /wp-content/plugins directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the AffiGet Settings page in WordPress dashboard: 
	* input your *Amazon Associates* credentials;
	* drag the *Afg+ button* to your Bookmarklets toolbar.
	
After you activate AffiGet, a new post type and a corresponding menu item is added to your Dashboard, labeled "Reviews".

== Frequently Asked Questions ==

= Can I change my reviews once they are created? =
Absolutely! AffiGet pre-populates reviews with the available product data, but you can open it for editing and change it to your liking by modifying any of the regular post elements (like title, slug, excerpt, featured image, tags/categories, publishing date, post status, comment status, etc.). In addition to that, you will see elements specific to AffiGet reviews (main review text, pricing details, review conclusion, call-to-action button). For your convenience, the rating stars element and the product attributes table are even *front-end editable*!    

= Will AffiGet work with my WordPress theme? =
Yes! AffiGet has been developed to work with any WordPress installation. You might be required to add some custom styling for the layouts to appear correctly should your theme have CSS code that conflicts. For starters we’ve made it fully compatible with the default WordPress themes.

= What if I want more control over how my review is presented? =
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
10. The Reviews page (note the product image and a link to the Amazon product page; the automatically assigned category is also presented).
11. The Review editing page. Note, highlighted elements are resolved automatically.

== Changelog ==
= Version 1.1.5 (2015-6-22) =
* Improved review auto-scheduling (a new review gets autoscheduled or not depending on the latest modified review in the same category).
* Significantly improved usability of the Product Details table.
* Fix: Custom Fields panel is no longer cluttered with needless data.   

= Version 1.1.0 (2015-6-19) =
* New feature: select what elements are displayed on a review page, and in excerpt.
* New feature: add featured image on review page (in case your theme does not do it automatically).
* New feature: RSS feed now shows the elements that are configured to be included in an excerpt.
* Fix: a number of minor bugs and display quirks.

= Version 1.0.5 (2015-6-4) =
* Improved the overall look and feel of the Product details table.
* New feature: upon creation, the order and labels of the items for the Product details table are inherited from the latest review in category.
* Fix: reviews are included alongside regular posts on the front-end and in the RSS feeds.
* Fix: reviews are properly separated from the regular posts in the Dashboard.
* Fix: eliminated a PHP notice which occured on the edit review page.
* Fix: now review title does not get overwritten with a product title on resync.
* Fix: when no featured image is attached, now product images get re-fetched upon resync.
* Thoroughly tested with Firefox, Chrome, and Safari.

= Version 1.0.0 (2015-5-15) =
Initial release.