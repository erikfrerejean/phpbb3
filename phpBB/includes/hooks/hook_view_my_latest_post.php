<?php
/**
*
* @package phpBB3
* @copyright (c) 2010 Erik Frèrejean (erikfrerejean@phpbb.com) http://www.erikfrerejean.nl
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* @example
* By using a hook the only core edits required will be to the templates. You'll
* have to add the following code to the viewforum_body.html of every template
* at the place where the link should appear.
* <code>
*	<!-- IF topicrow.U_LATEST_POST --><a href="{topicrow.U_LATEST_POST}"><img src="LATEST_POST_IMG" /></a><!-- ENDIF -->
* </code>
*/

/**
* This function is called in template::display(), when called this function
* will do some manipulation on the $template->_tpldata array and will add
* a link to the last post of the user in a topic to the "topicrow" block array
*
* @param	phpbb_hook $hook The phpBB hook object
* @return	void
*/
function hook_view_my_latest_post(&$hook)
{
	global $db, $forum_id, $template, $user;
	global $phpbb_root_path, $phpEx;

	// When not on viewforum.php return
	if ($user->page['page_name'] != 'viewforum.' . $phpEx)
	{
		return;
	}

	// Don't bother with guests and bots
	if ($user->data['user_id'] == ANONYMOUS || $user->data['is_bot'])
	{
		return;
	}

	// Prepare the link, this way append_sid will only be called once
	$linkformat = append_sid($phpbb_root_path . 'viewtopic.' . $phpEx, array('f' => $forum_id, 't' => '%1$s', 'p' => '%2$s', '#' => 'p' . '%2$s'));

	// Lets collect all the topic IDs that are in this block. We'll have to read
	// this from the template data as $rowset gets unset before the code gets here
	$topic_ids = array();
	foreach ($template->_tpldata['topicrow'] as $row => $rowdata)
	{
		$topic_ids[$row] = $rowdata['TOPIC_ID'];
	}

	// Now select all required data by using the found topic_ids
	$sql = 'SELECT max(post_id) as post_id, topic_id, poster_id
		FROM ' . POSTS_TABLE . '
			WHERE poster_id = ' . $user->data['user_id'] . '
				AND ' . $db->sql_in_set('topic_id', $topic_ids) . '
			GROUP BY topic_id';
	$result	= $db->sql_query_limit($sql, sizeof($topic_ids));
	$set	= $db->sql_fetchrowset($result);
	$db->sql_freeresult($result);

	// Now flip the topic_ids array so we can access the block row number through
	// the topic ids
	$topic_ids = array_flip($topic_ids);

	// Loop through the set
	foreach ($set as $datarow)
	{
		// Determine where in the block row
		$rownr = $topic_ids[$datarow['topic_id']];

		// Build the url
		$url = sprintf($linkformat, $datarow['topic_id'], $datarow['post_id']);

		// Assign the link to the template
		$template->alter_block_array('topicrow', array(
			'U_LATEST_POST'	=> $url,
		), $rownr, 'change');
	}

	// Finally assign the img
	$template->assign_var('LATEST_POST_IMG', $phpbb_root_path . 'images/arrw.png');
}

// Register the hook
$phpbb_hook->register(array('template', 'display'), 'hook_view_my_latest_post');