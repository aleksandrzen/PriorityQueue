<?php
namespace aleksandrzen\PriorityQueue;

/**
 * Class PriorityQueueTrait.
 *
 * Base functionality for implementation new queues.
 * Suitable only for those classes, who extends PriorityQueueAbstract.
 *
 * @package aleksandrzen\PriorityQueue
 */
trait PriorityQueueTrait {

	/**
	 * @var $_logger
	 */
	protected $_logger;

	/**
	 * @param string $errorMessage
	 */
	protected function log($errorMessage) {
		if ($this->_logger !== null) {
			$this->_logger->error($errorMessage);
		}
	}

	/**
	 * Triggered when any problem with insertion occurred.
	 *
	 * @param array|\MongoException|\ErrorException $exception
	 */
	protected function insertErrorOccurred($exception) {
		if (is_array($exception)) {
			$exception = implode(';', $exception);
		} else {
			$exception = (string) $exception;
		}

		$this->log($exception);
	}

	/**
	 * @author Aleksandr Yuriev <aleksandrzen@gmail.com>
	 *
	 * @param $logger
	 */
	public function setLogger($logger) {
		$this->_logger = $logger;
	}

}