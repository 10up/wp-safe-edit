WP Safe Edit
========

Safely edit published posts behind the scenes and publish the changes when ready.

## Installation

1. Download and install the plugin in WordPress.

2. Register safe edit functionality for one or more post types:

```php
do_action( 'safe_edit_add_post_type_support', array( 'post', 'page' ) );
```

## Usage

1. Once safe edit functionality has been registered for a post type, you'll see a new "Create Fork" button when editing a post. To safely edit a post without affecting the published version, press the "Create Fork" button. A copy of the post is created where you can stage your changes.

2. When editing a fork, it functions like any other post:
   * **Save Changes as a Draft:** Save your changes as a draft by pressing the "Save Draft" button. Changes saved as a draft will not be reflected on the live site until you publish them.
	 
   * **Preview Changes:** Preview your changes at any time by pressing the "Preview" button.

3. Once you're happy with your changes, publish the changes back to the source post by pressing the "Publish Changes" button. The published post you created the fork from will be updated with your changes and reflected on the live site.
