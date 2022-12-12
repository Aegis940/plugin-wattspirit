<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class wattspirit extends eqLogic {
  /*     * *************************Attributs****************************** */

  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */
  public static $_widgetPossibility = array('custom' => true, 'custom::layout' => false);
  
  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */

  /*     * ***********************Methode static*************************** */

  public static function base64url_encode($data) {
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  } 

  public static function cleanCrons($eqLogicId) {
	$crons = cron::searchClassAndFunction(__CLASS__, 'pull', '"wattspirit_id":' . $eqLogicId);
	if (!empty($crons)) {
	  foreach ($crons as $cron) {
		$cron->remove(false);
	  }
	}
  }

  public static function pull($options) {
	  
    $eqLogic = self::byId($options['wattspirit_id']);
    if (!is_object($eqLogic)) {
      self::cleanCrons($options['wattspirit_id']);
      throw new Exception(__('Tâche supprimée car équipement non trouvé', __FILE__) . ' (ID) : ' . $options['wattspirit_id']);
    }
    $options = $eqLogic->cleanArray($options, 'wattspirit_id');
	
  
	$eqLogic->refreshData();
  }


  /*     * *********************Méthodes d'instance************************* */

  public function reschedule($lastMeasureDate,$options = array()) {
	  
	$diffMinutes = floor(abs(strtotime('now') - strtotime($lastMeasureDate))/60);
	$i = fmod($diffMinutes,10);
	//log::add(__CLASS__, 'debug', $this->getHumanName() . " diff = $diffMinutes i = $i");
    if ($i != 0)
		$diffMinutes = $diffMinutes + 10 - $i;	
	
    //log::add(__CLASS__, 'debug', $this->getHumanName() . " $lastMeasureDate + $diffMinutes minutes");
	$date_next_launch = date('Y-m-d H:i', strtotime($lastMeasureDate . ' +' . ($diffMinutes+1) . ' minutes'));	
	$next_launch = strtotime($date_next_launch);	
 	
    
    log::add(__CLASS__, 'info', $this->getHumanName() . ' ' . __('Prochaine programmation', __FILE__) . ' : ' . date('d/m/Y H:i', $next_launch));
    $options['wattspirit_id'] = intval($this->getId());
    self::cleanCrons($options['wattspirit_id']);
    $cron = (new cron)
      ->setClass(__CLASS__)
      ->setFunction('pull')
      ->setOption($options)
      ->setTimeout(30)
      ->setOnce(1);
    $cron->setSchedule(cron::convertDateToCron($next_launch));
    $cron->save();

  }

  public function getWattspiritData($start_date,$end_date) {

	$login = $this->getConfiguration('login');
    $password = $this->getConfiguration('password');
	$password = preg_replace('/\"/', '\\\"',$password, -1);
	$auth =  $login . ':' . $password;
	$auth = self::base64url_encode($auth);

	$ts_start = strtotime($start_date);
	$ts_stop = strtotime($end_date);
	
	log::add(__CLASS__, 'info', $this->getHumanName() . ' Récupération des données depuis le site de my.wattspirit.com');
   	log::add(__CLASS__, 'debug', $this->getHumanName() . " Auth = $auth");
    log::add(__CLASS__, 'info', $this->getHumanName() . " Start Period = $start_date => End Period = $end_date");
	log::add(__CLASS__, 'debug', $this->getHumanName() . " Start TS = $ts_start => End TS = $ts_stop");
	
	$api_url ="https://my.wattspirit.com/api/smk/p1m/$ts_start/$ts_stop";
	
    $header = array("Content-Type: application/x-www-form-urlencoded",
      "Authorization: Basic " . $auth);
    
	$curloptions = array(
      CURLOPT_URL => $api_url, CURLOPT_HTTPHEADER => $header, CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => false);
    $curl = curl_init();
    curl_setopt_array($curl, $curloptions);
    $response = curl_exec($curl);
    curl_getinfo($curl);
    $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);
    
	if ($response === false) {
      //log::add(__CLASS__,'error', "Failed curl_error: " .$curl_error);
      throw new Exception(__($curl_error,__FILE__));
    }
    else if (!empty(json_decode($response)->error)) {
      //log::add(__CLASS__,'error', "Error: AuthCode : $authorization_code Response $response");
      throw new Exception(__("Error: AuthCode : $authorization_code Response $response",__FILE__));
    }
    else { 
		//log::add(__CLASS__,'debug',__FUNCTION__ ."$response");
     }
    return ($response);
  }
 

  public function refreshData($force_histo = 0) {

      if ($this->getIsEnable() == 1) {

        log::add(__CLASS__, 'info', $this->getHumanName() . ' -----------------------------------------------------------------------');

        $cmd = $this->getCmd(null, 'power');
        $lastCollectDate = $cmd->getValueDate();    

        log::add(__CLASS__, 'debug', $this->getHumanName() . " lastCollectDate = $lastCollectDate");

        if (empty($lastCollectDate) || $force_histo == 1) {
          $start_date = date('2021-01-01 00:00');
          $end_date = date('Y-m-d H:i', strtotime('+1 minutes'));
        } else {
          $start_date = date('Y-m-d H:i', strtotime($lastCollectDate . ' +1 minutes'));
          $end_date = date('Y-m-d H:i', strtotime('+1 minutes'));
        }

        try {
            $data = $this->getWattspiritData($start_date,$end_date);
            $lastMeasureDate = $start_date;

            log::add(__CLASS__, 'info', $this->getHumanName() . ' Traitement des données reçues');

            $line = 0;
            $measures = json_decode($data,true); 
			
			if (count($measures) == 0) 
            	log::add(__CLASS__, 'warning', $this->getHumanName() . " Aucune donnée pour le créneau [$start_date , $end_date]");
			
            foreach ($measures as $record) {

                    $line = $line +1;
                    if (empty($record[0]) || empty($record[1])) {
                          log::add(__CLASS__, 'debug', $this->getHumanName() . ' Erreur de donnée ligne '. $line);
                    }	
                    else if (!is_numeric($record[0]) || !is_numeric($record[1])) {
                          log::add(__CLASS__, 'debug', $this->getHumanName() . ' Erreur donnée invalide ligne '. $line  . " : $record[0] -> $record[1]");
                      }
                    else {               
                      $time = substr($record[0],0, 10);
                      $lastMeasureDate = $dateVal = date('Y-m-d H:i', $time);
                      $value = $record[1];

                      //log::add(__CLASS__, 'debug', $this->getHumanName() . ' Donnée ok ligne ' . $count . " : $record[0] -> $record[1]");
                      //log::add(__CLASS__, 'debug', $this->getHumanName() . " $dateVal -> $value");

                      if ($line < count($measures))
                          $this->recordData($dateVal,$value,'event');
                      else
                          $this->recordData($dateVal,$value,'event');
                   }
            }

          }
          catch (exception $e) {
      			log::add(__CLASS__,'error', $this->getHumanName() . " Error: " .$e);
				$lastMeasureDate = $lastCollectDate;
	      }
        
          $this->reschedule($lastMeasureDate);
     } 
     
  }
  
  public function recordData($date, $value, $function = 'addHistoryValue' ) {
	  
	$cmd = $this->getCmd(null, 'power');
	$cmdId = $cmd->getId();
	  
	$cmdHistory = history::byCmdIdDatetime($cmdId, $date);
	if (is_object($cmdHistory) && $cmdHistory->getValue() == $value) {
		log::add(__CLASS__, 'debug', $this->getHumanName() . ' Mesure en historique - Aucune action : ' . ' Cmd = ' . $cmdId . ' Date = ' . $date . ' => Mesure = ' . $value);
	}
	else {
		log::add(__CLASS__, 'info', $this->getHumanName() . ' Enregistrement mesure manquante : ' . ' Cmd = ' . $cmdId . ' Date = ' . $date . ' => Mesure = ' . $value);

		if (is_object($cmdHistory)) {
			history::removes($cmdId, $date, $date);	
		}

      	if ($function === 'event')
			$cmd->event($value, $date);		
      	else
          	$cmd->addHistoryValue($value, $date);
	}
  }
 
  public function cleanArray($array, $logical) {
    if (count($array) > 1) {
      unset($array[$logical]);
    } else {
      $array = array();
    }
    return $array;
  }
 
  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
	$this->setDisplay('height','332px');
	$this->setDisplay('width', '192px');
	$this->setCategory('energy', 1);
	$this->setIsEnable(1);
	$this->setIsVisible(1);
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
	if (empty($this->getConfiguration('login'))) {
       throw new Exception(__('L\'identifiant du compte WattSpirit doit être renseigné',__FILE__));
    }
    
	if (empty($this->getConfiguration('password'))) {
      throw new Exception(__('Le mot de passe du compte WattSpirit doit être renseigné',__FILE__));
    }
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
	if ($this->getIsEnable() == 1) {
      $refreshCmd = $this->getCmd(null, 'refresh');
      if (!is_object($refreshCmd)) {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' ' . __('Création commande : refresh/Rafraîchir', __FILE__));
        $refreshCmd = (new wattspiritCmd)
          ->setLogicalId('refresh')
          ->setEqLogic_id($this->getId())
          ->setName(__('Rafraîchir', __FILE__))
          ->setType('action')
          ->setSubType('other')
          ->setOrder(0)
          ->save();
      }

      $powerCmd = $this->getCmd(null, 'power');
      if (!is_object($powerCmd)) {
        log::add(__CLASS__, 'debug', $this->getHumanName() . ' ' . __('Création commande : power/Puissance', __FILE__));
        $powerCmd = (new wattspiritCmd)
          ->setLogicalId('power')
          ->setEqLogic_id($this->getId())
          ->setName(__('Puissance', __FILE__))
		  ->setGeneric_type('POWER')
          ->setType('info')
          ->setSubType('numeric')
		  ->setDisplay('showStatsOndashboard', 1)
		  ->setDisplay('showStatsOnmobile', 1)
		  ->setIsHistorized(1)
		  ->setUnite('W')
		  ->setTemplate('dashboard','tile')
		  ->setTemplate('mobile','tile')
          ->setOrder(1);
		
        $powerCmd->setConfiguration("historizeMode","none");
		$powerCmd->save();
      }
	  
	  $this->refreshData();
	}
	else {
		self::cleanCrons(intval($this->getId()));
	}
	
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
	self::cleanCrons(intval($this->getId()));
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  */
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }


  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */


  /*     * **********************Getteur Setteur*************************** */

}

class wattspiritCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  // Exécution d'une commande
  public function execute($_options = array()) {
    if ($this->getLogicalId() == 'refresh') {
      $cmd = $this->getEqLogic()->getCmd(null, 'power');
	  $lastCollectDate = $cmd->getValueDate();
      $diffMinutes = floor(abs(strtotime('now') - strtotime($lastCollectDate))/60);
      if (empty($lastCollectDate) || $diffMinutes >= 20) {   	
      	return $this->getEqLogic()->refreshData();
      }
      
    }
  }
  /*     * **********************Getteur Setteur*************************** */

}