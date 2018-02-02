WP Safe Edit
========

Safely edit published posts behind the scenes without worrying about affecting the live site. You can publish your changes when you're ready, or throw them away if you change your mind.

## Requirements

TODO

## Installation

1. Download and activate the plugin in WordPress.

2. Register safe edit functionality for one or more post types:

```php
do_action( 'safe_edit_add_post_type_support', array( 'post', 'page' ) );
```

## Usage

1. A **"Create Fork"** button [Fig. 1] will be available for all post types you registered support for. Pressing this button will create a copy of the post where you can stage your changes. All post meta and taxonomy terms associated with the post will be included.

<img src="images/readme/create-fork-button.png" alt="Image of the “Create Fork” button." width="300"/>
Figure 1.

2. When editing a fork, it functions like any other post so you can do the following:
   * **Save Changes as a Draft:** Save your changes as a draft by pressing the **"Save Draft"** button. Changes saved as a draft will not be reflected on the live site until you publish them.
	 
   * **Preview Changes:** Preview your changes at any time by pressing the **"Preview"** button.
   
   * **Trash Changes:** If you change your mind, you can trash your changes by pressing the **"Move to Trash"** link.

3. Once you're happy with your changes, publish the changes back to the source post by pressing the **"Publish Changes"** button [Fig. 2] . The published post you created the fork from will be updated with your changes and reflected on the live site.

<img src="images/readme/publish-changes-button.png" alt="Image of the “Publish Changes” button." width="300"/>
Figure 2.

4. You can view previous forks that have been created and published using the **"Archived Forks"** meta box [Fig. 3].

<img src="images/readme/archived-forks.png" alt="Image of the “Archived Forks” meta box." width="516"/>
Figure 3.

## Caveats & Limitations

TODO
