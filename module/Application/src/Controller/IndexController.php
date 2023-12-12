<?php
declare(strict_types=1);

namespace Application\Controller;

use Application\Model\MetadataTemplateModel;
use Application\Model\VisionPropertyModel;
use Components\Form\UploadFileForm;
use Components\Form\Element\DatabaseSelect;
use Laminas\Box\API\AccessTokenAwareTrait;
use Laminas\Box\API\Form\MetadataForm;
use Laminas\Box\API\Resource\File;
use Laminas\Box\API\Resource\Folder;
use Laminas\Box\API\Resource\Items;
use Laminas\Box\API\Resource\Representation;
use Laminas\Db\Adapter\AdapterAwareTrait;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Exception;

class IndexController extends AbstractActionController
{
    use AdapterAwareTrait;
    use AccessTokenAwareTrait;
    
    public $vision_adapter;
    
    /**
     * @var VisionPropertyModel
     */
    public $vision_model;
    
    public $select_metadata_template_form;
    
    /**
     *
     * @var \Laminas\Log\Logger
     */
    public $logger;
    
    public function indexAction()
    {
        $view = new ViewModel();
        
        $view->setVariable('form', $this->select_metadata_template_form);
        
        /******************************
         * Upload Individual File Form
         ******************************/
        $upload_form = new UploadFileForm();
        $upload_form->init();
        $upload_form->setName('upload-file-form');
        $view->setVariable('upload_form', $upload_form);
        
        $folder = new Folder($this->getAccessToken());
        $folder->get_folder_information('170113907500');    //-- Test Folder 1 --//
        
        /**
         *
         * @var Items $items
         * @var File $item
         */
        $previews = [];
        $images = [];
        $items = $folder->list_items_in_folder($folder->id);
        foreach ($items->entries as $item) {
            if ($item['type'] == 'file') {
                $previews[$item['id']] = $item['name'];
                
                $file = new File($this->getAccessToken());
                $file->list_all_representations();
                $file->get_file_information($item['id']);
                $file->request_desired_representation(Representation::TYPE_JPG,Representation::DIMENSION_320x320);
                try {
                    $content = $file->download_file_representation();
                    $images[$item['id']] = base64_encode($content->getContent());
                } catch (Exception $e) {
                    $this->logger->info($e->getMessage());
                }
                
                
            }
        }
        $view->setVariable('previews', $previews);
        $view->setVariable('images', $images);
        return $view;
    }
    
    public function formAction()
    {
        $prikey = '';
        
        $view = new ViewModel();
        
        $metadata_template = new MetadataTemplateModel($this->adapter);
        
        $form = new MetadataForm();
        $form->setMetadataTemplate($metadata_template);
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
                );
            $prikey = $post['TEMPLATE'];
        }
        
        if (!$metadata_template->read([$metadata_template->getPrimaryKey() => $prikey])) {
            $this->logger->info('Unable to read metadata template.');
            return $view;
        }
        
        $view->setVariable('template_name', $metadata_template->displayName);
        
        /**
         * Create Application Specific Form Element for Street Name
         */
        $select = new \Laminas\Db\Sql\Select();
        $select->from($this->vision_model->getTableName());
        $select->columns(['REM_PID' => 'REM_PRCL_LOCN_STREET','REM_PRCL_LOCN_STREET']);
        $select->order(['REM_PRCL_LOCN_STREET']);
        $select->group(['REM_PRCL_LOCN_STREET']);
        
        $element = new DatabaseSelect();
        $element->setDbAdapter($this->vision_adapter);
        $element->setDatabase_table($this->vision_model->getTableName());
        $element->setDatabase_id_column('REM_PID');
        $element->setDatabase_value_columns(['REM_PRCL_LOCN_STREET']);
        $element->setDatabase_object($select);
        $element->setAttribute('class', 'form-select');
        $element->setName('streetName');
        $element->setAttributes([
            'id' => 'streetName',
            'required' => true,
        ]);
        $element->setOptions([
            'label' => 'Street Name',
        ]);
        
        $form->init();
        $form->remove('streetName');
        $form->add($element, ['priority' => 1]);
        
        if ($post['FILE_ID']) {
            $form->get('FILE_ID')->setValue($post['FILE_ID']);
            $form->remove('FILE');
            
            /**
             * Retrieve readable representation of document
             * 
             * @var Items $items
             * @var File $item
             */
            $file = new File($this->getAccessToken());
            $file->list_all_representations();
            $file->get_file_information($post['FILE_ID']);
            $file->request_desired_representation(Representation::TYPE_JPG,Representation::DIMENSION_1024x1024);
            try {
                $content = $file->download_file_representation();
            } catch (Exception $e) {
                $this->logger->info($e->getMessage());
            }
            
            $image = base64_encode($content->getContent());
            
            $view->setVariable('image', $image);
            
        } else {
            $form->remove('FILE_ID');
        }
        
        $view->setVariable('form', $form);
        return $view;
    }
}
