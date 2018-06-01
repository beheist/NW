<?php
namespace Beheist\NW\Fusion\Eel\FlowQueryOperations;

use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\ContentRepository\Domain\Model\Node;

/**
 * EEL sortByDate() operation to sort Nodes by the visible date (_hiddenBeforeDateTime if set, _creationDate otherwise)
 */
class SortByDateOperation extends AbstractOperation {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	static protected $shortName = 'sortByDate';
	/**
	 * {@inheritdoc}
	 *
	 * @var integer
	 */
	static protected $priority = 100;

	/**
	 * {@inheritdoc}
	 *
	 * We can only handle TYPO3CR Nodes.
	 *
	 * @param mixed $context
	 * @return boolean
	 */
	public function canEvaluate($context) {
		return (isset($context[0]) && ($context[0] instanceof NodeInterface));
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param FlowQuery $flowQuery the FlowQuery object
	 * @param array $arguments the arguments for this operation
	 * @return mixed
	 */
	public function evaluate(FlowQuery $flowQuery, array $arguments) {
		$nodes = $flowQuery->getContext();
		$sortOrder = isset($arguments[0]) && !empty($arguments[0]) ? $arguments[0] : 'DESC';
		$sortedNodes = array();
		$sortSequence = array();
		$nodesByIdentifier = array();
		/** @var Node $node */
		foreach ($nodes as $node) {
			$propertyValue = $node->getHiddenBeforeDateTime() ?: $node->getCreationDateTime();
			if ($propertyValue instanceof \DateTime) {
				$propertyValue = $propertyValue->getTimestamp();
			}
			$sortSequence[$node->getIdentifier()] = $propertyValue;
			$nodesByIdentifier[$node->getIdentifier()] = $node;
		}
		if ($sortOrder === 'DESC') {
			arsort($sortSequence);
		} else if ($sortOrder === 'ASC') {
			asort($sortSequence);
		} else {
			throw new \Neos\Eel\FlowQuery\FlowQueryException('Please provide a valid sort direction (DESC or ASC)', 1332492264);
		}
		foreach ($sortSequence as $nodeIdentifier => $value) {
			$sortedNodes[] = $nodesByIdentifier[$nodeIdentifier];
		}
		$flowQuery->setContext($sortedNodes);
	}
}