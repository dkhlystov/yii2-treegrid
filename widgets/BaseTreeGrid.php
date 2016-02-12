<?php

namespace dkhlystov\widgets;

use Yii;
use Closure;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\grid\DataColumn;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\i18n\Formatter;
use yii\web\JsExpression;

/**
 * Base class for widgets displaying data as tree with columns
 * 
 * Based on yii\grid\GridView, but not extends it because no needs in features like sorting, paging
 * and filtering data.
 * 
 * @author Dmitry Khlystov <dkhlystov@gmail.com>
 */
abstract class BaseTreeGrid extends Widget {

	/**
	 * @var array the HTML attributes for the container tag of the tree grid.
	 * The "tag" element specifies the tag name of the container element and defaults to "div".
	 * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
	 */
	public $options = ['class' => 'treegrid'];
	/**
	 * @see yii\widgets\BaseListView::$dataProvider
	 */
	public $dataProvider;
	/**
	 * @see yii\widgets\BaseListView::$emptyText
	 */
	public $emptyText;
	/**
	 * @see yii\widgets\BaseListView::$emptyTextOptions
	 */
	public $emptyTextOptions = ['class' => 'empty'];
	/**
	 * @see yii\grid\GridView::$dataColumnClass
	 */
	public $dataColumnClass;
	/**
	 * @see yii\grid\GridView::$tableOptions
	 */
	public $tableOptions = ['class' => 'table table-bordered'];
	/**
	 * @see yii\grid\GridView::$headerRowOptions
	 */
	public $headerRowOptions = [];
	/**
	 * @see yii\grid\GridView::$footerRowOptions
	 */
	public $footerRowOptions = [];
	/**
	 * @see yii\grid\GridView::$rowOptions
	 */
	public $rowOptions = [];
	/**
	 * @see yii\grid\GridView::$beforeRow
	 */
	public $beforeRow;
	/**
	 * @see yii\grid\GridView::$afterRow
	 */
	public $afterRow;
	/**
	 * @see yii\grid\GridView::$showHeader
	 */
	public $showHeader = true;
	/**
	 * @see yii\grid\GridView::$showFooter
	 */
	public $showFooter = false;
	/**
	 * @see yii\grid\GridView::$formatter
	 */
	public $formatter;
	/**
	 * @see yii\grid\GridView::$columns
	 */
	public $columns = [];
	/**
	 * @see yii\grid\GridView::$emptyCell
	 */
	public $emptyCell = '&nbsp;';
	/**
	 * @var array with this options initializes TreeGrid plugin for jQuery.
	 */
	public $pluginOptions = [];
	/**
	 * @var boolean if true, widget tries to display tree with dynamic data loading. When widget renderings, it shows first level of nodes. Next level of nodes loads when needed (node has been expanded).
	 */
	public $lazyLoad = true;
	/**
	 * @var boolean if true, root nodes will not showed when lazyLoad is enabled.
	 */
	public $showRoots = false;
	/**
	 * @var string name of id attribute.
	 */
	public $idAttribute;
	/**
	 * @var array|string|null the URL for the move action.
	 */
	public $moveAction;
	/**
	 * @var array|BaseActiveRecord|null node that need to be visible
	 */
	public $initialNode;
	/**
	 * @var string ajax token.
	 */
	private $_token;
	/**
	 * @var array ids of expanded nodes
	 */
	private $_expanded = [];

	/**
	 * Initializes the grid view.
	 * This method will initialize required property values and instantiate [[columns]] objects.
	 */
	public function init()
	{
		parent::init();

		if ($this->dataProvider === null) {
			throw new InvalidConfigException('The "dataProvider" property must be set.');
		}
		if ($this->emptyText === null) {
			$this->emptyText = Yii::t('yii', 'No results found.');
		}
		if (!isset($this->options['id'])) {
			$this->options['id'] = $this->getId();
		}
		if ($this->formatter === null) {
			$this->formatter = Yii::$app->getFormatter();
		} elseif (is_array($this->formatter)) {
			$this->formatter = Yii::createObject($this->formatter);
		}
		if (!$this->formatter instanceof Formatter) {
			throw new InvalidConfigException('The "formatter" property must be either a Format object or a configuration array.');
		}
		if ($this->idAttribute === null) {
			if ($this->dataProvider instanceof ActiveDataProvider) {
				$class = $this->dataProvider->query->modelClass;
				$pk = $class::primaryKey();
				if (isset($pk[0]) && is_string($pk[0])) $this->idAttribute = $pk[0];
			} elseif ($this->dataProvider instanceof ArrayDataProvider) {
				if (is_string($this->dataProvider->key)) $this->idAttribute = $this->dataProvider->key;
				else $this->idAttribute = 'id';
			}

			if ($this->idAttribute === null) throw new InvalidConfigException('The "idAttribute" property could not be determined automaticaly.');
		}

		$this->initDataProvider();

		$this->initColumns();
	}

