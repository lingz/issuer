issuer
======

Wordpress Issue Management Plugin
Adds an "Issue" taxonomy to your wordpress, so that 
you can filter posts by an issue. It also lets you set the current issue,
for display on the home screen.

**To the Home Page**
To get the current issue either wrap your existing query in `current_issue()`
```php
$query = new WP_Query(current_issue(array("posts_to_show" => 20)));
```
Or just use it straight if you have no existing query.
```php
$query = new WP_Query(current_issue());
```

**For the taxonomy/archive pages**
There exists a function get_issue which returns the appropriate query.
```php
get_issuek([$query, $issue_name, $issue_number]);
```

Wrap your query in the same way with get_issue(). Pass empty args where necessary.
```php
// for the taxonomy / archive page
$query = new WP_Query(get_issue());
// for custom queries

// by name
$query = new WP_Query(get_issue('', "issue 1"));

// by id
$query = new WP_Query(get_issue('', '', 1));
```

**Listing Issues**
```php
list_issues($limit, $orderby, $order);
```
a `$limit` of 0 returns all issues, `$orderby` lets you specify term fields such as `term_id`, `name`, etc, 
and `$order` lets you specify `ASC` or `DESC`.

This returns an `<ul class="issues-list">` with each element being a link contained in
`<li class="issue-item"><a href='issue-link'>issue-name</a></li>`

**Manual**
```
You can operate manually also instead of using the included helpers, what this plugin does 
is it registers a taxonomy called `issue` and creates an option called `current_issue` which
stores the ID of the current wordpress issue.

**Custom Ordering**
If you want to be able to custom order your issues, I recommend the excellent plugin by Zack Trollman called 
[Custom Taxonomy Sort](http://wordpress.org/plugins/custom-taxonomy-sort/).



