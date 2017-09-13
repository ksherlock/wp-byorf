<?php
/**
 * @package Build Your Own RSS Feed
 * @version 1.0
 */
/*
Plugin Name: Build Your Own RSS Feed
Plugin URI: https://github.com/ksherlock/wp-byorf
Description: Display a nice tree for creating an RSS feed by category.
Author: Kelvin Sherlock
Version: 1.0
*/


function byorf_form(&$array, $parent = 0){
	$rv = '';

	$children = array();
	foreach($array as &$item) {
		if ($item->parent == $parent)
			array_push($children, $item);
	}

	if (empty($children)) return $rv;

	if ($parent == 0) {
		$rv .= '<div><form id="byorf_form">';
	}

	$rv .= '<ul>';
	foreach($children as &$item) {
		$tmp = byorf_form($array, $item->term_id);

		#if ($parent == 0 && empty($tmp)) continue; // skip empty top-level.
		
		$rv .= '<li>';
		$rv .= '<input type="checkbox"  value="' . $item->term_id . '" />';
		$rv .= htmlspecialchars($item->name) . "\n";
		if (!empty($tmp)) { $rv .= $tmp; }
		$rv .= "</li>\n";
	}
	$rv .= "</ul>\n";

	if ($parent == 0) {
		$rv .= '</form></div>';
	}
	return $rv;
}

function byorf_js() {

	$href = get_bloginfo('rss2_url');
	return <<<EOT
<script>
function byorf_rebuild(e){
	var target = e.target;
	var term_id = e.target.value;
	var checked = e.target.checked;
	var term = byorf_map[term_id];

	// if this is a parent, update all children.
	var children = byorf_children[term_id] || [];
	children.forEach(function(e){
		e.element.checked = checked;
	});

	// if this is a child, cancel all parents.
	if (!checked && term) {
		var parent = term.parent;
		while (parent) {
			parent.element.checked = false;
			parent = parent.parent;
		}
	}

	var elements = document.getElementById('byorf_form') || [];
	var checked = [].filter.call(elements, function(x) {
		return x.type == 'checkbox' && x.checked; 
	});

	var list = checked.map(function(x){return x.value;}).sort();
	var href = "$href";
	if (list.length) href += "?cat=" + list.join(',');
	var a = document.getElementById('byorf_link');
	a.href = href;
	a.textContent = href;

}

jQuery(document).ready(function(){

	jQuery('#byorf_form').change(byorf_rebuild);

	var elements = document.getElementById('byorf_form') || [];
	[].forEach.call(elements, function(e){
		if (e.type != 'checkbox') return;
		var term_id = e.value;
		byorf_map[term_id].element = e;
	});

	// fixup parents.
	for (var k in byorf_map) {
		if (!byorf_map.hasOwnProperty(k)) continue;

		var e = byorf_map[k];
		var parent = e.parent;
		if (parent === 0) e.parent = undefined;
		else e.parent = byorf_map[e.parent];
	}


	for (var k in byorf_children) {
		if (!byorf_children.hasOwnProperty(k)) continue;

		byorf_children[k] = byorf_children[k].map(function(n){
			return byorf_map[n];
		});

	}
/*
	//fixup parents.
	for (var k in byorf_parents) {
		if (!byorf_parents.hasOwnProperty(k)) continue;

		byorf_map[k].parents = byorf_parents[k].map(function(n){
			return byorf_map[n];
		});
	}
*/


});

</script>
EOT;
}

function byorf_map(&$array) {
	$map = array();

	foreach($array as &$item) {
		$map[$item->term_id] = array(
			'term_id' => $item->term_id,
			'parent' => $item->parent,
			'name' => $item->name
		);
	}

	return '<script>byorf_map = ' . json_encode($map) . ';</script>';
}

function byorf_parents_helper(&$array, $parent, &$tree) {

	$rv = array();

	foreach($array as &$item) {

		if ($item->parent == $parent) {
			$term_id = $item->term_id;
			$tmp = byorf_parents_helper($array, $term_id, $tree);
			$tree[$term_id] = $tmp;
			foreach ($tmp as $x) { array_push($rv, $x); }
			array_push($rv, $term_id);
		}
	}
	return $rv;
}

function byorf_parents(&$array) {

	# build a tree, then invert it.

	$tree = array();
	byorf_parents_helper($array, 0, $tree);

#	$parents = array();
#	foreach ($tree as $parent => $children) {
#		# code...
#		foreach ($children as $child) {
#			if (isset($parents[$child])) array_push($parents[$child], $parent);
#			else $parents[$child] = array($parent);
#		}
#	}


	return '<script>byorf_children = ' . json_encode($tree) . ';</script>';

}


function byorf_terms($attr = '') {

	// $args = array(
	// 	'taxonomy' => 'category',
	// 	'orderby' => 'parent,name',
	// 	'hide_empty' => false
	// );

	$args = wp_parse_args($attr);
	$args['taxonomy'] = 'category';
	$args['orderby'] = 'parent,name';
	$args['hide_empty'] = false;


	$array = get_terms($args);
	function cmp($a, $b){
		if ($a->parent < $b->parent) return -1;
		if ($a->parent > $b->parent) return 1;

		if ($a->name < $b->name) return -1;
		if ($a->name > $b->name) return 1;

		if ($a->term_id < $b->term_id) return -1;
		if ($a->term_id > $b->term_id) return 1;
		return 0;
	}
#	function map($a) {
#		return array(
#			'name' => $a->name,
#			'term_id' => $a->term_id,
#			'parent' => $a->parent
#		);
#	}
	usort($array, 'cmp');
	return $array;
}

function byorf($attr = '') {

	$array = byorf_terms($attr);

	$html = array();

	$href = get_bloginfo('rss2_url');
	$html[] = byorf_form($array, 0);
	$html[] = '<div><a id="byorf_link" href="' . $href . '">' . $href . '</a></div>';
	$html[] = byorf_map($array);
	$html[] = byorf_parents($array);
	$html[] = byorf_js();

	return implode("\n", $html);
}

function byorf_link() {
	$href = get_bloginfo('rss2_url');

	return '<div><a id="byorf_link" href="' . $href . '">' . $href . '</a></div>';
}

function byorf_tree($attr = '') {

	$array = byorf_terms($attr);

	$html = array();

	$href = get_bloginfo('rss2_url');
	$html[] = byorf_form($array, 0);
	$html[] = byorf_map($array);
	$html[] = byorf_parents($array);
	$html[] = byorf_js();

	return implode("\n", $html);

}


function byorf_init() {
	add_shortcode('byorf', 'byorf');
	add_shortcode('byorf_link', 'byorf_link');
	add_shortcode('byorf_tree', 'byorf_tree');
}
add_action('init', 'byorf_init');

?>
