<?php

use aleksandrzen\PriorityQueue\PriorityQueueAbstract;

/**
 * Class ImplementationQueue
 *
 * Simple implementation for testing base functionality of PriorityQueue.
 *
 * @package UnitTests\PriorityQueue
 */
class ImplementationQueue extends PriorityQueueAbstract {

	protected $_collectionName  = 'test_queue';

	protected $_defaultPriority = 7;

}