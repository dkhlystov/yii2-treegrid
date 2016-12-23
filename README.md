yii2-treegrid
=============
TreeView widget for Yii 2 framework

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
<?= \dkhlystov\widgets\TreeGrid::widget([
		'dataProvider' => $dataProvider,
]); ?>
```

Parent relative tree
-----
[To be written]

Nested sets tree
-----
[To be written]

Lazy loading
-----

By default the `lazyLoad` property is set to `true`. This mean, that widget automatically will add conditions to `dataProvider` (for `yii\data\ActiveDataProvider`) to load children nodes on demand. Initially widget loads only root and its children.

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
<?= \dkhlystov\widgets\TreeGrid::widget([
		'dataProvider' => $dataProvider,
]); ?>
```

Roots
-----
By default the `showRoots` property is set to `false`. To show roots in the tree, set this property to `true`. If you use nested sets tree, make sure that `treeAttribute` property is set correctly. By default it set to `tree`.

Initial node
-----

You can make lazy load tree initially partialy rendered. To do this, set `initialNode` to node that should be visible. Widget will render all parents of this node with their children. All parents will be rendered expanded. It may be usefull when you edit nodes (on redirect to `index` just add `id` of edited node).

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
<?= \dkhlystov\widgets\TreeGrid::widget([
		'dataProvider' => $dataProvider,
		'initialNode' => $initial,
]); ?>
```

Moving nodes
-----

Set `moveAction` property in widget to enable nodes moving. Url will be generated using `yii\helpers\Url::toRoute()` function. Action receives three parameters: `id` - identifier of moving node, `target` - a node identifier, where the movement has been made, `position` - movement position (0 - before target, 1 - into target, 2 - after target).

View :

```php
<?= \dkhlystov\widgets\NestedTreeGrid::widget([
    'dataProvider' => $dataProvider,
    'moveAction' => ['move'],
]); ?>
```

Controller :

```php
    function actionMove($id, $target, $position)
    {
        $model = Menu::findOne($id);

        $t = Menu::findOne($target);

        switch ($position) {
            case 0:
                $model->insertBefore($t);
                break;

            case 1:
                $model->appendTo($t);
                break;
            
            case 2:
                $model->insertAfter($t);
                break;
        }
    }
```
