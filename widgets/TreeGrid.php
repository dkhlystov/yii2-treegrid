<?php

namespace dkhlystov\widgets;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\grid\GridView;

class TreeGrid extends GridView {

	/**
	 * @var array the HTML attributes for the grid table element.
	 * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
	 */
	public $tableOptions = ['class' => 'table table-bordered'];
	/**
	 * @var string the layout that determines how different sections of the list view should be organized.
	 * The following tokens will be replaced with the corresponding section contents:
	 *
	 * - `{summary}`: the summary section. See [[renderSummary()]].
	 * - `{errors}`: the filter model error summary. See [[renderErrors()]].
	 * - `{items}`: the list items. See [[renderItems()]].
	 * - `{sorter}`: the sorter. See [[renderSorter()]].
	 * - `{pager}`: the pager. See [[renderPager()]].
	 */
	public $layout = "{items}";

	public $pluginOptions;

	public $parentIdAttribute = 'parent_id';

	public $leftAttribute = 'lft';

	public $rightAttribute = 'rgt';

	public $depthAttribute = 'depth';

	public $countAttribute = 'count';

	// public $lazyLoad = true;

	private $_depth;
	private $_parentIds = [];

	public function init() {
		parent::init();

		$this->dataProvider->pagination = false;
		$this->dataProvider->sort = false;

		$id = $this->options['id'];
		$options = Json::htmlEncode($this->pluginOptions);

		$view = $this->getView();
		TreeGridAsset::register($view);

		$view->registerJs("jQuery('#$id > table').treegrid($options);");
	}

	/**
	 * Renders a table row with the given data model and key.
	 * @param mixed $model the data model to be rendered
	 * @param mixed $key the key associated with the data model
	 * @param integer $index the zero-based index of the data model among the model array returned by [[dataProvider]].
	 * @return string the rendering result
	 */
	public function renderTableRow($model, $key, $index) {
		$cells = [];
		/* @var $column Column */
		foreach ($this->columns as $column) {
			$cells[] = $column->renderDataCell($model, $key, $index);
		}
		if ($this->rowOptions instanceof Closure) {
			$options = call_user_func($this->rowOptions, $model, $key, $index, $this);
		} else {
			$options = $this->rowOptions;
		}
		$key = is_array($key) ? json_encode($key) : (string) $key;
		$options['data-key'] = $key;

		//treegrid
		//id
		Html::addCssClass($options, 'treegrid-'.$key);
		//parent id
		$parentId = null;
		if ($model->hasAttribute($this->parentIdAttribute)) {
			//@todo check
			$parentId = $model->{$this->parentIdAttribute};
		} elseif ($model->hasAttribute($this->depthAttribute)) {
			$depth = $model->{$this->depthAttribute};
			if (sizeof($this->_parentIds)) {
				$offset = $depth - $this->_depth - 1;
				if ($offset < 0) array_splice($this->_parentIds, $offset);
			}
			$this->_parentIds[] = $key;
			$this->_depth = $depth;
			if (($i = sizeof($this->_parentIds)) > 1) $parentId = $this->_parentIds[$i - 2];
		}
		if ($parentId !== null) Html::addCssClass($options, 'treegrid-parent-'.$parentId);
		//child count
		$count = 0;
		if ($model->hasAttribute($this->countAttribute)) {
			$count = $model->{$this->countAttribute};
		} elseif ($model->hasAttribute($this->leftAttribute) && $model->hasAttribute($this->rightAttribute)) {
			$count = ($model->{$this->rightAttribute} - $model->{$this->leftAttribute} - 1) / 2;
		}
		if ($count !== 0) $options['data-count'] = $count;

		return Html::tag('tr', implode('', $cells), $options);
	}

}
