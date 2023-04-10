<?php
namespace Application\Model;

use Laminas\Db\Adapter\Adapter;
use Components\Model\AbstractBaseModel;

class MetadataTemplateFieldModel extends AbstractBaseModel
{
    public $templateKey;
    public $sequence;
    
    public $id;
    public $type;
    public $key;
    public $displayName;
    public $hidden;
    public $options;
    
    public function __construct(Adapter $adapter = NULL)
    {
        parent::__construct($adapter);
        $this->setTableName('box_metadata_template_fields');
    }
    
    public function create()
    {
        $strval = '';
        
        if (is_array($this->options)) {
            $aryval = [];
            foreach ($this->options as $array) {
                $aryval[] = $array['key'];
            }
            $strval = implode($aryval, ",");
        }
        $this->options = $strval;
        
        $retval = parent::create();
        return $retval;
    }
}