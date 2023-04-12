<?php
namespace Application\Form;

use Components\Form\AbstractBaseForm;
use Components\Form\Element\DatabaseSelect;
use Laminas\Db\Adapter\AdapterAwareTrait;
use Laminas\Form\Element\Button;
use Laminas\Form\Element\Hidden;

class SelectMetadataTemplateForm extends AbstractBaseForm
{
    use AdapterAwareTrait;
    
    public function init()
    {
        parent::init();
        
        $this->add([
            'name' => 'TEMPLATE',
            'type' => DatabaseSelect::class,
            'attributes' => [
                'class' => 'form-control',
                'id' => 'TEMPLATE',
                'required' => 'true',
                'placeholder' => '',
            ],
            'options' => [
                'label' => 'Metadata Template',
                'database_adapter' => $this->adapter,
                'database_table' => 'box_metadata_templates',
                'database_id_column' => 'UUID',
                'database_value_columns' => [
                    'DISPLAYNAME',
                ],
            ],
        ],['priority' => 100]);
        
        $this->add([
            'name' => 'FILE_ID',
            'type' => Hidden::class,
            'attributes' => [
                'id' => 'FILE_ID',
                'required' => 'true',
            ],
        ]);
        
        $this->add([
            'name' => 'BUTTON',
            'type' => Button::class,
            'attributes' => [
                'id' => 'BUTTON',
                'class' => 'btn btn-outline-primary',
                'onclick' => "",
            ],
            'options' => [
                'label' => 'Refresh List',
            ],
        ]);
        
    }
}