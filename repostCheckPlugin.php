<?php

class repostCheckPlugin implements pluginInterface {

		/**
		 * Called when plugins are loaded
		 *
		 * @param mixed[]	$config
		 * @param resource 	$socket
		**/
		function init($config, $socket) {
			if (!isset($config['plugins']['repostCheck']['hostsToCheck']) || !is_array($config['plugins']['repostCheck']['hostsToCheck'])) {
				$config['plugins']['repostCheck']['hostsToCheck'] = array();
			}

			$this->config       = $config;
			$this->socket       = $socket;
            $this->pluginConfig = $config['plugins']['repostCheck'];

			$trace = debug_backtrace();
			if (isset($trace[0]['file']) && basename($trace[0]['file']) == "VikingBot.php") {
				$this->dir = dirname($trace[0]['file']);
			} else {
				$this->dir = getcwd();
			}

			$this->db = $this->loadDB();
		}

		/**
		 * Called about twice per second or when there are
		 * activity on the channel the bot are in.
		 *
		 * Put your jobs that needs to be run without user interaction here.
		 */
		function tick() {
		}

		/**
		 * Called when messages are posted on the channel
		 * the bot are in, or when somebody talks to it
		 *
		 * @param string $from
		 * @param string $channel
		 * @param string $msg
		 */
		function onMessage($from, $channel, $msg) {
			if (preg_match_all("/(http[s]{0,1}:\/\/[\S]+)/i", $msg, $matches)) {
				foreach ($matches[1] as $url) {
					$host = parse_url($url, PHP_URL_HOST);
					$continue = false;
					foreach ($this->pluginConfig['hostsToCheck'] as $hostToCheck) {
						if (strpos($host, $hostToCheck) !== false) {
							$continue = true;
							break;
						}
					}
					if ($continue === true) {
						if (isset($this->db[$channel][$url])) {
							if ($this->db[$channel][$url]["from"] != $from && strpos($msg, $this->db[$channel][$url]["from"]) === false) {
								$this->sendMessage($channel, "I smell repost! This was posted by " .
										$this->db[$channel][$url]["from"] . " " .
										$this->secondsToHumanReadableTime(time() - $this->db[$channel][$url]["timestamp"]) .
										" ago!"
								);
								return;
							}
						} else {
							$this->db[$channel][$url] = array(
								"from"      => $from,
								"timestamp" => time()
							);
						}
					}
				}
			}
		}

		/**
		 * Called when the bot is shutting down
		 */
		function destroy() {
			$this->saveDB($this->db);
		}

		/**
		 * Called when the server sends data to the bot which is *not* a channel message, useful
		 * if you want to have a plugin do it`s own communication to the server.
		 *
		 * @param string $data
		 */
		function onData($data) {
		}

		/**
		 * @param array $data
		 * @param string $name = "repostCheckPlugin"
		 *
		 * @return boolean
		 */
		function saveDB($db, $name = "repostCheckPlugin") {
			$file = $this->dir . DIRECTORY_SEPARATOR . "db" . DIRECTORY_SEPARATOR . $name . ".json";
			if (is_array($db)) {
				if (!empty($db)) {
					$json = json_encode($db);
					if (file_put_contents($file, $json) !== false) {
						return true;
					}
				} elseif(file_exists($file)) {
					unlink($file);
				}
			}
			return false;
		}

		/**
		 * @param string $name = "repostCheckPlugin"
		 *
		 * @return array
		 */
		function loadDB($name = "repostCheckPlugin") {
			$file = $this->dir . DIRECTORY_SEPARATOR . "db" . DIRECTORY_SEPARATOR . $name . ".json";
			if (file_exists($file) && ($db = file_get_contents($file)) !== false) {
				if ($db = json_decode($db, true)) {
					return $db;
				}
			}
			return array();
		}

		/**
		 * @param string $msg
		 * @param string $command
		 *
		 * @return string|boolean
		 */
		function getCommandQuery($msg, $command) {
			if(stringStartsWith(strtolower($msg), $this->config['trigger'] . $command)) {
				$query = str_replace($this->config['trigger'] . $command, "", $msg);
				$query = trim($query);
				return $query;
			} else {
				return false;
			}
		}

		/**
		 * @param string $to
		 * @param string $msg
		 * @param string|array $highlight = NULL
		 */
		function sendMessage($to, $msg, $highlight = NULL) {
			if ($highlight !== NULL) {
				if (is_array($highlight)) {
					$highlight = join(", ", $highlight);
				}
				$msg = $highlight . ": " . $msg;
			}
			sendMessage($this->socket, $to, $msg);
		}

		/**
		 * @param int $seconds
		 *
		 * @return string
		 */
		function secondsToHumanReadableTime($seconds) {
			if ($seconds >= 86400) {
				$days = round($seconds / 86400);
				if ($days > 1) {
					return $days . " days";
				} else {
					return $days . " day";
				}
			} elseif ($seconds >= 3600) {
				$hours = round($seconds / 3600);
				if ($hours > 1) {
					return $hours . " hours";
				} else {
					return $hours . " hour";
				}
			} elseif ($seconds >= 60) {
				$minutes = round($seconds / 60);
				if ($minutes > 1) {
					return $minutes . " minutes";
				} else {
					return $minutes . " minute";
				}
			} elseif ($seconds > 1) {
				return $seconds . " seconds";
			} else {
				return $seconds . " second";
			}
		}
}