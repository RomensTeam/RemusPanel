<?php

/**
 * RemusPanel - панель для отладки приложкения
 * 
 *
 * @author Romens
 */
class RemusPanel {
    
    private static $lang = false;
    private static $style = false;
    public static $_data = array(
        'constants' => array(),
        'files' => array(),
        'var_app' => array(),
        'query' => array(),
        'log' => array()
    );

    public function __construct(){
        if(lang('remuspanel_head') != null){
            self::$lang = true;
        }
        self::$style = _urlen(str_replace(DIR, getURL(),__DIR__._DS.'style'._DS));
    }
    
    private static function addStyles() {
        
        echo '<link href="'.self::$style.'bootstrap.min.css" rel="stylesheet" type="text/css">';
        echo '<link href="'.self::$style.'panel.css" rel="stylesheet" type="text/css">';
        echo '<script src="'.self::$style.'jquery.min.js" type="text/javascript"></script>';
        echo '<script src="'.self::$style.'bootstrap.min.js" type="text/javascript"></script>';
        echo "<script>
$('#remus_panel > div.panel_body > ul > li > a').click(function (e) {
  e.preventDefault()
  $(this).tab('show')
})</script>";
    }
    
    private static function name($key) {
        $data = lang('remuspanel_tabs_'.strtolower($key));
        if($data != NULL){
            return $data;
        } else { return $key;}
    }

    private static function render_tabs() {
        
        $data = '<ul class="nav nav-tabs">';
        
        foreach (self::$_data as $key => $value) {
            $data .= '<li><a href="#'.$key.'" data-toggle="tab" style="border-radius:0; border-top:none;">'.  self::name($key).'</a></li>';
        }
        
        $data .= '</ul>';
        
        return $data;
    }
    
    private static function render_area() {
        
        $data = '<div class="tab-content" style="max-height:400px;overflow-y:scroll;">';
        
        foreach (self::$_data as $key => $value) {
            if($key == 'log'){
                $data .= '<div class="tab-pane seduce" id="'.$key.'">'.$value.'</div>';
            } else {
                $data .= '<div class="tab-pane" id="'.$key.'">'.$value.'</div>';
            }
        }
        
        $data .= '</div>';
        
        return $data;
    }
	
    
    public static function types($mixed) {
        if(is_string($mixed)){
            
            $mixed = trim($mixed);
            
            if($mixed === ''){
                return '(empty string)';
            }
            
            
            return (string) '<code>'.htmlspecialchars($mixed).'</code>';
        }
        if(is_bool($mixed)){
            if($mixed){ return '<span class="label label-info">TRUE</span>';} 
            else { return '<span class="label label-danger">FALSE</span>';}
        }
        if(is_numeric($mixed)){
            return (string) '<span class="badge">'.$mixed.'</span>';
        }
        if(is_null($mixed)){
            return 'NULL';
        } 
        if(is_array($mixed)){
            return 'array['.count($mixed).']';
        }
        return 'ERROR';
    }

    public static function addData($name,$data) {
        if(isset(self::$_data[strtolower($name)])){
            self::$_data[strtolower($name)][] = $data;
        }
    }
    
    public static function log($message, $type = 'default') {
        $text = debug_backtrace();
        $text = $text;
        
        $data = str_replace(DIR,'',$text[0]['file']).': '.$text[0]['line'];
        
        if(isset($text[1]['class'],$text[1]['function'])){
            $call = $text[1]['class'].':'.$text[1]['function'].'()';
        } else {$call = null;}
        
        self::$_data['log'][] = array($message,$type,$data,$call);
    }
    
    private static function prepare_data() {
        self::constants_render();
        self::files_render();
        self::var_render();
        self::query_render();
        self::log_render();
    }


