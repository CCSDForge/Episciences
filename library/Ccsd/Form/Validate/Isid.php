<?php

class Ccsd_Form_Validate_Isid extends Zend_Validate_Regex {

    protected $_messageTemplates = array(
            self::INVALID   => "La valeur saisie ne peut pas être validée.",
            self::NOT_MATCH => "'%value%' n'est pas un identifiant valide.",
            self::ERROROUS  => "Une erreur interne s'est produite, veuillez recommencer.",
    );
    
    public function __construct($pattern = "/^.+$/")
    {
        parent::__construct($pattern);
    }
    
}