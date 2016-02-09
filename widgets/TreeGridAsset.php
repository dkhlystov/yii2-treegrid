<?php

namespace dkhlystov\widgets;

use yii\web\AssetBundle;

class TreeGridAsset extends AssetBundle {

	public $sourcePath = '@bower/treegrid/dist';

	public $js = [
		'js/jquery.treegrid.min.js',
	];

	public $css = [
		'css/jquery.treegrid.min.css',
	];

	public $depends = [
		'yii\web\JqueryAsset',
	];

}
