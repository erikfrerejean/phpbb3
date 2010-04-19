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
* @var Array Define an array containing all ids of the forums that should be excluded from
* the word censor
*/
$forums = array(

);

/**
* This hook will globally disable word censor for given forums
*
* @param phpbb_hook $hook phpBB hook instance
* @return void
*/
function hook_disable_word_censor_in_forum(phpbb_hook &$hook)
{
	global $auth, $config, $user;
	global $forums, $topic_data;

	// Not looking at a topic
	if (empty($topic_data))
	{
		return;
	}

	// Not ignoring censor settings here?
	if (!in_array($topic_data['forum_id'], $forums))
	{
		return;
	}

	// Setup the stuff so censor is blocked for this topic
	$auth->cache[0]['u_chgcensors'] = "1";
	$config['allow_nocensors'] = "1";
	$user->optionset('viewcensors', false);
}

// Register the hook
$phpbb_hook->register('phpbb_user_session_handler', 'hook_disable_word_censor_in_forum');