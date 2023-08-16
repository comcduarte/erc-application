<?php
namespace Application\Controller;

use Application\Model\VisionPropertyModel;
use Components\Controller\AbstractConfigController;
use Components\Form\UploadFileForm;
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
use Exception;

class ApplicationConfigController extends AbstractConfigController
{
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
        
        
        /******************************
         * Perform Functions
         ******************************/
        $importForm = new UploadFileForm('IMPORTVISION');
        $importForm->init();
        $importForm->addInputFilter();
        
        $view->setVariable('importForm', $importForm);
        
        
        
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