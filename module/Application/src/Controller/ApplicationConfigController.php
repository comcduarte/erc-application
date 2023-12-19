<?php
namespace Application\Controller;

use Application\Model\VisionPropertyModel;
use Components\Controller\AbstractConfigController;
use Components\Form\UploadFileForm;
use Laminas\Box\API\AccessTokenAwareTrait;
use Laminas\Box\API\Resource\Folder;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Ddl\CreateTable;
use Laminas\Db\Sql\Ddl\DropTable;
use Laminas\Db\Sql\Ddl\Column\Datetime;
use Laminas\Db\Sql\Ddl\Column\Integer;
use Laminas\Db\Sql\Ddl\Column\Text;
use Laminas\Db\Sql\Ddl\Column\Varchar;
use Laminas\Db\Sql\Ddl\Constraint\PrimaryKey;
use Laminas\Validator\Db\RecordExists;
use Laminas\View\Model\ViewModel;
use Settings\Model\SettingsModel;
use Exception;
use Laminas\Box\API\Resource\Collaborations;
use Laminas\Box\API\Resource\User;
use Laminas\Box\API\Role;
use Laminas\Box\API\Resource\Collaboration;
use Laminas\Box\API\Resource\ClientError;

class ApplicationConfigController extends AbstractConfigController
{
    use AccessTokenAwareTrait;
    
    public function clearDatabase()
    {
        $sql = new Sql($this->adapter);
        $ddl = [];
        
        $ddl[] = new DropTable('box_metadata_templates');
        $ddl[] = new DropTable('box_metadata_template_fields');
        
        foreach ($ddl as $obj) {
            $this->adapter->query($sql->buildSqlString($obj), $this->adapter::QUERY_MODE_EXECUTE);
        }
        
        $this->clearSettings('USER');
    }

    public function createDatabase()
    {
        $sql = new Sql($this->adapter);
        
        /******************************
         * Box Metadata Templates
         ******************************/
        $ddl = new CreateTable('box_metadata_templates');
        
        $ddl->addColumn(new Varchar('UUID', 36));
        $ddl->addColumn(new Integer('STATUS', TRUE));
        $ddl->addColumn(new Datetime('DATE_CREATED', TRUE));
        $ddl->addColumn(new Datetime('DATE_MODIFIED', TRUE));
        
        $ddl->addColumn(new Varchar('id', 255, TRUE));
        $ddl->addColumn(new Varchar('type', 255, TRUE));
        $ddl->addColumn(new Varchar('copyInstanceOnItemCopy', 255, TRUE));
        $ddl->addColumn(new Varchar('displayName', 255, TRUE));
        $ddl->addColumn(new Varchar('hidden', 255, TRUE));
        $ddl->addColumn(new Varchar('scope', 255, TRUE));
        $ddl->addColumn(new Varchar('templateKey', 255, TRUE));
        
        $ddl->addConstraint(new PrimaryKey('UUID'));
        
        $this->adapter->query($sql->buildSqlString($ddl), $this->adapter::QUERY_MODE_EXECUTE);
        unset($ddl);
        
        /******************************
         * Box Metadata Template Fields
         ******************************/
        $ddl = new CreateTable('box_metadata_template_fields');
        
        $ddl->addColumn(new Varchar('UUID', 36));
        $ddl->addColumn(new Integer('STATUS', TRUE));
        $ddl->addColumn(new Datetime('DATE_CREATED', TRUE));
        $ddl->addColumn(new Datetime('DATE_MODIFIED', TRUE));
        
        $ddl->addColumn(new Varchar('id', 255, TRUE));
        $ddl->addColumn(new Varchar('type', 255, TRUE));
        $ddl->addColumn(new Varchar('key', 255, TRUE));
        $ddl->addColumn(new Varchar('displayName', 255, TRUE));
        $ddl->addColumn(new Varchar('hidden', 255, TRUE));
        $ddl->addColumn(new Text('options', TRUE));
        $ddl->addColumn(new Varchar('templateKey', 255, TRUE));
        $ddl->addColumn(new Varchar('sequence', 255, TRUE));
        
        $ddl->addConstraint(new PrimaryKey('UUID'));
        
        $this->adapter->query($sql->buildSqlString($ddl), $this->adapter::QUERY_MODE_EXECUTE);
        unset($ddl);
    }
    
