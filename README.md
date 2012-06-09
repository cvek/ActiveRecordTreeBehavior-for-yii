ActiveRecordTreeBehavior-for-yii
================================

Работа с иерархичными (id - parent_id) ActiveRecord такой структуры:

id_primary_key
id_parent
sequence
name
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


Список методов:
getTree()
getParent()
getChildCount()
getChild()
isChildExists()
getChildById($id)
getParentById($id)
isAncestor(CActiveRecord $model)
isDescendant(CActiveRecord $model, $checkSelf = false)
getRootParent()
