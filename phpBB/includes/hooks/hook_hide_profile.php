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
 * @ignore
 * To fully utalise this feature make the following file edit:
 *
 * Open: includes/functions_content.php; Find:
 * 		static $_profile_cache;
 *
 * After-add:
 * 		//-- Hidden profiles
 *		//-- Add:
 *		set_username_string_mode($mode, $user_id);
 *		//-- End Hidden profiles
 */

/**
 * @var Array Define an array containing all ids of the users whom profiles
 * 	   be hidden
 */
$users = array(
	2,
);

/**
 * Administratos can ignore this limit
 */
define('SHOW_ADMIN', true);

/**
 * Moderators can ignore this limit
 */
define('SHOW_MOD', true);

/**
 * The error message that is displayed when a user doesn't have access
 */
define('ERROR_MSG', 'You can not view this profile');

/**
 * This hook will hide profiles from given users from everyone besides the user
 * himself and (configurable) moderators and administrators
 *
 * @param phpbb_hook $hook phpBB hook instance
 * @return void
 */
function hook_hide_profile(&$hook)
{
	global $auth, $db, $user, $users;
	global $phpEx;

	// Viewing a profile?
	$mode = request_var('mode', '');
	if ($user->page['page_name'] != 'memberlist.' . $phpEx || $mode != 'viewprofile')
	{
		return;
	}

	// Determine the user_id
	$user_id	= request_var('u', 0);
	$username	= request_var('un', '', true);

	if (!empty($username))
	{
		$sql = 'SELECT user_id
			FROM ' . USERS_TABLE . "
			WHERE username_clean = '" . $db->sql_escape(utf8_clean_string($username)) . "'";
		$result		= $db->sql_query($sql);
		$user_id	= $db->sql_fetchfield('user_id', false, $result);
		$db->sql_freeresult($result);
	}

	// Not selected user?
	if (!in_array($user_id, $users))
	{
		return;
	}

	// Check access
	if ($user_id == $user->data['user_id'] || (SHOW_ADMIN == true && $auth->acl_getf_global('a_')) || (SHOW_MOD == true && $auth->acl_getf_global('m_')))
	{
		// Yay
		return;
	}

	trigger_error(ERROR_MSG);
}

/**
 * Function used to change the profile string mode
 *
 * @param	String	$mode	The requested mode, this is chagned by this function
 * @param	Integer	$id		The user id of the requested user
 * @return	void
 */
function set_username_string_mode(&$mode, $id)
{
	global $auth, $user, $users;

	// Not selected user?
	if (!in_array($id, $users))
	{
		return;
	}

	// Check access
	if ($id == $user->data['user_id'] || (SHOW_ADMIN == true && $auth->acl_getf_global('a_')) || (SHOW_MOD == true && $auth->acl_getf_global('m_')))
	{
		// Yay
		return;
	}

	// Switch mode
	$mode = 'no_profile';
}

// Register the hook
$phpbb_hook->register('phpbb_user_session_handler', 'hook_hide_profile');