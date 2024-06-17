<?php
$localopts = [
    'rvid=i' => 'RVID of a journal',
];


if (file_exists(__DIR__ . "/loadHeader.php")) {
    require_once __DIR__ . '/loadHeader.php';
} else {
    require_once 'loadHeader.php';
}
require_once "JournalScript.php";

class getDumpSQLPages extends JournalScript
{
    private const MENU_ITEM_LABEL = 'label';
    private const MENU_ITEM_PRIVILEGE = 'privilege';
    private const MENU_ITEM_PERMALIEN = 'permalien';
    private const MENU_ITEM_PAGES = 'pages';

    /**
     * @var bool
     */
    protected bool $_dryRun = true;

    /**
     * getDoi constructor.
     * @param $localopts
     */

    public function __construct($localopts)
    {

        // missing required parameters will be asked later
        $this->setRequiredParams([]);
        $this->setArgs(array_merge($this->getArgs(), $localopts));
        parent::__construct();

        if ($this->getParam('dry-run')) {
            $this->setDryRun(true);
        } else {
            $this->setDryRun(false);
        }
    }
    public function run(): void
    {
        $this->initApp(false);
        $this->initDb();
        $this->initTranslator();

        $rvidOpt = $this->getParam('rvid');
        if ($rvidOpt === null){
            $this->displayError('PUT RVID PLEASE', true);
            exit();
        }
        defineJournalConstants($rvidOpt);
        $strSQL = "";
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $titles = [];
        $pageVisibility = ['public'];
        $menu = new Episciences_Website_Navigation(["sid" => $rvidOpt]);
        $menu->load();
        $pagesFromMenu = $menu->toArray();
        foreach ($pagesFromMenu as $page) {
            $pageCode = $page['action'];
            $labelAndPrivilege = self::findLabelAndPrivilegeByPageCode($pagesFromMenu, $pageCode);
            if ($labelAndPrivilege[self::MENU_ITEM_LABEL] !== null) {
                foreach (Episciences_Tools::getLanguages() as $languageCode => $languageLabel) {
                    $translator = Zend_Registry::get('Zend_Translate');
                    $titles[$languageCode] = $translator->translate($labelAndPrivilege[self::MENU_ITEM_LABEL], $languageCode);
                }
            }
            if ($labelAndPrivilege[self::MENU_ITEM_PRIVILEGE] !== null) {
                $pageVisibility = [$labelAndPrivilege[self::MENU_ITEM_PRIVILEGE]];
            }

        }

        if ($labelAndPrivilege[self::MENU_ITEM_LABEL] !== null) {
            foreach (Episciences_Tools::getLanguages() as $languageCode => $languageLabel) {
                //$titles[$languageCode] = $this->view->translate($labelAndPrivilege[self::MENU_ITEM_LABEL], $languageCode);
            }
        }


        if ($labelAndPrivilege[self::MENU_ITEM_PRIVILEGE] !== null) {
            $pageVisibility = [$labelAndPrivilege[self::MENU_ITEM_PRIVILEGE]];
        }

        $pageToSave = new Episciences_Page();
//        $pageToSave->setContent($pageContent);
//        $pageToSave->setTitle($titles);
        $pageToSave->setCode(RVCODE);
//        $pageToSave->setUid(Episciences_Auth::getUid());
        $pageToSave->setPageCode($pageCode);
        $pageToSave->setVisibility($pageVisibility);

//        $select = $db->select()->from(["volume" => T_VOLUMES])
//            ->where('setting.value = ?','1');// prevent empty row
//        $myfile = fopen("/tmp/sqldumpSpecialIssue.sql", "wb+") or die("Unable to open file!");
//        foreach($db->fetchAll($select) as $value){
//            $selectVol =  $db->select()->from(["volume" => T_VOLUMES])->where('VID = ?',$value['VID']);
//            $strSQL .= "UPDATE VOLUME SET vol_type = 'special_issue' WHERE `VOLUME`.`VID` = " . $value['VID'].','."\n";
//        }
//        fwrite($myfile,$strSQL);
//        fclose($myfile);
    }


    /**
     * @return bool
     */
    public
    function isDryRun(): bool
    {
        return $this->_dryRun;
    }

    /**
     * @param bool $dryRun
     */
    public
    function setDryRun(bool $dryRun)
    {
        $this->_dryRun = $dryRun;
    }

    private static function findLabelAndPrivilegeByPageCode(array $menuItemArray, string $pageCode): array
    {
        $label = null;
        $privilege = null;

        // Define a recursive helper function
        $findLabelAndPrivilege = function ($arr) use (&$findLabelAndPrivilege, $pageCode, &$label, &$privilege) {
            foreach ($arr as $item) {
                if (isset($item[self::MENU_ITEM_PERMALIEN]) && $item[self::MENU_ITEM_PERMALIEN] === $pageCode) {
                    $label = $item[self::MENU_ITEM_LABEL];
                    $privilege = $item[self::MENU_ITEM_PRIVILEGE] ?? null;
                    return;
                } elseif (is_array($item) && array_key_exists(self::MENU_ITEM_PAGES, $item)) {
                    $findLabelAndPrivilege($item[self::MENU_ITEM_PAGES]);
                }
            }
        };

        // Call the recursive helper function
        $findLabelAndPrivilege($menuItemArray);

        return array(self::MENU_ITEM_LABEL => $label, self::MENU_ITEM_PRIVILEGE => $privilege);
    }
}
$script = new getDumpSQLPages($localopts);
$script->run();