	/**
	 * Runs the widget.
	 */
	public function run()
	{
		if ($this->_token !== null && Yii::$app->getRequest()->isAjax) {
			$this->renderAjax();
		}

		$id = $this->options['id'];
		$options = Json::htmlEncode($this->getClientOptions());
		$view = $this->getView();
		TreeGridAsset::register($view);
		$view->registerJs("jQuery('#$id > table').treegrid($options);");

		if ($this->dataProvider->getCount() > 0) {
			$content = $this->renderItems();
		} else {
			$content = $this->renderEmpty();
		}
		$options = $this->options;
		$tag = ArrayHelper::remove($options, 'tag', 'div');
		echo Html::tag($tag, $content, $options);
	}

	/**
	 * Ajax renderer. If ajax request, it needs only tbody data.
	 * @return void
	 */
	protected function renderAjax() {
		$response = Yii::$app->getResponse();
		$response->clearOutputBuffers();
		echo Json::encode($this->makeTableRows());
		Yii::$app->end();
	}

	/**
	 * Returns the options for the treegrid JS plugin.
	 * @return array the options
	 */
	protected function getClientOptions()
	{
		$options = $this->pluginOptions;

		if (!isset($options['source'])) {
			$url = Url::toRoute('');
			$options['source'] = new JsExpression('function(id, response) {
				var $tr = this, token = Math.random().toString(36).substr(2);
				$.get("'.$url.'", {treegrid_id: id, treegrid_token: token}, function(data) {
					response(data);
				}, "json");
			}');
		}

		if (!isset($options['enableMove']) && $this->moveAction !== null) {
			$options['enableMove'] = true;
			if (!isset($options['onMove'])) {
				$url = Url::toRoute($this->moveAction);
				$options['onMove'] = new JsExpression('function(item, target, position) {
					var $el = this;
					$el.treegrid("option", "enableMove", false);
					$.get("'.$url.'", {
						id: item.treegrid("getId"),
						target: target.treegrid("getId"),
						position: position
					}).done(function() {
						$el.treegrid("option", "enableMove", true);
					}).fail(function(xhr) {
						alert(xhr.responseText);
					});
				}');
			}
		}

		return $options;
	}

	/**
	 * @see yii\widgets\BaseListView::renderItems()
	 */
	public function renderItems()
	{
		$columnGroup = $this->renderColumnGroup();
		$tableHeader = $this->showHeader ? $this->renderTableHeader() : false;
		$tableBody = $this->renderTableBody();
		$tableFooter = $this->showFooter ? $this->renderTableFooter() : false;
		$content = array_filter([
			$columnGroup,
			$tableHeader,
			$tableFooter,
			$tableBody,
		]);
		return Html::tag('table', implode("\n", $content), $this->tableOptions);
	}

	/**
	 * @see yii\widgets\BaseListView::renderEmpty()
	 */
	public function renderEmpty()
	{
		$options = $this->emptyTextOptions;
		$tag = ArrayHelper::remove($options, 'tag', 'div');
		return Html::tag($tag, $this->emptyText, $options);
	}

	/**
	 * @see yii\grid\GridView::renderColumnGroup()
	 */
	public function renderColumnGroup()
	{
		$requireColumnGroup = false;
		foreach ($this->columns as $column) {
			/* @var $column Column */
			if (!empty($column->options)) {
				$requireColumnGroup = true;
				break;
			}
		}
		if ($requireColumnGroup) {
			$cols = [];
			foreach ($this->columns as $column) {
				$cols[] = Html::tag('col', '', $column->options);
			}
			return Html::tag('colgroup', implode("\n", $cols));
		} else {
			return false;
		}
	}

	/**
	 * @see yii\grid\GridView::renderTableHeader()
	 */
	public function renderTableHeader()
	{
		$cells = [];
		foreach ($this->columns as $column) {
			/* @var $column Column */
			$cells[] = $column->renderHeaderCell();
		}
		$content = Html::tag('tr', implode('', $cells), $this->headerRowOptions);
		return "<thead>\n" . $content . "\n</thead>";
	}

	/**
	 * @see yii\grid\GridView::renderTableFooter()
	 */
	public function renderTableFooter()
	{
		$cells = [];
		foreach ($this->columns as $column) {
			/* @var $column Column */
			$cells[] = $column->renderFooterCell();
		}
		$content = Html::tag('tr', implode('', $cells), $this->footerRowOptions);
		return "<tfoot>\n" . $content . "\n</tfoot>";
	}

	/**
	 * @see yii\grid\GridView::renderTableBody()
	 */
	public function renderTableBody()
	{
		$rows = $this->makeTableRows();
		return "<tbody>\n" . implode("\n", $rows) . "\n</tbody>";
	}

