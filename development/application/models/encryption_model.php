<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
/* Author: Dave Timmerman
 * Description: Encryption and decryption model class
 */
class Encryption_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function how_it_works()
	{
		/////////////////////////////////
		// SERVER-SIDE
		/////////////////////////////////

		require 'application/libraries/encryption/aes.class.php';
		//$aes_key = abcdefgh01234567"; 					// 128-bit key
		//$aes_key = "abcdefghijkl012345678901"; 			// 192-bit key
		//$aes_key = "abcdefghijuklmno0123456789012345"; 	// 256-bit key
		$session_key = "4%tGbNMj87&9o_2D";
		// RANDOMIZE!!
		$flash_key = "l(u%tGhF4E#7Jk!M";
		// DUPLICATE IN FLASH!!
		$highscore_key = "98&jhJKkMHfr^%4y";
		// RANDOMIZE!!
		$session_secret = "some_unique_session_secret";
		// getGUID !!

		$start = microtime(true);
		$timestamp = time();
		$rand = rand();

		/////////////////////////////////
		// SERVER-SIDE
		/////////////////////////////////
		echo "====== SERVER-SIDE ======<br /><br />";

		echo "<i>session_key, session_secret, timestamp, random_number are set by developer</i><br />";
		echo "<strong>session_key :: " . $session_key . "</strong><br />";
		echo "<strong>session_secret :: " . $session_secret . "</strong><br />";
		echo "<strong>timestamp :: " . $timestamp . "</strong><br />";
		echo "<strong>random_number :: " . $rand . "</strong><br /><br />";

		echo "<i>session_token is an encrypted concatenation of session_secret, timestamp, random_number</i><br />";
		echo "<i>session_token = hex-encoding( AES(session_key)->encryped(session_secret, timestamp, random_number))</i><br />";
		$aes = new AES($session_key);
		$session_token = urlencode($aes -> encrypt($session_secret . "-" . $timestamp . "-" . $rand));
		echo "<strong>session_token :: " . $session_token . "</strong><br /><br />";

		echo "<i>send session_token to client-side</i><br /><br />";

		echo "<i>flash_key, highscore_key are set by developer</i><br />";
		echo "<strong>flash_key :: " . $flash_key . "</strong><br />";
		echo "<strong>highscore_key :: " . $highscore_key . "</strong><br /><br />";

		echo "<i>highscore_key_token is an encrypted highscore_key</i><br />";
		echo "<i>highscore_key_token :: hex-encoding( AES(flash_key)->encryped(highscore_key))</i><br />";
		$aes = new AES($flash_key);
		$highscore_key_token = urlencode($aes -> encrypt($highscore_key));
		echo "<strong>highscore_key_token :: " . $highscore_key_token . "</strong><br /><br />";

		echo "<i>send highscore_key_token to client-side</i><br /><br />";

		/////////////////////////////////
		// CLIENT-SIDE / FLASH APP
		/////////////////////////////////

		echo "====== CLIENT-SIDE ======<br /><br />";

		echo "<i>Decrypt the highscore_key_token, which it can do because you hardcoded the flash_key into the Flash binary.</i><br />";
		echo "<i>highscore_key_decrypted = AES(flash_key)->decryped(hex-decoding(highscore_key_token));</i><br />";
		$aes = new AES($flash_key);
		$highscore_key_decrypted = $aes -> decrypt(urldecode($highscore_key_token));
		echo "<strong>highscore_key_decrypted :: " . $highscore_key_decrypted . "</strong><br /><br />";

		echo "<i>You encrypt the highscore with the highscore_key_decrypted, along with the SHA1 hash of the highscore (200 points).</i><br />";
		$highscore = strval(200);
		echo "<strong>highscore :: " . $highscore . "</strong><br />";
		echo "<strong>sha1 highscore :: " . sha1($highscore) . "</strong><br /><br />";

		echo "<i>highscore_token = hex-encoding( AES(highscore_key_decrypted)->encryped(highscore, SHA1(highscore))).</i><br />";
		$aes = new AES($highscore_key_decrypted);
		$highscore_token = urlencode($aes -> encrypt($highscore . "-" . sha1($highscore)));
		echo "<strong>highscore_token :: " . $highscore_token . "</strong><br /><br />";

		echo "<i>Send session_token to the server and the PHP code checks the token to make sure the request came from a valid game instance.</i><br />";
		echo "<i>Send highscore_token to the server and the PHP code checks the token to make sure the request came from a valid game instance.</i><br />";
		echo "<i>Decrypt encrypted highscore. Check to make sure the highscore matches the SHA1 of the highscore (absent this, decryption will simply produce random, likely very high, highscores).</i><br /><br />";

		/////////////////////////////////
		// SERVER-SIDE
		/////////////////////////////////
		echo "====== SERVER-SIDE ======<br /><br />";

		echo "<i>Recieve session_token_encrypted from flash app</i><br />";
		$aes = new AES($session_key);
		$session_token_decrypted = $aes -> decrypt(urldecode($session_token));
		echo "<strong>session_token_decrypted :: " . $session_token_decrypted . "</strong><br /><br />";

		echo "<i>Recieve highscore_token_encrypted from flash app</i><br />";
		$aes = new AES($highscore_key);
		$highscore_token_decrypted = $aes -> decrypt(urldecode($highscore_token));
		echo "<strong>highscore_token_decrypted :: " . $highscore_token_decrypted . "</strong><br /><br />";

		$end = microtime(true);
		echo "<strong>All this took " . ($end - $start) . " ms</strong>";
		// time for data sanity check

		// ALSO
		/*
		 Here are some things that can actually reduce high score fraud:
		 Require a login to play the game, have the login produce a session cookie,
		 * and don't allow multiple outstanding game launches on the same session,
		 * or multiple concurrent sessions for the same user.

		 Reject high scores from game sessions that last less than the shortest
		 * real games ever played (for a more sophisticated approach, try "quarantining"
		 * high scores for game sessions that last less than 2 standard deviations
		 * below the mean game duration). Make sure you're tracking game durations serverside.

		 Reject or quarantine high scores from logins that have only played the game
		 * once or twice, so that attackers have to produce a "paper trail" of
		 * reasonable looking game play for each login they create.

		 "Heartbeat" scores during game play, so that your server sees the score
		 * growth over the lifetime of one game play. Reject high scores that don't
		 * follow reasonable score curves (for instance, jumping from 0 to 999999).

		 "Snapshot" game state during game play (for instance, amount of ammunition,
		 * position in the level, etc), which you can later reconcile against recorded interim scores.
		 * You don't even have to have a way to detect anomalies in this data to start with;
		 * you just have to collect it, and then you can go back and analyze it if things look fishy.

		 Disable the account of any user who fails one of your security checks
		 * (for instance, by ever submitting an encrypted high score that fails validation).

		 Remember though that you're only deterring high score fraud here.
		 * There's nothing you can do to prevent if. If there's money on the line in your game,
		 * someone is going to defeat any system you come up with. The objective isn't to stop this attack;
		 * it's to make the attack more expensive than just getting really good at the game and beating it.
		 */
	}

}
?>