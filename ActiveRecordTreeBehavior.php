<?php
class ActiveRecordTreeBehavior extends CActiveRecordBehavior{

  /**
   * Сортировка данных: имя поля родителя, имя поля порядка
   * @property string
   */
  public $order = 'id_parent DESC, sequence ASC';
  /**
   * Имя поля родительского ключа
   * @property string
   */
  public $idParentField = 'id_parent';
  /**
   * with-параметр для выборки
   * @var string
   */
  public $with = null;
  
  private $_parent = null;
  private $_child = array();
  
  private static $_tree = array();
  
  /**
   * Установка родительского экземпляра
   * @param CActiveRecord $parent Родитель
   */
  public function setParent(CActiveRecord $parent) {
    $this->_parent = $parent;
  }
  /**
   * Установка дочерних экземепляров
   * @param array $child массив из дочерних экземпляров
   */
  public function setChild(array $child) {
    $this->_child = $child;
  }
  /**
   * Добавление дочки
   * @param CActiveRecord $child дочерний экземпляр
   */
  public function addChild(CActiveRecord $child) {
    $this->_child[] = $child;
  }
  
  /**
   * Получение дерева экземпляров модели
   * @param mixed $addCriteria параметры запроса на получение данных
   * @param string $cacheKey ключ для кэширования данных
   */
  public function getTree($addCriteria=array(), $cacheKey=null) {
    $cacheKey = ($cacheKey == null ? $this->owner->tableName() : $cacheKey);
    if ( isset(self::$_tree[$cacheKey]) ) {
      return self::$_tree[$cacheKey];
    }
    
    $tree = array();
    if (($cache = Yii::app()->cache) !== null && ($val = $cache->get($cacheKey)) !== false) {
      $tree = $val;
    } else {
      $criteria = new CDbCriteria();
      $criteria->order = $this->order;
      if ($this->with != null) $criteria->with = $this->with;
      $criteria->mergeWith($addCriteria);
      $items = $this->owner->model()->findAll($criteria);

      $child = array();
      $countIntems = count($items);
      
      $idParentField = $this->idParentField;

      for($i = 0; $i < $countIntems; $i++) {
        $item = $items[$i];
        $id = $item->getPrimaryKey();
        $idParent = $item->$idParentField;
        if ($idParent !== null) {
          $child[$idParent][] = $item;
        }
      }
      $tree = $this->owner->model();
      for ($i = 0; $i < $countIntems; $i++) {
        $item = $items[$i];
        $id = $item->getPrimaryKey();
         
        if (isset($child[$id])) {
          $countChild = count($child[$id]);
          for($k = 0; $k < $countChild; $k++) {
            $child[$id][$k]->setParent($item);
          }
          $item->setChild($child[$id]);
        }
        if ($item->$idParentField === null) {
          $tree->addChild($item);
        }
      }
      
      if ($cache !== null) {
        $cache->set($cacheKey, $tree, 3600);
      }
      
    }
    
    self::$_tree[$cacheKey] = $tree;
    return $tree;
  }
  
  /**
   * Получение родительского экземпляра
   */
  public function getParent() {
    $idParentField = $this->idParentField;
    if ($this->owner->$idParentField !== null && $this->_parent === null) {
      $this->_parent = $this->owner->model()->findByPk($this->owner->$idParentField);
    } 
    return $this->_parent;
  }  
  
  /**
   * Получение количества детей
   */
  public function getChildCount() {
    return count($this->_child);
  }
  
  /**
   * Получение всех дочерних экземпляров
   */
  public function getChild() {
    return $this->_child;
  }
  
  /**
   * Есть ли дочерние экземпляры
   */
  public function isChildExists() {
    return ($this->getChildCount() > 0);
  }
  
  /**
   * Поиск потомка текущего экземпляра по ИД
   * Поддерживается только работа с не составным первичным ключом
   * @param mixed $id
   */
  public function getChildById($id) {
    $childCount = $this->getChildCount();
    
    $res = null;
    for($i = 0; $i < $childCount; $i++) {
      if ($this->_child[$i]->getPrimaryKey() == $id) {
        return $this->_child[$i];
      }
      $res = $this->_child[$i]->getChildById($id);
      
      if ($res !== null) {
        return $res;
      } 
    }
    
    return $res;
  }
  
  /**
   * Поиск предка текущего экземпляра по ИД
   * Поддерживается только работа с не составным первичным ключом
   * @param mixed $id
   */
  public function getParentById($id) {
    $parent = $this->getParent();
    if ($parent !== null) {
      if ($parent->getPrimaryKey() == $id) {
        return $parent;
      } else {
        return $parent->getParentById($id);
      }
    }
    return null;
  }
  
  /**
   * Является ли текущий объект предком для модели из параметров
   * @param DaActiveRecord $model
   */
  public function isAncestor(CActiveRecord $model) {
    return $model->getParentById($this->owner->getPrimaryKey()) !== null;
  }
  
  /**
   * Является ли текущий объект потомком для модели из параметров
   * @param DaActiveRecord $model
   */
  public function isDescendant(CActiveRecord $model, $checkSelf = false) {
    if ($checkSelf && $this->owner->getPrimaryKey() == $model->getPrimaryKey()) return true;
    return $this->getParentById($model->getPrimaryKey()) !== null;
  }
  
  /**
   * Получение корневого предка
   */
  public function getRootParent() {
    $parent = $this->getParent();
    if ($parent === null) {
      return $this->owner;
    }
    return $parent->getRootParent();
  }
  
  /**
   * Поиск экземпляра, включая проверку и текущего экземпляра
   * @param mixed $id
   */
  public function getById($id) {
    if ($this->owner->getPrimaryKey() == $id) {
      return $this;
    }
    return $this->getChildById($id);
  }

  protected function afterSave($event){
    $cacheKey = ($cacheKey == null ? $this->owner->tableName() : $cacheKey);

    Yii::app()->cache->delete($id);
  }

}
