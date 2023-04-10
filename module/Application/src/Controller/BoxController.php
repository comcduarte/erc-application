<?php
namespace Application\Controller;

use Application\Model\MetadataTemplateModel;
use Laminas\Box\API\AccessTokenAwareTrait;
use Laminas\Box\API\Form\MetadataForm;
use Laminas\Box\API\Resource\ClientError;
use Laminas\Box\API\Resource\File;
use Laminas\Box\API\Resource\Folder;
use Laminas\Box\API\Resource\MetadataInstance;
use Laminas\Box\API\Resource\MetadataTemplate;
use Laminas\Box\API\Resource\Upload;
use Laminas\Db\Adapter\AdapterAwareTrait;
use Laminas\Mvc\Controller\AbstractActionController;

class BoxController extends AbstractActionController
{
    use AdapterAwareTrait;
    use AccessTokenAwareTrait;
    
    /**
     * 
     * @var \Laminas\Log\Logger
     */
    public $logger;
    
    public function redirectAction() 
    {
        $this->logger->info('Entered Redirect Action');
        return $this->redirect()->toRoute('home');
    }
    
    public function refreshMetadataTemplatesAction()
    {
        $this->logger->info('Entered Metadata Refresh Action');
        
        $metadata_templates = new MetadataTemplate($this->getAccessToken());
        try {
            $metadata_templates->list_all_metadata_templates_for_enterprise();
            $this->logger->info('Retrieved list of Metadata Templates');
        } catch (\Exception $e) {
            $this->logger->info('Exception: Unknown');
            $this->redirectAction();
        }
        $response = json_decode($metadata_templates->getResponse()->getContent(),TRUE);
        
        foreach ($response['entries'] as $data) {
            $model = new MetadataTemplateModel($this->adapter);
            
            if ($model->read(['id' => $data['id']])) {
                $model->exchangeArray($data);
                if ($model->update()) {
                    $this->logger->info('Model Updated: ' . $model->displayName);
                } else {
                    $this->logger->info('Error: Update Failed: ' . $model->displayName);
                }
            } else {
                $model->exchangeArray($data);
                if ($model->create()) {
                    $this->logger->info('Model Created: ' . $model->displayName);
                } else {
                    $this->logger->info('Error: Model Creation Failed: ' . $model->displayName);
                }
            }
        }
        
        $this->redirectAction();
    }

    public function uploadAction()
    {
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $data = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
                );
            
            $metadata_template = new MetadataTemplateModel($this->adapter);
            if (!$metadata_template->read(['templateKey' => $data['template_key']])) {
                $this->logger->info('Unable to read metadata template');
            }
            
            $form = new MetadataForm();
            $form->setMetadataTemplate($metadata_template);
            $form->init();
            
            $form->setData($data);
            
            if ($form->isValid()) {
            
                $folder = new Folder($this->getAccessToken());
                $folder->get_folder_information('170113907500');    //-- Test Folder 1 --//
                
                /**
                 * Create the Street Folder if it doesn't exist.
                 * @var \Laminas\Box\API\Resource\Folder $streetFolder
                 */
                $streetFolder = $folder->create_folder($folder->id, $data['streetName']);
                
                /**
                 * Create the Building Folder.
                 * @var \Laminas\Box\API\Resource\Folder $buildingFolder
                 */
                $buildingFolder = $streetFolder->create_folder($streetFolder->id, $data['houseBuildingNumber']);
                
                /**
                 * Upload the File
                 * @var \Laminas\Box\API\Resource\Upload $upload
                 */
                $name = sprintf('%s - %s - %s - %s.pdf', $data['houseBuildingNumber'], $data['streetName'], $data['recordType'], $data['originalDocumentDate']);
                
                $attributes = [
                    'name' => $name,
                    'parent' => [
                        'id' => $buildingFolder->id,
                    ],
                ];
                
               
                if (isset($data['FILE_ID'])) {
                    $file = new File($this->getAccessToken());
                    $response =  $file->copy_file($data['FILE_ID'], $attributes);
                    
                    switch (true) {
                        case ($response instanceof File):
                            $file->delete_file($data['FILE_ID']);
                            break;
                        default:
                            /**
                             * @var ClientError $response
                             */
                            $this->logger->info($response->message);
                            $this->flashMessenger()->addErrorMessage($response->message);
                            break;
                    }
                    return $this->redirectAction();
                }
                
                 if (isset($data['FILE'])) {
                    $upload = new Upload($this->getAccessToken());
                    $filename = $data['FILE']['tmp_name'];
                    $files = $upload->upload_file($attributes, $filename);
                    
                } 
                
                /**
                 * 
                 * @var \Laminas\Box\API\Resource\File $file
                 */
                $file = $files->entries['0'];
                $template_key = $data['template_key'];
                
                $exclude = [
                    'SUBMIT' => $data['SUBMIT'],
                    'SECURITY' => $data['SECURITY'],
                    'FILE' => $data['FILE'],
                    'template_key' => $data['template_key'],
                ];
                $data = array_diff($data, $exclude);
                $data['originalDocumentDate'] = date('c', strtotime($data['originalDocumentDate']));
                
                
                $metadata_instance = new MetadataInstance($this->getAccessToken());
                $retval = $metadata_instance->create_metadata_instance_on_file($file->id, $this->getAccessToken()->box_subject_type . '_' . $this->getAccessToken()->box_subject_id, $template_key, $data);
                
                if (is_a($retval, 'Exception')) {
                    $this->logger->err($retval->getMessage());
                }
            
                $this->logger->info('Successfully Uploaded File');
            } else {
                foreach ($form->getMessages() as $message) {
                    if (is_array($message)) {
                        $message = array_pop($message);
                    }
                    $this->logger->info($message);
                    $this->flashMessenger()->addErrorMessage($message);
                }
                
            }
            
        }
        
        $this->redirectAction();
    }

}


