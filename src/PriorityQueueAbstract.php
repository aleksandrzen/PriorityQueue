<?php
namespace aleksandrzen\PriorityQueue;

/**
 * The PriorityQueueAbstract class provides the main functionality of an prioritized queue, uses MongoDB as storage.
 *
 * @package aleksandrzen\PriorityQueueAbstract
 */
abstract class PriorityQueueAbstract implements  \Iterator, \Countable {

	const VALUE_FIELD       = 'value';
	const CREATED_FIELD     = 'created';
	const ITERATED_FIELD    = 'iterated';
	const PRIORITY_FIELD    = 'priority';
	const DESCRIPTION_FIELD = 'description'; // This field can be useful, if you are going to implement find method (by iterated (or not iterated) elements in the queue by some properties associated with them).

	/**
	 * @var int
	 */
	protected $_defaultPriority;

	/**
	 * @var string
	 */
	protected $_collectionName;

	/**
	 * @var \MongoCollection
	 */
	protected $_collection;

	/**
	 * @var string
	 */
	protected $_current;

	/**
	 * Triggered when any problem with insertion occurred.
	 *
	 * @param array|\MongoException|\ErrorException $exception
	 *
	 * @throws \ErrorException
	 */
	protected function insertErrorOccurred($exception) {
/* $exception instanceof
* \MongoException              if the inserted document is empty or if it contains zero-length keys. Attempting to insert an object with protected and private properties will cause a zero-length key error.
* \MongoCursorException        if the "w" option is set and the write fails.
* \MongoCursorTimeoutException if the "w" option is set to a value greater than one and the operation takes longer than MongoCursor::$timeout milliseconds to complete. This does not kill the operation on the server, it is a client-side timeout. The operation in MongoCollection::$wtimeout is milliseconds.
* \ErrorException              if insertion failed.
*/
		throw new \ErrorException($exception);
	}

	/**
	 * @throws \LogicException if $this->_defaultPriority is not an integer.
	 * @throws \LogicException if ITERATED_FIELD is not nonempty string.
	 * @throws \LogicException if $this->_collectionName is not nonempty string.
	 */
	protected final function checkInheritanceIsCorrect() {
		switch (true) {
			case ! is_int($this->_defaultPriority):
				throw new \LogicException('$_defaultPriority property must be an integer');
				break;
			case ! is_string(static::ITERATED_FIELD) || static::ITERATED_FIELD === '':
				throw new \LogicException('ITERATED_FIELD must be nonempty sting');
				break;
			case empty($this->_collectionName) || ! is_string($this->_collectionName):
				throw new \LogicException(static::class . ' must have nonempty (string) $_collectionName property');
				break;
		}
	}

	/**
	 *
	 * @param string $server   The server name.
	 * @param string $database The database name.
	 * @param bool   $journal  If true - all write operations will block until the database has flushed the changes to the journal on disk (more info: http://php.net/manual/mongoclient.construct.php).
	 *
	 * @throws \LogicException if $this->_defaultPriority is not an integer.
	 * @throws \LogicException if ITERATED_FIELD is not nonempty string.
	 * @throws \LogicException if $this->_collectionName is not nonempty string.
	 *
	 * @throws \MongoConnectionException if $server is not suitable for \MongoClient::_construct()
	 * @throws \InvalidArgumentException if $database is not correct for \MongoClient::selectDB()
	 * @throws \Exception                if name of collection is invalid.
	 */
	public function __construct($server, $database, $journal = true) {
		$this->checkInheritanceIsCorrect();

		$mongoDb          = new \MongoClient($server, [
			'journal' => $journal
		]);
		$mongoDb          = $mongoDb->selectDB($database);

		$this->_collection = $mongoDb->selectCollection($this->_collectionName);
	}
	/**
	 * Returns name of mongos' collection.
	 *
	 * @return string
	 */
	public function getNameOfCollection() {
		return $this->_collectionName;
	}

