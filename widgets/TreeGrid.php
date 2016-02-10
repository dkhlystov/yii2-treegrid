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
	protected function getParentId($model, $key, $index) {
		if ($this->_isRoot) return null;
		return $model[$this->parentIdAttribute];
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getChildCount($model, $key, $index) {
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
	protected function addNodeCondition($id) {
		$class = $this->dataProvider->query->modelClass;
		$pk = $class::primaryKey();
		if (!isset($pk[0])) return;

		$this->_isRoot = false;

		if ($id === null && $this->showRoot) {
			$this->_isRoot = true;
			$this->dataProvider->query->andWhere([$this->parentIdAttribute => null]);
		} else {
			if ($id === null) {
				$this->_isRoot = true;
				$conditions = [$this->parentIdAttribute => null];
			} else {
				$conditions = [$pk[0] => $id];
			}
			$row = $class::find()->select([$pk[0]])->where($conditions)->asArray()->one();
			if ($row !== null) $this->dataProvider->query->andWhere([$this->parentIdAttribute => $row[$pk[0]]]);
		}
	}

}
