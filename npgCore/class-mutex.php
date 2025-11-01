<?php

/**
 * Mutex class
 * @author Stephen
 *
 */
class npgMutex {

	private $locked = NULL;
	private $mutex = NULL;
	private $lock = NULL;

	function __construct($lock = 'npg', $concurrent = NULL, $folder = NULL) {
		global $_conf_vars;
		// if any of the construction fails, run in free mode (lock = NULL)
		if (defined('SERVERPATH')) {
			if (is_null($folder)) {
				$folder = SERVERPATH . '/' . DATA_FOLDER . '/' . MUTEX_FOLDER;
			}
			if (!is_dir($folder)) {
				mkdir_recursive($folder, (fileperms(__DIR__) & 0666) | 0311);
			}

			if ($concurrent > 1) {
				If ($lock = self::which_lock($lock, $concurrent, $folder)) {
					$this->lock = $lock;
				}
			} else {
				$this->lock = $folder . '/' . $lock;
			}
		}
		if (isset($_conf_vars['MUTEX_RUN_FREE'])) {
			$this->locked = $_conf_vars['MUTEX_RUN_FREE'];
		}
		return $this->lock;
	}

	// returns the integer id of the lock to be obtained
	// rotates locks sequentially mod $concurrent
	private static function which_lock($lock, $concurrent, $folder) {
		$locks = [];
		for ($i = 1; $i <= $concurrent; $i++) {
			$file = $folder . '/' . $lock . '_' . $i;
			if (file_exists($file)) {
				$locks[$file] = filemtime($file);
			} else {
				$locks[$file] = -1;
			}
		}
		asort($locks);
		foreach ($locks as $lock => $mtime) {
			if ($mtime === -1 || empty(filesize($lock))) {
				return($lock);
			}
		}
		//	no free locks, will have to wait...
		$lock = array_rand($locks);
		return $lock;
	}

	function __destruct() {
		if ($this->locked) {
			$this->unlock();
		}
	}

	public function lock() {
		//	if "flock" is not supported run un-serialized
		//	Only lock an unlocked mutex, we don't support recursive mutexes
		if (!$this->locked && $this->lock) {
			if ($this->mutex = @fopen($this->lock, 'wb')) {
				try {
					if (flock($this->mutex, LOCK_EX)) {
						$this->locked = true;
						ftruncate($this->mutex, 0);
						fwrite($this->mutex, getUserIP() . NEWLINE . 'Locked ' . gmdate('D, d M Y H:i:s') . " GMT" . NEWLINE);
						if (TEST_RELEASE) {
							ob_start();
							debug_print_backtrace();
							fwrite($this->mutex, ob_get_clean());
						}
						fflush($this->mutex);
					}
				} catch (Exception $e) {
					// what can you do, we will just have to run in free mode
					if (TEST_RELEASE) {
						debugLog('mutex lock failed: ' . $this->lock);
					}
					$this->locked = NULL;
				}
			}
		}
		return $this->locked;
	}

	/**
	 * 	Unlock the mutex.
	 */
	public function unlock() {
		if ($this->locked && $this->mutex) {
			//Only unlock a locked mutex.
			$this->locked = false;
			ftruncate($this->mutex, 0); //	which_lock prefers empty files
			flock($this->mutex, LOCK_UN);
			fclose($this->mutex);
			return true;
		}
		return false;
	}

}
