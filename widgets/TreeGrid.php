<?php

namespace dkhlystov\widgets;

use yii\base\Widget;
use yii\helpers\Html;

class TreeGrid extends Widget {

	public function run() {
		echo Html::tag('div', 'tree grid');
	}

}
