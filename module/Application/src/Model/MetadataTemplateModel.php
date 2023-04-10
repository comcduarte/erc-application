<?php
namespace Application\Model;

use Components\Model\AbstractBaseModel;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Where;

class MetadataTemplateModel extends AbstractBaseModel
{
    public $id;
    public $type;
    public $copyInstanceOnItemCopy;
    public $displayName;
    public $fields;
    public $hidden;
    public $scope;
    public $templateKey;
    
    public function __construct(Adapter $adapter = NULL)
    {
        parent::__construct($adapter);
        array_push($this->private_attributes,'fields');
        $this->public_attributes = array_diff(array_keys(get_object_vars($this)), $this->private_attributes);
        
        
        $this->setTableName('box_metadata_templates');
    }
    
    public function exchangeArray($data)
    {
        parent::exchangeArray($data);
        
        if (isset($data['fields'])) {
            foreach ($data['fields'] as $id => $record) {
                $field = new MetadataTemplateFieldModel($this->adapter);
                $field->exchangeArray($record);
                $field->templateKey = $this->templateKey;
                $this->fields[$id] = $field;
            }
        }
    }
    
    public function create() 
    {
        /**
         * @var MetadataTemplateFieldModel $objField
         */
        foreach ($this->fields as $objField) {
            $objField->create();
        }
        
        $result = parent::create();
        return $result;
    }
    
    public function read(Array $criteria)
    {
        $result = parent::read($criteria);
        
        $where = new Where();
        $where->equalTo('templateKey', $this->templateKey);
        
        $field = new MetadataTemplateFieldModel($this->adapter);
        $fields = $field->fetchAll($where);
        
        foreach ($fields as $record) {
            $tmp = new MetadataTemplateFieldModel($this->adapter);
            $tmp->read(['id' => $record['id']]);
            $this->fields[$record['id']] = $tmp; 
        }
        
        return $result;
    }
    
    public function update()
    {
        foreach ($this->fields as $id => $objField) {
            $objField->update();
        }
        
        $result = parent::update();
        return $result;
    }
    
    public function delete()
    {
        foreach ($this->fields as $id => $objField) {
            $objField->delete();
        }
        
        $result = parent::delete();
        return $result;
    }
}