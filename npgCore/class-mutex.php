<?php

/**
 * Mutex class
 * @author Stephen
 *
 */
class npgMutex {

	private $locked = NULL;
	private $ignoreUseAbort = NULL;
	private $mutex = NULL;
	private $lock = NULL;

	function __construct($lock = 'npg', $concurrent = NULL, $folder = NULL) {
		// if any of the construction fails, run in free mode (lock = NULL)
		if (defined('SERVERPATH')) {
			if (is_null($folder)) {
				$folder = SERVERPATH . '/' . DATA_FOLDER . '/' . MUTEX_FOLDER;
			}
			if (!is_dir($folder)) {
				mkdir_recursive($folder, (fileperms(__DIR__) & 0666) | 311);
			}

			if ($concurrent > 1) {
				If ($subLock = self::which_lock($lock, $concurrent, $folder)) {
					$this->lock = $folder . '/' . $lock . '_' . $subLock;
				}
			} else {
				$this->lock = $folder . '/' . $lock;
			}
		}
		return $this->lock;
	}

	// returns the integer id of the lock to be obtained
	// rotates locks sequentially mod $concurrent
	private static function which_lock($lock, $concurrent, $folder) {
		$count = false;
		$counter_file = $folder . '/' . $lock . '_counter';
		if ($f = fopen($counter_file, 'a+')) {
			if (flock($f, LOCK_EX)) {
				clearstatcache();
				fseek($f, 0);
				$data = fgets($f);
				$count = (((int) $data) + 1) % $concurrent;
				ftruncate($f, 0);
				fwrite($f, "$count");
				fflush($f);
				flock($f, LOCK_UN);
				fclose($f);
				$count++;
			}
		}
		return $count;
	}

	function __destruct() {
		if ($this->locked) {
			$this->unlock();
		}
	}

	public function lock() {
		//if "flock" is not supported run un-serialized
		//Only lock an unlocked mutex, we don't support recursive mutex'es
		if (!$this->locked && $this->lock) {
			if ($this->mutex = @fopen($this->lock, 'wb')) {
				try {
					if (flock($this->mutex, LOCK_EX)) {
						$this->locked = true;
						//We are entering a critical section so we need to change the ignore_user_abort setting so that the
						//script doesn't stop in the critical section.
						$this->ignoreUserAbort = ignore_user_abort(true);
					}
				} catch (Exception $e) {
					// what can you do, we will just have to run in free mode
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
		if ($this->locked) {
			//Only unlock a locked mutex.
			$this->locked = false;
			ignore_user_abort($this->ignoreUserAbort); //Restore the ignore_user_abort setting.
			flock($this->mutex, LOCK_UN);
			fclose($this->mutex);
			return true;
		}
		return false;
	}

}
