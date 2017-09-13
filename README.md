# Build Your Own RSS Feed Plugin for WordPress

This plugin includes 3 short codes to build a category tree which generates an RSS feed URL in real-time.

*See it in action [here](https://retroroundup.com/subscribe-rss/).*

## Short Codes:

### `[byorf_tree]`

Builds a html/javascript category tree.  Any provided attributes are passed to
[`get_terms()`](https://developer.wordpress.org/reference/functions/get_terms/).
`exclude` or `exclude_tree` can be used to exclude unwanteded categories.

### `[byorf_link]`

The accomponying RSS link.  Provided separately so you can control placement and text.

### `[byorf]`

Equivalent to `[byorf_tree] [byorf_link]`.  Any attributes are passed to `get_terms()`, as above.

## Example

    [byorf_tree exclude_tree=1,2,3]

    Here is your custom feed:

    [byorf_link]
