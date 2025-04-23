<?php

function bps_current_page ()
{
	$current = defined ('DOING_AJAX')?
		@ parse_url ($_SERVER['HTTP_REFERER'], PHP_URL_PATH):	// don't log any warnings if the HTTP_REFERER key is missing
		parse_url ($_SERVER['REQUEST_URI'], PHP_URL_PATH);

	return apply_filters ('bps_current_page', $current);		// published interface, 20190324
}

add_filter ('bp_ajax_querystring', 'bps_filter_members', 99, 2);
function bps_filter_members ($querystring, $object)
{
	if ($object != 'members')  return $querystring;

	$request = bps_get_request ('search');
	if (empty ($request))
	{
		$hide_directory = apply_filters ('bps_hide_directory', false);
		if ($hide_directory)
		{
			parse_str ($querystring, $args);
			$args['include'] = '0';
			$querystring = http_build_query ($args);
		}
		return $querystring;
	}

	$results = bps_search ($request);
	if ($results['validated'])
	{
		$users = $results['users'];

		parse_str ($querystring, $args);
		if (isset ($args['include']) && $args['include'] !== '')
		{
			$included = explode (',', $args['include']);
			$users = array_intersect ($users, $included);
		}

		$users = apply_filters ('bps_search_results', $users);
		if (count ($users) == 0)  $users = array (0);

		$args['include'] = implode (',', $users);
		$querystring = http_build_query ($args);
	}

	return $querystring;
}

function bps_search ($request, $users=null)		// published interface, 20190324
{
	$results = array ('users' => array (0), 'validated' => true);

	$fields = bps_parse_request ($request);
	foreach ($fields as $f)
	{
		if (!isset ($f->filter))  continue;
		if (!is_callable ($f->search))  continue;

		do_action ('bps_field_before_query', $f);
		$found = call_user_func ($f->search, $f);
		$found = apply_filters ('bps_field_search_results', $found, $f);

		$match_all = apply_filters ('bps_match_all', true);
		if ($match_all)
		{
			$users = isset ($users)? array_intersect ($users, $found): $found;
			if (count ($users) == 0)  return $results;
		}
		else
		{
			$users = isset ($users)? array_merge ($users, $found): $found;
		}
	}

	if (isset ($users))
	{
		if (count ($users) == 0)  return $results;
		$results['users'] = $users;
	}
	else
	{
		$results['validated'] = false;
	}

	return $results;
}

add_action ('bps_field_before_query', 'bps_field_before_query', 99, 1);
function bps_field_before_query ($f)
{
	if (bps_debug ())
	{
		$g = clone $f;
		$g->value = esc_html($f->value);
		echo "<!--\n";
		echo "query "; print_r ($g);
		echo "-->\n";
	}
}

add_filter ('bps_field_sql', 'bps_field_sql', 99, 2);
function bps_field_sql ($sql, $f)
{
	global $wpdb;

	if (bps_debug ())
	{
		$where = implode (' AND ', $sql['where']);
		$where = esc_html($wpdb->remove_placeholder_escape ($where));
		echo "<!--\n";
		echo "where $where\n";
		echo "-->\n";
	}
	
	return $sql;
}

add_filter ('bps_field_search_results', 'bps_field_search_results', 99, 2);
function bps_field_search_results ($found, $f)
{
	if (bps_debug ())
	{
		$ids = implode (',', $found);
		echo "<!--\n";
		echo "found $ids\n";
		echo "-->\n";
	}
	
	return $found;
}

if (!defined ('BPS_AND'))  define ('BPS_AND', ' AND ');
if (!defined ('BPS_OR'))  define ('BPS_OR', ' OR ');

function bps_is_expression ($value)
{
	$and = explode (BPS_AND, $value);
	$or = explode (BPS_OR, $value);

	if (count ($and) > 1 && count ($or) > 1)  return 'mixed';
	if (count ($and) > 1)  return 'and';
	if (count ($or) > 1)  return 'or';

	return false;
}

function bps_sql_expression ($format, $value, $escape=false)
{
	global $wpdb;

	foreach (array (BPS_AND, BPS_OR) as $split)
	{
		$values = explode ($split, $value);
		if (count ($values) > 1)  break;
	}

	$parts = array ();
	foreach ($values as $value)
	{
		if ($escape)  $value = '%'. bps_esc_like ($value). '%';
		$parts[] = $wpdb->prepare ($format, $value);
	}

	$join = ($split == BPS_AND)? ' AND ': ' OR ';
	return '('. implode ($join, $parts). ')';
}

function bps_esc_like ($text)
{
    return addcslashes ($text, '_%\\');
}