	/**
	 * Returns default priority.
	 *
	 * @return int
	 */
	public function getDefaultPriority() {
		return $this->_defaultPriority;
	}

	/**
	 * Returns true if queue can be iterated.
	 *
	 * @return bool
	 */
	public function valid() {
		$iterationsCompleted  = ! $this->isEmpty() || $this->_current !== null;
		return $iterationsCompleted;
	}

	/**
	 * Inserts an element in the queue.
	 * $values with higher $priority will be returned earlier.
	 *
	 * @param string     $value       The value to insert.
	 * @param int|null   $priority
	 * @param array|null $description Additional field, which will be written in new document.
	 *
	 * @return bool true if insertion was successful.
	 *
	 * @throws \InvalidArgumentException if $value is not a string.
	 * @throws \InvalidArgumentException if $priority is not an integer.
	 * @throws \ErrorException           if insertion failed.
	 */
	public function insert($value, $priority = null, array $description = []) {
		if (! is_string($value)) {
			throw new \InvalidArgumentException('$value must be a string');
		}

		if ($priority === null) {
			$priority = $this->_defaultPriority;
		}

		if (! is_int($priority)) {
			throw new \InvalidArgumentException('$priority must be an integer');
		}

		$resultOfInsertion = [];
		$errorOccurred     = false;

		$newElement = [
			static::VALUE_FIELD    => new \MongoBinData($value, \MongoBinData::CUSTOM),
			static::CREATED_FIELD  => new \MongoDate(),
			static::ITERATED_FIELD => false,
			static::PRIORITY_FIELD => $priority
		];

		if (! empty($description)) {
			$newElement[static::DESCRIPTION_FIELD] = $description;
		}

		try {
			$resultOfInsertion = $this->_collection->insert($newElement);

			if ($resultOfInsertion['ok'] !== 1.0) {
				$errorOccurred = true;
			}

		} catch (\MongoException $exception) {
			$errorOccurred = true;
			$resultOfInsertion = $exception;
		} finally {
			if ($errorOccurred) {
				$this->insertErrorOccurred($resultOfInsertion);
			}

			return ! $errorOccurred;
		}
	}

	/**
	 * Extracts a node from top of the queue.
	 *
	 * @return null|string
	 */
	public function extract() {
		$fields   = null;
		$query    = [
			static::ITERATED_FIELD => false
		];
		$update   = [
			'$set' => [
				static::ITERATED_FIELD => true
			]
		];
		$options  = [
			'sort' => [
				static::PRIORITY_FIELD => -1,
				static::CREATED_FIELD  => 1
			]
		];

		$elementFromCollection = $this->_collection->findAndModify($query, $update, $fields, $options);

		$this->_current = isset($elementFromCollection[static::VALUE_FIELD]) ? $elementFromCollection[static::VALUE_FIELD]->bin : null;

		return $this->_current;
	}

	/**
	 * Move to the next node and return it.
	 *
	 * Keep in mind, that after __construct $this->_current is null, so,
	 * next() will return first element from the queue,
	 * if the queue was not iterated (by foreach(){}, current() or extract())
	 * and this method was not called before.
	 *
	 * @return null|string
	 */
	public function next() {
		$this->_current = $this->extract();

		return $this->_current;
	}

	/**
	 * Return current node pointed by the iterator.
	 *
	 * @return null|string
	 */
	public function current() {
		if ($this->_current === null) {
			$this->_current = $this->extract();
		}

		return $this->_current;
	}

	/**
	 * Counts the number of elements in the queue.
	 *
	 * @return int The number of elements in the queue.
	 */
	public function count() {
		return $this->_collection->count([static::ITERATED_FIELD => false]);
	}

	/**
	 * Checks whether the queue is empty.
	 *
	 * @return bool whether the queue is empty.
	 */
	public function isEmpty() {
		return $this->count() === 0;
	}

	/**
	 * There are no any keys in the queue.
	 */
	public final function key() {}

	/**
	 * Prevents the rewind operation on the inner iterator.
	 */
	public final function rewind() {}

}