	/**
	 * Making rows to array
	 * @return array
	 */
	protected function makeTableRows()
	{
		$models = array_values($this->dataProvider->getModels());
		$keys = $this->dataProvider->getKeys();
		$rows = [];
		foreach ($models as $index => $model) {
			$key = $keys[$index];
			if ($this->beforeRow !== null) {
				$row = call_user_func($this->beforeRow, $model, $key, $index, $this);
				if (!empty($row)) {
					$rows[] = $row;
				}
			}
			$rows[] = $this->renderTableRow($model, $key, $index);
			if ($this->afterRow !== null) {
				$row = call_user_func($this->afterRow, $model, $key, $index, $this);
				if (!empty($row)) {
					$rows[] = $row;
				}
			}
		}
		return $rows;
	}

	/**
	 * @see yii\grid\GridView::renderTableRow()
	 */
	public function renderTableRow($model, $key, $index)
	{
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
		$id = $model[$this->idAttribute];
		Html::addCssClass($options, 'treegrid-'.$id);
		$parentId = $this->getParentId($model, $key, $index);
		if ($parentId !== null) {
			Html::addCssClass($options, 'treegrid-parent-'.$parentId);
		}
		$childCount = $this->getChildCount($model, $key, $index);
		if (array_search($id, $this->_expanded) !== false) {
			Html::addCssClass($options, 'expanded');
		} elseif ($childCount) {
			$options['data-count'] = $childCount;
		}

		return Html::tag('tr', implode('', $cells), $options);
	}

	/**
	 * Additional params for data provider.
	 * @return void
	 */
	protected function initDataProvider()
	{
		$this->dataProvider->pagination = false;
		$this->dataProvider->sort = false;
		if ($this->dataProvider instanceof ActiveDataProvider) {
			$this->initActiveDataProvider();
		}
		if (!$this->lazyLoad && !$this->showRoots) $this->removeRoots();
		if ($this->initialNode) $this->_expanded = $this->initialExpand();
		$this->sortModels();
		$this->dataProvider->setKeys(null);
		$this->dataProvider->prepare();
	}

	/**
	 * Initialization of active data provider.
	 * @return void
	 */
	protected function initActiveDataProvider()
	{
		$id = Yii::$app->getRequest()->get('treegrid_id', null);
		$items = [];
		if ($this->lazyLoad && $this->initialNode !== null && $id === null) {
			$items = $this->loadInitial();
		}
		if ($this->lazyLoad || $id !== null) {
			$this->addLazyCondition($id);
			$this->_token = Yii::$app->getRequest()->get('treegrid_token', null);
			if ($this->_token !== null) $this->options['data-treegrid-token'] = $this->_token;
		}
		if (!empty($items)) {
			$models = array_merge($this->dataProvider->getModels(), $items);
			$this->dataProvider->setModels($models);
		}
	}

	/**
	 * @see yii\grid\GridView::initColumns()
	 */
	protected function initColumns()
	{
		if (empty($this->columns)) {
			$this->guessColumns();
		}
		foreach ($this->columns as $i => $column) {
			if (is_string($column)) {
				$column = $this->createDataColumn($column);
			} else {
				$column = Yii::createObject(array_merge([
					'class' => $this->dataColumnClass ? : DataColumn::className(),
					'grid' => $this,
				], $column));
			}
			if (!$column->visible) {
				unset($this->columns[$i]);
				continue;
			}
			$this->columns[$i] = $column;
		}
	}

	/**
	 * @see yii\grid\GridView::createDataColumn()
	 */
	protected function createDataColumn($text)
	{
		if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
			throw new InvalidConfigException('The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"');
		}
		return Yii::createObject([
			'class' => $this->dataColumnClass ? : DataColumn::className(),
			'grid' => $this,
			'attribute' => $matches[1],
			'format' => isset($matches[3]) ? $matches[3] : 'text',
			'label' => isset($matches[5]) ? $matches[5] : null,
		]);
	}

	/**
	 * @see yii\grid\GridView::guessColumns()
	 */
	protected function guessColumns()
	{
		$models = $this->dataProvider->getModels();
		$model = reset($models);
		if (is_array($model) || is_object($model)) {
			foreach ($model as $name => $value) {
				$this->columns[] = (string) $name;
			}
		}
	}

	/**
	 * Returns parent id of model for row render.
	 * @param mixed $model the data model to be rendered
	 * @return mixed parent id of model. If model does not have a parent node returns null.
	 */
	abstract protected function getParentId($model);

	/**
	 * Returns count of children of model.
	 * @param mixed $model the data model to be rendered
	 * @return integer count of child of the model
	 */
	abstract protected function getChildCount($model);

	/**
	 * Addition conditions for child nodes filtering in lazy load mode when $dataProvider is ActiveDataProvider.
	 * @param string $id node id
	 * @return void
	 */
	abstract protected function addLazyCondition($id);

	/**
	 * Manualy load initial node when $dataProvider is ActiveDataProvider.
	 * @return array
	 */
	abstract protected function loadInitial();

	/**
	 * Make array of expanded nodes to show initial node.
	 * @return array
	 */
	abstract protected function initialExpand();

	/**
	 * Sorting models in data provider. Placing child models after its parent.
	 * @return void
	 */
	abstract protected function sortModels();

	/**
	 * Remove roots from data provider if needed
	 * @return void
	 */
	abstract protected function removeRoots();

}