    public static function renderPanel() {
        self::addStyles();
        self::prepare_data();
        
        $head = 'RemusPanel';
        
        if(self::$lang){
            $head = lang('remuspanel_head');
        }
        
        echo '<div class="container navbar-fixed-bottom"><div class="panel panel-default" id="remus_panel" style="border: 1px solid rgb(221, 221, 221);box-shadow: 0 0 3px rgb(230, 230, 230); margin-bottom:0; border-radius:0;">
            <div class="panel_head panel-heading">
            <h3  class="panel-title" style="font-size:1.5em;">'.$head.' 
                <div class="btn-group-xs pull-right">
                <span class=" btn btn-default" onclick="$(\'#remus_panel .panel-body\').toggle();">_</span>
                <span class="btn btn-default" onclick="$(\'#remus_panel\').text(\'\')">X</span>
                </div>
            </h3>
            </div>
            <div class="panel_body panel-body" style="padding:0;">
         '.self::render_tabs().self::render_area().'
         </div>
         </div></div>';
    }
    
    private static function constants_render() {
        
        $settings  = require DIR_DEFAULT.'config.php';
        
        self::$_data['constants'] = '<table class="table table-condensed"><tbody>';
        
        $data = get_user_constants();
        
        foreach ($data as $key => $value) {
            
            if(isset($settings[$key])){
                if($settings[$key] === $value){
                    self::$_data['constants'] .= '<tr class="warning"><th><abbr title="Значение по умолчанию">'.$key.'</abbr></th><td>'.  self::types($value).'</td></tr>';
                } else {
                    self::$_data['constants'] .= '<tr class="success"><th><abbr title="Измененно">'.$key.'</abbr></th><td>'.  self::types($value).'</td></tr>';
                }
            } else {
                if(substr($key, 0,4) == 'DIR_'){
                    self::$_data['constants'] .= '<tr class="active"><th><abbr title="Являются директориями приложения">'.$key.'</abbr></th><td>'.  self::types($value).'</td></tr>';
                } else {
                    self::$_data['constants'] .= '<tr><th>'.$key.'</th><td>'.  self::types($value).'</td></tr>';
                }
            }
        }
        
        self::$_data['constants'] .= '</tbody></table>';
    }
    
    private static function files_render() {
        
        self::$_data['files'] = '<table class="table table-bordered"><tbody>';
        
        foreach (get_included_files() as $value) {
            self::$_data['files'] .= '<tr><td>'.str_replace(DIR, '<b>DIR:</b>', $value).'</td></tr>';
        }
        
        self::$_data['files'] .= '</tbody></table>';
    }
    
    private static function log_render() {
        $log = self::$_data['log'];
        self::$_data['log'] = '<table class="table table-condensed"><tbody>';
        
        foreach ($log as $value) {
            $value[2] = '['.$value[2].']';
            switch ($value[1]) {
                case 'error':
                    self::$_data['log'] .= '<tr class="danger">';
                    break;
                
                case 'warning':
                    self::$_data['log'] .= '<tr class="warning">';
                    break;
                
                case 'success':
                    self::$_data['log'] .= '<tr class="success">';
                    break;
                
                default:
                    self::$_data['log'] .= '<tr>';
                    break;
            }
            self::$_data['log'] .= '<th>'.$value[3].'</th><th>'.$value[2].'</th><td>'.$value[0].'</td></tr>';
            
        }
        self::$_data['log'] .= '</tbody></table>';
    }
    
    private static function var_render() {
        self::$_data['var_app'] = '<table class="table table-striped"><tbody>';
        
        foreach (Remus::Model()->var_app as $key => $value) {
            self::$_data['var_app'] .= '<tr><th>'.$key.'</th><td>'.  self::types($value).'</td></tr>';
        }
        
        self::$_data['var_app'] .= '</tbody></table>';
    }
    
    private static function query_render() {
        $query = self::$_data['query'];
        self::$_data['query']  = '<table class="table"><thead>';
        self::$_data['query'] .= '<tr><th>BackTrace</th><th>SQL</th><th>Result</th></tr></thead><tbody>';
        
        foreach ($query as $value) {
            $trace = '['.str_replace(DIR, 'DIR'._DS, $value['trace']['file']).':'.$value['trace']['line'].']';
            self::$_data['query'] .= '<tr><th>'.$trace.'</th><th><code>'.$value['sql'].'</code></th><td>'.$value['result'].'</td></tr>';
        }
        
        self::$_data['query'] .= '</tbody></table>';        unset($query);
    }
}