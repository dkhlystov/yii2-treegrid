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
	 * @var string name of child count attribute.
	 */
	public $countAttribute = 'count';
	/**
	 * @var boolean current drows root node
	 */
	private $_isRoot;

	/**
	 * {@inheritdoc}
	 */
	protected function initDataProvider()
	{
		parent::initDataProvider();

		$this->sortModels();
	}

	/**
	 * Sorting models in data provider. Placing child models after parent.
	 * @return void
	 */
	protected function sortModels()
	{
		//original models
		$_models = array_reverse($this->dataProvider->getModels());
		//roots
		$stack = [];
		foreach ($_models as $key => $model) {
			if ($model[$this->parentIdAttribute] === null) {
				array_push($stack, $model);
				unset($_models[$key]);
			}
		}
		//parent - child
		$models = [];
		while (sizeof($stack)) {
			$model = array_pop($stack);
			foreach ($_models as $key => $child) {
				if ($child[$this->parentIdAttribute] === $model[$this->idAttribute]) {
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
	protected function getParentId($model)
	{
		if ($this->_isRoot) return null;
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
		$this->_isRoot = false;

		if ($id === null && $this->showRoots) {
			$this->_isRoot = true;
			$this->dataProvider->query->andWhere([$this->parentIdAttribute => null]);
		} else {
			if ($id === null) {
				$this->_isRoot = true;
				$conditions = [$this->parentIdAttribute => null];
			} else {
				$conditions = [$this->idAttribute => $id];
			}
			$class = $this->dataProvider->query->modelClass;
			$row = $class::find()->select([$this->idAttribute])->where($conditions)->asArray()->one();
			if ($row !== null) $this->dataProvider->query->andWhere([$this->parentIdAttribute => $row[$this->idAttribute]]);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	protected function removeRoots()
	{
		$models = $this->dataProvider->getModels();
		$ids = [];
		foreach ($models as $key => $model) {
			if ($model[$this->parentIdAttribute] === null) {
				$ids[] = $model[$this->idAttribute];
				unset($models[$key]);
			}
		}
		foreach ($models as $key => $model) {
			if (array_search($model[$this->parentIdAttribute], $ids) !== false) {
				$model[$this->parentIdAttribute] = null;
				$models[$key] = $model;
			}
		}
		$this->dataProvider->setModels($models);
	}

}
