yii2-treegrid
=============
TreeView widget for Yii PHP Framework Version 2

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist dkhlystov/yii2-treegrid "*"
```

or add

```
"dkhlystov/yii2-treegrid": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?= \dkhlystov\widgets\TreeView::widget([
		'dataProvider'=>$dataProvider,
]); ?>
```