    public function indexAction()
    {
        $view = new ViewModel();
        $view->setTemplate('application/config/index');
        $view->setVariables([
            'route' => $this->getRoute(),
        ]);
        
        /******************************
         * Checks and Balances
         ******************************/
        $folder = new Folder($this->getAccessToken());
        $folder->get_folder_information('0');
        $view->setVariable('folder', $folder->getResponse());
        
        $settings = new SettingsModel($this->adapter);
        $settings->read(['MODULE' => 'ERC', 'SETTING' => 'APP_FOLDER_NAME']);
        
        $items = $folder->list_items_in_folder('0');
        if ($items->total_count == 0) {
            $app_folder = $folder->create_folder('0', $settings->VALUE);
            $settings->read(['MODULE' => 'ERC', 'SETTING' => 'APP_FOLDER_ID']);
            $settings->VALUE = $app_folder->id;
            $settings->update();
        } else {
            $settings->read(['MODULE' => 'ERC','SETTING' => 'APP_FOLDER_ID']);
            $app_folder = $settings->VALUE;
        }
  
        /**
         * Add Collaborators
         * @var User $user
         */
        $user = new User($this->access_token);
        $user->login = 'christopher.duarte@middletownct.gov';
                
        $item = $folder->get_folder_information($app_folder);
        $role = Role::CO_OWNER;
        
        $collaboration = new Collaboration($this->access_token);
        try {
            // $result = $collaboration->create_collaboration($user, $item, $role);
        } catch (ClientError $e) {
            $this->flashmessenger->error($e->message);
        }
        
        
        return ($view);
    }
    
    public function visionAction()
    {
        /****************************************
         * Generate Form
         ****************************************/
        $request = $this->getRequest();
        
        $form = new UploadFileForm();
        $form->init();
        $form->addInputFilter();
        
        if ($request->isPost()) {
            $data = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
                );
            
            $form->setData($data);
            
            if ($form->isValid()) {
                $data = $form->getData();
                if (($handle = fopen($data['FILE']['tmp_name'],"r")) !== FALSE) {
                    
                    /****************************************
                     * Retrieve First Line
                     ****************************************/
                    $header = fgetcsv($handle, NULL, ",");
                    
                    /****************************************
                     * Remove BOM
                     * https://stackoverflow.com/questions/20124630/strange-characters-in-first-row-of-array-after-fgetcsv
                     ****************************************/
                    $header[0] = preg_replace('/\x{EF}\x{BB}\x{BF}/', '', $header[0]);
                    
                    $vision = new VisionPropertyModel($this->adapter);
                    
                    $validator = new RecordExists([
                        'adapter' => $this->adapter,
                        'table' => $vision->getTableName(),
                        'field' => $vision->getPrimaryKey(),
                        'value' => '6083',
                    ]);
                    
                    try {
                        $validator->isValid('666');
                    } catch (Exception $e) {
                        /****************************************
                         * Create Table
                         ****************************************/
                        $sql = new Sql($this->adapter);
                        
                        $ddl = new CreateTable($vision->getTableName());
                        
                        foreach ($header as $var) {
                            $ddl->addColumn(new Varchar($var, 255, TRUE));
                        }
                        
                        $ddl->addConstraint(new PrimaryKey($vision->getPrimaryKey()));
                        
                        $this->adapter->query($sql->buildSqlString($ddl), $this->adapter::QUERY_MODE_EXECUTE);
                        unset($ddl);
                    }
                    
                    while (($record = fgetcsv($handle, NULL, ",")) !== FALSE) {
                        /****************************************
                         * Object Processing
                         ****************************************/
                        foreach ($record as $id => $val) {
                            $property = $header[$id];
                            $vision->$property = $val;
                        }
                        try {
                            $vision->create();
                        } catch (Exception $e) {
                            $this->flashmessenger()->addErrorMessage($e->getMessage());
                            break;
                        }
                        
                    }
                    fclose($handle);
                    unlink($data['FILE']['tmp_name']);
                }
            } else {
                $this->flashmessenger()->addErrorMessage("Form is Invalid.");
            }
        }
        
        $url = $this->getRequest()->getHeader('Referer')->getUri();
        return $this->redirect()->toUrl($url);
    }
}