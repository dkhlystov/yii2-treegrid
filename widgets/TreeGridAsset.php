<?php

namespace dkhlystov\widgets;

use yii\web\AssetBundle;

class TreeGridAsset extends AssetBundle {

	public $sourcePath = '@bower/treegrid/dist';

	public $css = [
		'css/jquery.treegrid.css',
	];

	public $depends = [
		'yii\web\JqueryAsset',
	];

	public function init()
	{
		parent::init();

		$this->js = [
			'js/jquery.treegrid'.(YII_DEBUG ? '' : '.min').'.js',
		];
	}

}
