<?php

namespace aleksandrzen\PriorityQueue;

/**
 * Class EmailsQueue.
 *
 * @package Service\Postman\Queues
 */
class EmailsQueue extends PriorityQueueAbstract {

	use PriorityQueueTrait;

	const PROBLEM_MAILS_PRIORITY = 2;
	const LOW_MAILS_PRIORITY     = 3;
	const DIRECT_MAILS_PRIORITY  = 5;
	const DEFAULT_MAILS_PRIORITY = 7;
	const URGENT_MAILS_PRIORITY  = 73;

	protected $_collectionName = 'emails';

	protected $_defaultPriority = self::DEFAULT_MAILS_PRIORITY;

	/**
	 * Alias for native interface.
	 *
	 * @param string     $message     The value to insert.
	 * @param int|null   $priority
	 * @param array|null $description Additional field, which will be written in new document.
	 *
	 * @return bool true if insertion was successful.
	 *
	 * @throws \InvalidArgumentException if $value is not a string.
	 * @throws \InvalidArgumentException if $priority is not an integer.
	 * @throws \ErrorException           if insertion failed.
	 */
	public function send($message, $priority = null, array $description = []) {
		$this->insert($message, $priority, $description);
	}
}