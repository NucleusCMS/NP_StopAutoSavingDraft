<?php

class NP_StopAutoSavingDraft extends NucleusPlugin {
  function getName() { return PLUG_STOPAUTOSAVINGDRAFT_TEXT_PLUGINNAME; }
  function getAuthor() { return 'NKJG'; }
  function getVersion() { return '0.3'; }
  function getDescription() { return PLUG_STOPAUTOSAVINGDRAFT_TEXT_DESCIRPTION; }
  function getURL() { return 'http://niku.suku.name/'; }
  function getMinNucleusVersion() { return 330; }
  function supportsFeature($what) { return (int)($what=='SqlTablePrefix'); }

  function init() {
    $this->incLangFile();
  }
  
  function incLangFile() {
    // include language file for this plugin
    $language = preg_replace( '![\\\\|/]!', '', getLanguageName());
    if (file_exists($this->getDirectory().$language.'.php')) {
      include_once($this->getDirectory().$language.'.php');
    } elseif (file_exists($this->getDirectory().'english.php')) {
      include_once($this->getDirectory().'english.php');
    }
  }
  
  function getEventList() {
    return array(
      'AddItemFormExtras', 'EditItemFormExtras',
      'PrePluginOptionsEdit',
      );
  }
  
  function install() {
    $this->createOption(
      'active',
      PLUG_STOPAUTOSAVINGDRAFT_TEXT_OPTION_ACTIVE,
      'select',
      'yes',
      PLUG_STOPAUTOSAVINGDRAFT_TEXT_OPTION_ACTIVE_YES . '|yes|' .
      PLUG_STOPAUTOSAVINGDRAFT_TEXT_OPTION_ACTIVE_NO . '|no'
      );
    $this->createOption(
      'memberSetting',
      PLUG_STOPAUTOSAVINGDRAFT_TEXT_OPTION_MEMBERSETTING,
      'yesno',
      'no'
      );
    
    $this->createMemberOption(
      'mem_active',
      PLUG_STOPAUTOSAVINGDRAFT_TEXT_OPTION_ACTIVE,
      'select',
      'global',
      PLUG_STOPAUTOSAVINGDRAFT_TEXT_OPTION_ACTIVE_YES . '|yes|' .
      PLUG_STOPAUTOSAVINGDRAFT_TEXT_OPTION_ACTIVE_NO . '|no|' .
      PLUG_STOPAUTOSAVINGDRAFT_TEXT_OPTION_ACTIVE_GLOBAL . '|global'
      );
  }

  function toPrintScript() {
    global $member;
    $memOptionValue = $this->getMemberOption($member->getID(), 'mem_active');
    if ($memOptionValue === 'global') {
      $memOptionValue = $this->getOption('active');
    }
    return ($memOptionValue === 'yes');
  }

  function event_AddItemFormExtras($data) {
    if ($this->toPrintScript()) {
      echo $this->wrapJavascriptCode($this->getJavascriptCode());
    }
  }
  
  function event_EditItemFormExtras($data) {
    if ($this->toPrintScript()) {
      echo $this->wrapJavascriptCode($this->getJavascriptCode());
    }
  }
  
  function getJavascriptCode() {
    $jsCode = "\n";
    $jsCode .= '  (function() {' . "\n";
    $jsCode .= '    var elemInfo = document.getElementById("info");' . "\n";
    $jsCode .= '    elemInfo.parentNode.parentNode.style.display = "none";' . "\n";
    $jsCode .= '    doMonitor = function() {' . "\n";
    $jsCode .= '      if (checks * (now() - seconds) > 120 * 1000 * 50) {' . "\n";
    $jsCode .= '        checks = 0;' . "\n";
    $jsCode .= '        var ticket = addform.ticket.value;' . "\n";
    $jsCode .= '        var querystring = "action=updateticket&ticket=" + ticket;' . "\n";
    $jsCode .= '        xmlhttprequest[1].open("POST", goalurl, true);' . "\n";
    $jsCode .= '        xmlhttprequest[1].onreadystatechange = updateTicket;' . "\n";
    $jsCode .= '        xmlhttprequest[1].setRequestHeader("Content-Type", "application/x-www-form-urlencoded");' . "\n";
    $jsCode .= '        xmlhttprequest[1].send(querystring);' . "\n";
    $jsCode .= '      } else {' . "\n";
    $jsCode .= '        checks++;' . "\n";
    $jsCode .= '      }' . "\n";
    $jsCode .= '    };' . "\n";
    $jsCode .= '    var originalCheckMonitor = checkMonitor;' . "\n";
    $jsCode .= '    checkMonitor = function() {' . "\n";
    $jsCode .= '      originalCheckMonitor();' . "\n";
    $jsCode .= '      var elemInfo = document.getElementById("info");' . "\n";
    $jsCode .= '      elemInfo.parentNode.parentNode.style.display = "";' . "\n";
    $jsCode .= '    };' . "\n";
    $jsCode .= '  })();' . "\n";
    return $jsCode;
  }

  function wrapJavascriptCode($jsCode) {
    $output = "\n";
    $output .= '<script type="text/javascript">/*<![CDATA[*/' . "\n";
    $output .= $jsCode . "\n";
    $output .= '/*]]>*/</script>' . "\n";
    return $output;
  }

  function event_PrePluginOptionsEdit($data) {
    if ($data['context'] != 'member') {
      return;
    }
    $pid = $this->getID();
    if ($this->getOption('memberSetting') != 'no') {
      for ($i = 0, $length = sizeof($data['options']); $i < $length; $i++) {
        $aOption =& $data['options'][$i];
        if (
          $aOption['pid'] != $pid ||
          $aOption['type'] !== 'select' ||
          strpos($aOption['name'], 'mem_') !== 0
          ) {
          continue;
        }
        $globalOptionName = substr($aOption['name'], 4);
        $globalOptionValue = $this->getOption($globalOptionName);
        $constantName = (
          'PLUG_STOPAUTOSAVINGDRAFT_TEXT_OPTION_' . strtoupper($globalOptionName) . '_' .
          strtoupper($globalOptionValue)
          );
        $globalOptionDesc = constant($constantName);
        $optionAppendix = sprintf(PLUG_STOPAUTOSAVINGDRAFT_TEXT_FORMAT_DESC, $globalOptionDesc);
        $aOption['typeinfo'] = str_replace('|global', $optionAppendix . '|global', $aOption['typeinfo']);
      }
    } else {
      $aNewOptions = array();
      for ($i = 0, $length = sizeof($data['options']); $i < $length; $i++) {
        if ($data['options'][$i]['pid'] != $pid) {
          $aNewOptions[] = $data['options'][$i];
        }
      }
      $data['options'] = $aNewOptions;
    }
  }
}

?>