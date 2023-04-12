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
use Laminas\Box\API\Resource\MetadataInstance;
use Laminas\Box\API\Resource\MetadataTemplate;
use Laminas\Box\API\Resource\Representation;
use Laminas\Box\API\Resource\Upload;
use Laminas\Db\Adapter\AdapterAwareTrait;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Exception;
use PDOException;

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
                    $message = $e->getMessage();
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
                $message = $e->getMessage();
            }
            
            $image = base64_encode($content->getContent());
            
            $view->setVariable('image', $image);
            
        } else {
            $form->remove('FILE_ID');
        }
        
        $view->setVariable('form', $form);
        return $view;
    }
    
    public function testAction()
    {
        $view = new ViewModel();
        $view->setVariable('access_token', $this->getAccessToken()->getResponse());
//         return $view;
        
        /**************************************************
         * Folder
         **************************************************/
        $folder = new Folder($this->getAccessToken());
//         $folder->get_folder_information('0');
//         $folder->get_folder_information('170113907500');    //-- Test Folder 1 --//
        /**
         * 
         * @var Items $items
         */
        $items = $folder->list_items_in_folder('170113907500');
//         $view->setVariable('items', $items->entries);
        $view->setVariable('folder', $folder->getResponse());
        
        
        /**************************************************
         * File
         **************************************************/
        $file = new File($this->getAccessToken());
        $file->list_all_representations();
        $file->get_file_information('1060882484037');
        $view->setVariable('file', $file->getResponse());
        
        $retval = $file->download_file('1060882484037');
        
        /**************************************************
         * Representation
         **************************************************/
        $file->request_desired_representation('jpg','94x94');
        $view->setVariable('representation', $file->getResponse());
        
        $content = $file->download_file_representation();
        $img = base64_encode($content->getContent());
        $view->setVariable('Image', $img);
        
        return $view;
        /**************************************************
         * Metadata Templates
         **************************************************/
        $metadata_templates = new MetadataTemplate($this->getAccessToken());
        $metadata_templates->list_all_metadata_templates_for_enterprise();
//         $metadata_templates->get_metadata_template_by_id('3c310734-cfa9-40f1-a681-258f561736f6');
        $view->setVariable('enterprise_metadata_templates', $metadata_templates->getResponse());
        return $view;
        
        /**************************************************
         * Direct Upload File
         **************************************************/
        $data = [];
        $upload = new Upload($connection);
        $attributes = [
            'name' => 'new_name3.txt',
            'parent' => [
                'id' => $folder->id,
            ],
        ];
        
        /**
         * Using tmp_name from POST, actual path not file contents.
         */
        $file = $file_name;
        
        /**
         * @var \Laminas\Box\API\Resource\Files $files
         */
        $files = $upload->upload_file($attributes, $file);
        $view->setVariable('direct_upload_file', $upload->getResponse());
        
        /**************************************************
         * Create Metadata Instance for File
         * Use metadata from above.
         *
         * @var File $file
         **************************************************/
        $file = $files->entries[0];
        $metadata_instance->create_metadata_instance_on_file($file->id, $access_token->box_subject_type . '_' . $access_token->box_subject_id, 'healthDepartmentEnvironmentalData', $meta_data);
        $view->setVariable('create_metadata_instanceon upload', $metadata_instance->getResponse());
        
        return $view;
        
        
        
        
        
        /**************************************************
         * Files
         **************************************************/
        $file = new File($this->getAccessToken());
//         $file->id = '998737414395'; //-- README.txt --//
        $file->id = '1001159600047';    //-- CopyOfREADME.txt --//
        $data['parent'] = ['id' => '170113907500'];
        $data['name'] = 'CopyofREADME.txt';
//         $file->copy_file($file->id, $data);
//         $view->setVariable('copy_readme_file_info', $file->response);
        $file->get_file_information($file->id);
        $view->setVariable('readme_file_info', $file->getResponse());
        
        /**************************************************
         * Get Metadata Instance for File
         **************************************************/
        $metadata_instance = new MetadataInstance($connection);
//         $metadata_instance->get_metadata_instances_on_file($file->id, $access_token->box_subject_type . '_' . $access_token->box_subject_id, 'healthDepartmentEnvironmentalData');
        $metadata_instance->list_metadata_instances_on_file($file->id);
        $view->setVariable('metadata_instance_on_file', $metadata_instance->getResponse());
        //-- If no metadata instance is available, returns 404 Not Found
        
        /**************************************************
         * Create Metadata Instance for File
         **************************************************/
        $meta_data = [
            'recordType' => 'Complaint',                //-- Currently using enum specified value.  Test with unconventional value.
            'originalDocumentDate' => date("c", strtotime('2022-08-01')),               //-- yyyy-MM-dd'T'HH:mm:ssZ  maybe Y-m-d'T'H:i:sZ--//
            'author' => 'John Dough',                   
            'streetName' => 'Main Street',              //-- Later replaced with verified street name from TA --//
            'houseBuildingNumber' => '555',
            'outdatedPlotOrLotNumberUnknown' => 'No',  //-- Purposely leaving this field out of the array. --//
            'containsPii' => 'Yes',
        ];
//         $metadata_instance->create_metadata_instance_on_file($file->id, $access_token->box_subject_type . '_' . $access_token->box_subject_id, 'healthDepartmentEnvironmentalData', $meta_data);
//         $view->setVariable('create_metadata_instance', $metadata_instance->response);
        
       
        /**************************************************
         * Start an Upload Session - Requires 20MB file minimum
         **************************************************/
        $file_name = "/tmp/Upload-File-Test.txt";
        
        $file = fopen($file_name, 'w');
        for ($i = 0; $i < 10; $i++) {
            fwrite($file,file_get_contents('http://loripsum.net/api'));
        }
        fclose($file);
        
//         $file_size = filesize($file_name);
        
//         $upload_session = new UploadSession($connection);
//         $upload_session->create_upload_session($file_name, $file_size, $folder->id);
        
//         $view->setVariable('start_an_upload_session', $upload_session->response);
        
        /**************************************************
         * Upload Part
         **************************************************/
//         $upload_session->upload_file($file_name, $file_size);
//         $view->setVariable('upload_file', $upload_session->response);
        
        /**************************************************
         * Remove Upload Session
         **************************************************/
//         $upload_session->remove_upload_session($upload_session_id);
//         $view->setVariable('remove_upload_session', $upload_session->response);
        
        
    }

    public function testdbAction()
    {
        $view = new ViewModel();
        // Database connection details
        $dsn = 'sqlsrv:Server=ta-vision01;Database=VISION';
        $user = 'erc';
        $pass = 'Middletown2022!';
        
        // Initialise
        $conn = null;
        try {
            // Database connection
            $conn = sqlsrv_connect('TA-VISION01', [
                'Database' => 'VISION',
                'UID' => 'erc',
                'PWD' => 'Middletown2022!',
                'TrustServerCertificate' => true,
            ]);
            $errors = sqlsrv_errors();
//             $pdoObj = new \PDO($dsn, $user, $pass);
            if($errors){
                $view->setVariable('response',  $errors[0]['message'] . " - " . $errors[0]['code']);
            }
        }
        catch(PDOException $pe){
            // Throw exception
            $view->setVariable('response', 'Critical Error: Unable to connect to Database Server because: '.$pe->getMessage());
        }
        return $view;
    }
}
