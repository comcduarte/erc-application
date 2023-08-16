<?php
namespace Application\Model;

use Components\Model\AbstractBaseModel;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Where;
use Laminas\Db\Sql\Predicate\Predicate;

class VisionPropertyModel extends AbstractBaseModel
{
    public $REM_MNC;
    public $REM_PID;
    public $REM_PIN;
    public $REM_OWN_NAME;
    public $REM_ACCT_NUM;
    public $REM_PRCL_LOCN;
    public $REM_PRCL_LOCN_STR_PFX;
    public $REM_PRCL_LOCN_STREET;
    public $REM_PRCL_LOCN_STR_SFX;
    public $REM_PRCL_LOCN_NUM;
    public $REM_PRCL_LOCN_NUM_CHAR;
    public $REM_PRCL_LOCN_CITY;
    public $REM_PRCL_LOCN_STT;
    public $REM_PRCL_LOCN_ZIP;
    public $REM_PRCL_LOCN_APT;
    public $REM_ALT_PRCL_ID;
    public $REM_PRCL_STATUS_DATE;
    public $REM_MBLU_MAP;
    public $REM_MBLU_MAP_CUT;
    public $REM_MBLU_BLOCK;
    public $REM_MBLU_BLOCK_CUT;
    public $REM_MBLU_LOT;
    public $REM_MBLU_LOT_CUT;
    public $REM_MBLU_UNIT;
    public $REM_MBLU_UNIT_CUT;
    public $REM_STATUS_DATE;
    public $REM_INTRNL_NOTE;
    public $REM_CARD_QUEUE;
    public $REM_GROWTH;
    public $REM_CHANGED_BY;
    public $REM_GIS_ID;
    public $REM_INET_SUPPRESS;
    public $REM_TEMP_PID;
    public $REM_IS_CONDO_MAIN;
    public $REM_CMPLX_NAME;
    public $REM_PROCESS;
    public $REM_STREET_IDX;
    public $REM_OWN_IDX;
    public $REM_ACCT_IDX;
    public $REM_KEY;
    public $REM_BLDG_NAME;
    public $REM_ASSOC_PARCEL_ID;
    public $REM_ASSOC_PCT;
    public $REM_CMPLX_NUM;
    public $REM_USE_CODE;
    public $REM_USE_TYPE;
    public $REM_LEGAL_AREA;
    public $REM_PRCL_ID;
    public $REM_INTRNL_SUPPRESS;
    public $REM_ENTITY;
    public $REM_IS_SKELETAL;
    public $REM_CROSS_STREET_1;
    public $REM_CROSS_STREET_2;
    public $REM_PRCL_LOCN_NUM_HIGH;
    public $REM_PRCL_LOCN_NUM_CHAR_HIGH;
    public $REM_BLDG_CLASS;
    public $REM_BLDG_CLASS_DESC;
    public $REM_ST_CODE;
    public $REM_IS_SUB_MAIN;
    public $REM_PARCEL_STATUS;
    public $REM_MBLU_MAP_DESC;
    public $REM_FIELD_REVIEW;
    public $REM_CREATE_STAMP;
    public $REM_USER_ID;
    public $REM_CREATE_DATE;
    public $REM_LAST_UPDATE;
    public $REM_TAX_ID;
    public $REM_SUBMNC;
    public $REM_ACCESS_DATE;
    public $REM_PRCL_LOCN_COUNTRY;
    public $REM_PRCL_LOCN_COUNTY;
    public $REM_PRCL_LOCN_POST_DIRECTION;
    public $REM_PRCL_LOCN_PRE_DIRECTION;
    public $REM_PRCL_LOCN_STREET_TYPE;
    public $REM_PRCL_LOCN_APT_TYPE;
    public $REM_USRFLD;
    public $REM_USRFLD_DESC;
    public $REM_PRCL_LOCN_ADDRESS_ID;
    
    
    public function __construct($adapter)
    {
        parent::__construct($adapter);
        array_push($this->private_attributes,
            'UUID',
            'STATUS',
            'DATE_CREATED',
            'DATE_MODIFIED');
        $this->public_attributes = array_diff(array_keys(get_object_vars($this)), $this->private_attributes);
        
        $this->setPrimaryKey('REM_PID');
        $this->setTableName('REALMAST');
    }
    
    public function fetchAll(Predicate $predicate = null, array $order = [])
    {
        if ($predicate == null) {
            $predicate = new Where();
        }
        
        $sql = new Sql($this->adapter);
        
        $select = $this->getSelect();
        $select->from($this->getTableName());
        $select->where($predicate);
        $select->order($order);
        
        $statement = $sql->prepareStatementForSqlObject($select);
        
        $resultSet = new ResultSet();
        try {
            $results = $statement->execute();
            $resultSet->initialize($results);
        } catch (\Exception $e) {
            return FALSE;
        }
        
        return $resultSet->toArray();
    }
}