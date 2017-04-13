<?php

namespace dkhlystov\widgets;

use yii\db\BaseActiveRecord;

/**
 * The TreeGrid widget is used to display parent relation tree data in a grid.
 *
 * For more information see documentation of yii\grid\GridView.
 *
 * @author Dmitry Khlystov <dkhlystov@gmail.com>
 */
class TreeGrid extends BaseTreeGrid {

	/**
	 * @var string name of parent relation attribute.
	 */
	public $parentIdAttribute = 'parent_id';
	/**
	 * @var mixed value of parent relation attribute for root node
	 */
	public $rootParentId = 0;
	/**
	 * @var string name of child count attribute.
	 */
	public $countAttribute = 'count';
	/**
	 * @var mixed current root nodes parent id
	 */
	private $_rootParentId;
	/**
	 * @var mixed parent id for lazy load
	 */
	private $_parentId;

	/**
	 * {@inheritdoc}
	 */
	protected function getParentId($model)
	{
		if ($model[$this->parentIdAttribute] == $this->_rootParentId) return null;
		return $model[$this->parentIdAttribute];
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getChildCount($model)
	{
		$attr = $this->countAttribute;
		if ($model instanceof BaseActiveRecord) {
			if ($model->hasAttribute($attr)) return $model->getAttribute($attr);

			$idAttr = $this->idAttribute;
			return $model::find()->where([$this->parentIdAttribute => $model->$idAttr])->count();
		} else {
			if (isset($model[$attr])) return $model[$attr];
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function addLazyCondition($id)
	{
		$this->_parentId = $id;

		$this->_rootParentId = $this->rootParentId;
		$class = $this->dataProvider->query->modelClass;

		if ($id === null) {
			if (!$this->showRoots) {
				$row = $class::find()
					->select([$this->idAttribute])
					->where([$this->parentIdAttribute => $this->rootParentId])
					->asArray()
					->one();
				if ($row !== null) $this->_rootParentId = $row[$this->idAttribute];
			}
			$this->dataProvider->query->andWhere([$this->parentIdAttribute => $this->_rootParentId]);
		} else {
			$this->dataProvider->query->andWhere([$this->parentIdAttribute => $id]);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	protected function loadInitial()
	{
		$items = [];
		$parent_id = $this->initialNode[$this->parentIdAttribute];
		while ($parent_id !== $this->rootParentId) {
			$query = clone $this->dataProvider->query;
			$parent = $query->select([
				$this->idAttribute,
				$this->parentIdAttribute,
			])->andWhere([$this->idAttribute => $parent_id])->asArray()->one();
			if ($parent === null) break;
			if (!$this->showRoots && $parent[$this->parentIdAttribute] == $this->rootParentId) break;

			$query = clone $this->dataProvider->query;
			$items = array_merge($items, $query->andWhere([$this->parentIdAttribute => $parent_id])->all());

			$parent_id = $parent[$this->parentIdAttribute];
		}

		return $items;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function initialExpand()
	{
		$expand = [];
		$models = $this->dataProvider->getModels();
		$parent_id = $this->initialNode[$this->parentIdAttribute];
		while ($parent_id !== $this->rootParentId) {
			$parent = null;
			foreach ($models as $model) {
				if ($model[$this->idAttribute] == $parent_id) {
					$parent = $model;
					break;
				}
			}
			if ($parent === null) break;

			$expand[] = $parent_id;

			$parent_id = $parent[$this->parentIdAttribute];
		}

		return $expand;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function sortModels()
	{
		if ($this->_parentId === null) $this->_parentId = $this->_rootParentId;
		//original models
		$_models = array_reverse($this->dataProvider->getModels());
		//roots
		$stack = [];
		foreach ($_models as $key => $model) {
			if ($model[$this->parentIdAttribute] == $this->_parentId) {
				array_push($stack, $model);
				unset($_models[$key]);
			}
		}
		//parent - child
		$models = [];
		while (sizeof($stack)) {
			$model = array_pop($stack);
			foreach ($_models as $key => $child) {
				if ($child[$this->parentIdAttribute] == $model[$this->idAttribute]) {
					array_push($stack, $child);
					unset($_models[$key]);
				}
			}
			$models[] = $model;
		}

		$this->dataProvider->setModels($models);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function removeRoots()
	{
		$models = $this->dataProvider->getModels();
		$ids = [];
		foreach ($models as $key => $model) {
			if ($model[$this->parentIdAttribute] === $this->rootParentId) {
				$ids[] = $model[$this->idAttribute];
				unset($models[$key]);
			}
		}
		foreach ($models as $key => $model) {
			if (array_search($model[$this->parentIdAttribute], $ids) !== false) {
				$model[$this->parentIdAttribute] = $this->rootParentId;
				$models[$key] = $model;
			}
		}
		$this->dataProvider->setModels($models);
	}

}
