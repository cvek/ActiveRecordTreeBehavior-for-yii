ActiveRecordTreeBehavior-for-yii
================================

Работа с иерархичными (id - parent_id) ActiveRecord такой структуры:

id_primary_key<br>
id_parent<br>
sequence<br>
name<br>
...

Подключается к модели следующим образом (пример для категорий товаров):

      public function behaviors() {
        return array(
          'tree' => array(
            'class' => 'ActiveRecordTreeBehavior',
            'order' => 'id_parent DESC, sequence ASC',
            'idParentField' => 'id_parent',
            'with' => 'productCount',
          ),
        );
      }
     

Используем в коде:

    // получаем дерево экземпляров:
    $tree = ProductCategory::model()->getTree();
    // кол-во корневых элементов:
    $count = $tree->getChildCount();
    ...


Список методов:<br>
getTree()<br>
getParent()<br>
getChildCount()<br>
getChild()<br>
isChildExists()<br>
getChildById($id)<br>
getParentById($id)<br>
isAncestor(CActiveRecord $model)<br>
isDescendant(CActiveRecord $model, $checkSelf = false)<br>
getRootParent()<br>
