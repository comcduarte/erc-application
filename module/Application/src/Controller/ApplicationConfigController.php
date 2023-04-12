<?php
namespace Application\Controller;

use Components\Controller\AbstractConfigController;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Ddl\CreateTable;
use Laminas\Db\Sql\Ddl\DropTable;
use Laminas\Db\Sql\Ddl\Column\Datetime;
use Laminas\Db\Sql\Ddl\Column\Integer;
use Laminas\Db\Sql\Ddl\Column\Text;
use Laminas\Db\Sql\Ddl\Column\Varchar;
use Laminas\Db\Sql\Ddl\Constraint\PrimaryKey;

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
    
}