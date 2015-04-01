<?php

include_once 'ImplementationQueue.php';

/**
 *
 * @coversDefaultClass \aleksandrzen\PriorityQueue\PriorityQueue
 */
class PriorityQueueTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var \MongoCollection
	 */
	protected $_collection;

	/**
	 * @var ImplementationQueue
	 */
	protected $_priorityQueueAbstract;


	/**
	 * @var string
	 */
	protected $_collectionName;

	public function tearDown() {
		if ($this->_collection !== null) {
			$this->_collection->drop();
		}
	}

	public function setUp() {
		parent::setUp();

		$this->_priorityQueueAbstract = new ImplementationQueue('The server name', 'The database name');
		$this->_collectionName        = $this->_priorityQueueAbstract->getNameOfCollection();

		$db = new \MongoDB(new MongoClient('server'), 'db name');
		$this->_collection = $db->createCollection($this->_collectionName);
	}

	/**
	 * @return array with structure: [[(string) value, (int | null) priority], [...], ...]
	 */
	public function correctDataForInsertion() {
		return [
			['value', null],
			['value', null, []],
			['value', null, ['description' => 'property']],
			[
				'value',
				null,
				[
					'description0' => 'property0',
					'description1' => 'property1',
					'description2' => 2,
					'description3' => [],
					'description4' => true,

				]
			],
			['another value', 0],
			['string', 1],
			['', 2],
			['禅', 3],
			['Кириллица', PHP_INT_MAX]
		];
	}

	/**
	 * @return array
	 */
	public function incorrectValueForInsertion() {
		return [
			[0],
			[1],
			[1.1],
			[null],
			[true],
			[false],
			[function() {}],
			[new StdClass()],
			[[]],
			[[0, 1, 2]],
			[['associative' => 'array']],
			[['array', 'with', 'strings']]
		];
	}

	/**
	 * Description field must be an array.
	 *
	 * @return array
	 */
	public function incorrectDescriptionForInsertion() {
		return [
			[''],
			[1],
			[1.1],
			[null],
			[true],
			[function() {}],
			[new StdClass()]
		];
	}

	/**
	 * @return array
	 */
	public function incorrectPriorityForInsertion() {
		return [
			[''],
			['0'],
			['1'],
			[true],
			[false],
			[function() {}],
			[new StdClass()],
			[[]],
			[[0, 1, 2]],
			[['associative' => 'array']],
			[['array', 'with', 'strings']]
		];
	}

	/**
	 * @covers ::isEmpty
	 */
	public function testCollectionIsEmpty() {
		$this->assertTrue($this->_priorityQueueAbstract->isEmpty(), 'collection must be empty in the beginning of testing');
	}

	/**
	 * @covers ::isEmpty
	 */
	public function testCollectionIsNotEmptyAfterInsertion() {
		$firstValue = '1';

		$this->_priorityQueueAbstract->insert($firstValue);

		$this->assertFalse($this->_priorityQueueAbstract->isEmpty(), 'collection must not be empty after elements\' insertion');
	}

	/**
	 * @covers ::isEmpty
	 */
	public function testCollectionIsNotEmptyIfQueueHasElement() {
		$firstValue  = '1';
		$secondValue = '2';

		$this->_priorityQueueAbstract->insert($firstValue);
		$this->_priorityQueueAbstract->insert($secondValue);

		$this->_priorityQueueAbstract->extract();

		$this->assertFalse($this->_priorityQueueAbstract->isEmpty(), 'collection must not be empty if queue has element');
	}

	/**
	 * @covers ::isEmpty
	 */
	public function testQueueMustBeEmptyAfterExtractionAllElements() {
		$firstValue  = '1';
		$secondValue = '2';
		$thirdValue  = '3';

		$this->_priorityQueueAbstract->insert($firstValue);
		$this->_priorityQueueAbstract->insert($secondValue);
		$this->_priorityQueueAbstract->insert($thirdValue);

		$this->_priorityQueueAbstract->extract();
		$this->_priorityQueueAbstract->extract();
		$this->_priorityQueueAbstract->extract();

		$this->assertTrue($this->_priorityQueueAbstract->isEmpty(), 'collection must be empty after all elements were extracted');
	}

	/**
	 * @covers ::count
	 */
	public function testCountIsCorrectThanQueueIsEmpty() {
		$this->assertSame(0, $this->_priorityQueueAbstract->count());
	}

	/**
	 * @covers ::count
	 */
	public function testCountIsCorrectThanQueueIsNotEmpty() {
		$firstValue  = '1';
		$secondValue = '2';
		$thirdValue  = '3';

		$this->_priorityQueueAbstract->insert($firstValue);
		$this->_priorityQueueAbstract->insert($secondValue);
		$this->_priorityQueueAbstract->insert($thirdValue);

		$this->assertSame(3, $this->_priorityQueueAbstract->count());
	}

	/**
	 * @covers ::valid
	 */
	public function testEmptyQueueIsInvalid() {
		$this->assertFalse($this->_priorityQueueAbstract->valid());
	}

	/**
	 * @covers ::valid
	 */
	public function testNotEmptyQueueIsValid() {
		$this->_priorityQueueAbstract->insert('first');

		$this->assertTrue($this->_priorityQueueAbstract->valid());
	}

	/**
	 * @covers ::valid
	 */
	public function testIteratedQueueIsInvalid() {
		$defaultPriority           = $this->_priorityQueueAbstract->getDefaultPriority();
		$higherPriority            = $defaultPriority + 1;
		$highestPriority           = $higherPriority + 1;
		$higherThanHighestPriority = $highestPriority + 1;

		$elementsIn                = [
			'first'  => $defaultPriority,
			'second' => $higherPriority,
			'third'  => $highestPriority,
			'fourth' => $higherThanHighestPriority
		];

		foreach ($elementsIn as $value => $priority) {
			$this->_priorityQueueAbstract->insert($value, $priority);
		}

		foreach ($this->_priorityQueueAbstract as $element) {}

		$this->assertFalse($this->_priorityQueueAbstract->valid());

	}

	/**
	 * @covers ::current
	 */
	public function testCurrentIsNullThanQueueIsEmpty() {
		$this->assertNull($this->_priorityQueueAbstract->current());
	}

	/**
	 * @covers ::current
	 */
	public function testCurrentIsNullThanQueueWasIterated() {
		$element = 'element';

		$this->_priorityQueueAbstract->insert($element);
		$this->_priorityQueueAbstract->insert($element);
		$this->_priorityQueueAbstract->insert($element);

		foreach ($this->_priorityQueueAbstract as $element) {}

		$this->assertNull($this->_priorityQueueAbstract->current());
	}

	/**
	 * @covers ::current
	 */
	public function testCurrentReturnsElementWhichWillBeIteratedFirstly() {
		$firstElement  = 'first';
		$secondElement = 'second';

		$this->_priorityQueueAbstract->insert($firstElement);
		$this->_priorityQueueAbstract->insert($secondElement);

		$this->assertSame($firstElement, $this->_priorityQueueAbstract->current());
	}

	/**
	 * @covers ::next
	 */
	public function testNextReturnsNullThanQueueWasIterated() {
		$element = 'element';

		$this->_priorityQueueAbstract->insert($element);
		$this->_priorityQueueAbstract->insert($element);
		$this->_priorityQueueAbstract->insert($element);

		foreach ($this->_priorityQueueAbstract as $element) {}

		$this->assertNull($this->_priorityQueueAbstract->next());
	}

	/**
	 * @covers ::next
	 */
	public function testNextReturnsNullThanQueueIsEmpty() {
		$this->assertNull($this->_priorityQueueAbstract->next());
	}

	/**
	 * After PriorityQueue::__construct(...) $this->_current is null, so,
	 * next() will return first element from the queue,
	 * if the queue was not iterated (by foreach(){}, current() or extract())
	 * and this method was not called before.
	 *
	 * @covers ::next
	 */
	public function testNextReturnsFirstElementOfTheNotIteratedYetQueue() {
		$firstElement  = 'first';
		$secondElement = 'second';

		$this->_priorityQueueAbstract->insert($firstElement);
		$this->_priorityQueueAbstract->insert($secondElement);

		$this->assertSame($firstElement, $this->_priorityQueueAbstract->next());
	}

	/**
	 * @covers ::next
	 */
	public function testNextMovesToTheNextNodeCorrectWhenQueueIsEmpty() {
		$next    = $this->_priorityQueueAbstract->next();
		$current = $this->_priorityQueueAbstract->current();

		$this->assertSame($current, $next);
	}

	/**
	 * @covers ::next
	 */
	public function testNextMovesToTheNextNodeCorrectWhenQueueIsNotEmpty() {
		$firstElement  = 'first';
		$secondElement = 'second';
		$thirdElement  = 'third';

		$this->_priorityQueueAbstract->insert($firstElement);
		$this->_priorityQueueAbstract->insert($secondElement);
		$this->_priorityQueueAbstract->insert($thirdElement);

		$next0    = $this->_priorityQueueAbstract->next();
		$current0 = $this->_priorityQueueAbstract->current();

		$next1    = $this->_priorityQueueAbstract->next();
		$current1 = $this->_priorityQueueAbstract->current();

		$next2    = $this->_priorityQueueAbstract->next();
		$current2 = $this->_priorityQueueAbstract->current();

		$next3    = $this->_priorityQueueAbstract->next();
		$current3 = $this->_priorityQueueAbstract->current();

		$this->assertSame($current0, $next0);
		$this->assertSame($current1, $next1);
		$this->assertSame($current2, $next2);
		$this->assertSame($current3, $next3);
	}

	/**
	 * PriorityQueue must iterate all elements with correct order.
	 */
	public function testPriorityLogicByIteration() {
		$defaultPriority           = $this->_priorityQueueAbstract->getDefaultPriority();
		$higherPriority            = $defaultPriority + 1;
		$highestPriority           = $higherPriority + 1;
		$higherThanHighestPriority = $highestPriority + 1;
		$elementsOut               = [];
		$elementsIn                = [
			'first'  => $defaultPriority,
			'second' => $higherPriority,
			'third'  => $highestPriority,
			'fourth' => $higherThanHighestPriority
		];

		foreach ($elementsIn as $value => $priority) {
			$this->_priorityQueueAbstract->insert($value, $priority);
		}

		foreach ($this->_priorityQueueAbstract as $element) {
			$elementsOut[] = $element;
		}

		$this->assertTrue($elementsOut === array_reverse(array_keys($elementsIn)), 'elements with higher priority must be extracted earlier');
	}

	/**
	 * Elements with higher priority must be extracted earlier.
	 *
	 * @covers ::extract
	 */
	public function testPriorityLogicByExtractMethod() {
		$defaultPriority           = $this->_priorityQueueAbstract->getDefaultPriority();
		$higherPriority            = $defaultPriority + 1;
		$highestPriority           = $higherPriority + 1;
		$higherThanHighestPriority = $highestPriority + 1;
		$elementsOut               = [];
		$elementsIn                = [
			'first'  => $defaultPriority,
			'second' => $higherPriority,
			'third'  => $highestPriority,
			'fourth' => $higherThanHighestPriority
		];

		foreach ($elementsIn as $value => $priority) {
			$this->_priorityQueueAbstract->insert($value, $priority);
		}

		$elementsOut[] = $this->_priorityQueueAbstract->extract();
		$elementsOut[] = $this->_priorityQueueAbstract->extract();
		$elementsOut[] = $this->_priorityQueueAbstract->extract();
		$elementsOut[] = $this->_priorityQueueAbstract->extract();

		$this->assertTrue($elementsOut === array_reverse(array_keys($elementsIn)), 'elements with higher priority must be extracted earlier');
	}

	/**
	 * @param string   $value
	 * @param int|null $priority
	 *
	 * @dataProvider correctDataForInsertion
	 * @covers ::insert
	 */
	public function testInsertCorrect($value, $priority = null) {
		$this->assertTrue($this->_priorityQueueAbstract->insert($value, $priority));
	}

	/**
	 * @param mixed $value
	 *
	 * @expectedException        InvalidArgumentException
	 * @expectedExceptionMessage $value must be a string
	 *
	 * @dataProvider incorrectValueForInsertion
	 * @covers ::insert
	 */
	public function testInsertIncorrectValue($value) {
		$this->_priorityQueueAbstract->insert($value);
	}

	/**
	 * @param mixed $description
	 *
	 * @expectedException PHPUnit_Framework_Error
	 *
	 * @dataProvider incorrectDescriptionForInsertion
	 * @covers ::insert
	 */
	public function testInsertIncorrectDescription($description) {
		$value    = 'simpleValue';
		$priority = null;

		$this->_priorityQueueAbstract->insert($value, $priority, $description);
	}

	/**
	 * @param mixed $priority
	 *
	 * @expectedException        InvalidArgumentException
	 * @expectedExceptionMessage $priority must be an integer
	 *
	 * @dataProvider incorrectPriorityForInsertion
	 * @covers ::insert
	 */
	public function testInsertIncorrectPriority($priority) {
		$value = 'correct value';

		$this->_priorityQueueAbstract->insert($value, $priority);
	}

}
