yii2-treegrid
=============
TreeView widget for Yii PHP Framework Version 2

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require dkhlystov/yii2-treegrid
```

or add

```
"dkhlystov/yii2-treegrid": "*"
```

to the require section of your `composer.json` file.


Basic usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?= \dkhlystov\widgets\TreeView::widget([
		'dataProvider' => $dataProvider,
]); ?>
```

Lazy loading
-----

By default the `lazyLoad` property is set to `true`. This mean, that widget automatically will add conditions to `dataProvider` (for `yii\data\ActiveDataProvider`) to load children nodes on demand.

Controller :

```php
    function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Menu::find(),
        ]);
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }
```

View :

```php
<?= \dkhlystov\widgets\TreeView::widget([
		'dataProvider' => $dataProvider,
]); ?>
```

Roots
-----
[To be written]

Initial node
-----

You can make lazy load tree initialy partialy rendered. To do this, set `initialNode` to node that should be visible. Widget will render all parents of this node with their children. All parents will be rendered expanded. It may be usefull when you edit nodes (on redirect to `index` just add `id` of edited node).

Controller :

```php
    function actionIndex($id = null)
    {
        $initial = Menu::findOne($id);

        $dataProvider = new ActiveDataProvider([
            'query' => Menu::find(),
        ]);
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'initial' => $initial,
        ]);
    }
```

View :

```php
<?= \dkhlystov\widgets\TreeView::widget([
		'dataProvider' => $dataProvider,
		'initialNode' => $initial,
]); ?>
```

Moving nodes
-----
[To be written]
