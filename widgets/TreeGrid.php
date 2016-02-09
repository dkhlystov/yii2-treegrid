<?php

namespace dkhlystov\widgets;

use yii\base\Widget;
use yii\helpers\Html;

class TreeGrid extends Widget {

	public function init() {
		parent::init();
	}

	public function run() {
		TreeGridAsset::register($this->view);
		echo Html::tag('div', 'tree grid');
	}

}
