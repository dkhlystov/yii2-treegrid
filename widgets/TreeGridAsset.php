<?php

namespace dkhlystov\widgets;

use yii\web\AssetBundle;

class TreeGridAsset extends AssetBundle {

	public $sourcePath = '@bower/treegrid/dist';

// 	public $js = [
// 		'js/jquery.treegrid'.(YII_DEBUG ? '' : '.min').'.js',
// 	];

	public $css = [
		'css/jquery.treegrid.css',
	];

	public $depends = [
		'yii\web\JqueryAsset',
	];
	
    	public function registerAssetFiles( $view ){
		$this->js = [
		    'js/jquery.treegrid'.(YII_DEBUG ? '' : '.min').'.js',
		];
		parent::registerAssetFiles( $view );
    	}
}
