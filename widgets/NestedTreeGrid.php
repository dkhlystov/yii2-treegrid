<?php

namespace dkhlystov\widgets;

use yii\data\ActiveDataProvider;

/**
 * The NestedTreeGrid widget is used to display nested set tree data in a grid.
 *
 * For more information see documentation of yii\grid\GridView.
 *
 * @author Dmitry Khlystov <dkhlystov@gmail.com>
 */
class NestedTreeGrid extends BaseTreeGrid {

	/**
	 * @var string name of left attribute.
	 */
	public $leftAttribute = 'lft';
	/**
	 * @var string name of right attribute.
	 */
	public $rightAttribute = 'rgt';
	/**
	 * @var string name of depth attribute.
	 */
	public $depthAttribute = 'depth';
	/**
	 * @var integer current depth to determite parent of table row
	 */
	private $_depth;
	/**
	 * @var array ids of nodes to determite parent of table row
	 */
	private $_parentIds = [];

	/**
	 * {@inheritdoc}
	 */
	public function init()
	{
		if ($this->dataProvider instanceof ActiveDataProvider) {
			$this->dataProvider->query->orderBy([$this->leftAttribute => SORT_ASC]);
		}
		parent::init();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getParentId($model)
	{
		$depth = $model[$this->depthAttribute];
		if (sizeof($this->_parentIds)) {
			$offset = $depth - $this->_depth - 1;
			if ($offset < 0) array_splice($this->_parentIds, $offset);
		}
		$this->_parentIds[] = $model[$this->idAttribute];
		$this->_depth = $depth;
		if (($i = sizeof($this->_parentIds)) > 1) return $this->_parentIds[$i - 2];

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getChildCount($model)
	{
		return ($model[$this->rightAttribute] - $model[$this->leftAttribute] - 1) / 2;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function addLazyCondition($id)
	{
		if ($id === null && $this->showRoots) {
			$this->dataProvider->query->andWhere([$this->leftAttribute => 1]);
		} else {
			if ($id === null) {
				$conditions = [$this->leftAttribute => 1];
			} else {
				$conditions = [$this->idAttribute => $id];
			}
			$class = $this->dataProvider->query->modelClass;
			$row = $class::find()->select([
				$this->leftAttribute,
				$this->rightAttribute,
				$this->depthAttribute,
			])->where($conditions)->asArray()->one();
			if ($row !== null) $this->dataProvider->query->andWhere(['and',
				['>', $this->leftAttribute, $row[$this->leftAttribute]],
				['<', $this->rightAttribute, $row[$this->rightAttribute]],
				['=', $this->depthAttribute, $row[$this->depthAttribute] + 1],
			]);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	protected function loadInitial()
	{
		$query = clone $this->dataProvider->query;
		if (!$this->showRoots) $query->andWhere(['<>', $this->leftAttribute, 1]);
		$parents = $query->select([
			$this->leftAttribute,
			$this->rightAttribute,
			$this->depthAttribute,
		])->andWhere(['and',
			['<', $this->leftAttribute, $this->initialNode[$this->leftAttribute]],
			['>', $this->rightAttribute, $this->initialNode[$this->rightAttribute]],
		])->asArray()->all();

		$items = [];
		foreach ($parents as $row) {
			$query = clone $this->dataProvider->query;
			$items = array_merge($items, $query->andWhere(['and',
				['>', $this->leftAttribute, $row[$this->leftAttribute]],
				['<', $this->rightAttribute, $row[$this->rightAttribute]],
				['=', $this->depthAttribute, $row[$this->depthAttribute] + 1],
			])->all());
		}

		return $items;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function initialExpand()
	{
		$lft = $this->initialNode[$this->leftAttribute];
		$rgt = $this->initialNode[$this->rightAttribute];
		$expanded = [];
		foreach ($this->dataProvider->getModels() as $model) {
			if ($model[$this->leftAttribute] < $lft && $model[$this->rightAttribute] > $rgt) {
				$expanded[] = $model[$this->idAttribute];
			}
		}

		return $expanded;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function sortModels()
	{
		$models = $this->dataProvider->getModels();
		usort($models, function($a, $b) {
			return $a[$this->leftAttribute] - $b[$this->leftAttribute];
		});
		$this->dataProvider->setModels($models);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function removeRoots()
	{
		$models = $this->dataProvider->getModels();
		foreach ($models as $key => $model) {
			if ($model[$this->depthAttribute] === 0) {
				unset($models[$key]);
			}
		}
		$this->dataProvider->setModels($models);
		$this->dataProvider->setKeys(null);
		$this->dataProvider->prepare();
	}

}
