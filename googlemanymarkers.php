<?php

include 'classes/GoogleManyMarkersSettings.php';
include 'classes/GoogleManyMarkersData.php';

class GoogleManyMarkers extends Module
{
    public function __construct()
    {
            $this->name = 'googlemanymarkers';
            $this->tab = 'front_office_features';
            $this->version = '1.6.0';
            $this->author = 'Przemysław Wleklik | PodwysockiDESIGN';
            $this->need_instance = 0;

            $this->bootstrap = true;
            parent::__construct();

            $this->displayName = $this->l('Google Many Markers');
            $this->description = $this->l('Display google map with markers.');
            $this->controllers = array('settings','markers');
            $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {

        $parent_tab = new Tab();
        $parent_tab->name[$this->context->language->id] = $this->l('Google Many Markers');
        $parent_tab->class_name = 'AdminGoogleManyMarkers';
        $parent_tab->id_parent = 0; // Home tab
        $parent_tab->module = $this->name;
        $parent_tab->add();	
        $this->installDb();
        
        return parent::install() && 
                $this->registerHook('googlemanymarkers') &&
                $this->registerHook('header') &&
                $this->registerHook('footer')
                ;
         
    }

    public function uninstall() 
    {
        // Uninstall Tabs
        $tab = new Tab((int)Tab::getIdFromClassName('AdminGoogleManyMarkers'));
        $tab->delete();
        $this->uinstallDb();
        
        // Uninstall Module
        if (!parent::uninstall())
            return false;
        return true;
    }
    
    public function hookHeader()
    {
        $this->context->controller->css_files['https://fonts.googleapis.com/css?family=Lato:100,100i,300,300i,400,400i,700,700i,900,900i&subset=latin-ext'] = 'all';
        $this->context->controller->css_files['/modules/googlemanymarkers/lib/css/manymarkers.css'] = 'all';
    }
    
    public function hookDisplayFooter()
    {
        $api_key = new GoogleManyMarkersSettings(1);
        $this->context->controller->js_files[] = 'http://maps.googleapis.com/maps/api/js?key=' . $api_key->option_value;
        $this->context->controller->js_files[] = '/modules/googlemanymarkers/lib/js/manymarkers.js';
    }


    public function hookGoogleManyMarkers()
    {
        $markers = GoogleManyMarkers::getAllMarkers();
        $this->context->smarty->assign(array(
            'markers' => $markers
        ));
        return $this->display(__FILE__, 'views/templates/front/googlemanymarkers.tpl');
    }
    
    
    protected function installDb()
    {
        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'google_many_markers_settings` (
            `id_settings` int(10) unsigned NOT NULL auto_increment,
            `option_name` varchar(255) NOT NULL,
            `option_value` text NOT NULL,
            PRIMARY KEY  (`id_settings`)
          ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8');
        
        
        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'google_many_markers_data` (
            `id_marker` int(10) unsigned NOT NULL auto_increment,
            `marker_name` varchar(255) NOT NULL,
            `street` varchar(255) NOT NULL,
            `postcode` varchar(255) NOT NULL,
            `city` varchar(255) NOT NULL,
            `phone` varchar(255) NOT NULL,
            `latitude` varchar(255) NOT NULL,
            `longitude` varchar(255) NOT NULL,
            `url` varchar(255) NOT NULL,
            PRIMARY KEY  (`id_marker`)
          ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8');

        $this->insertBasicSettings();
        $this->insertFirstMarker();
    }
    
    protected function uinstallDb()
    {
       Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'google_many_markers_settings`');
       Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'google_many_markers_data`');
    }

    
    public static function getAllMarkers()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'google_many_markers_data';
        $items = DB::getInstance()->executeS($sql);
        
        foreach($items as $item) {
            $markerCollection[] = new GoogleManyMarkersData($item['id_marker']);
        }
        
        $markerCollection = !empty($markerCollection) ? $markerCollection : false;
        
        return $markerCollection;
    }
    
    public static function getAllSettings()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'google_many_markers_settings';
        $items = DB::getInstance()->executeS($sql);
        
        foreach($items as $item) {
            $settingsCollection[] = new GoogleManyMarkersSettings($item['id_settings']);
        }
        
        $settingsCollection = !empty($settingsCollection) ? $settingsCollection : false;
        
        return $settingsCollection;
    }
    
    protected function insertBasicSettings()
    {
        $settings = array(
           'google_api_key' => '',
           'main_zoom' => '11',
           'main_map_center' => '51.1398264,16.925233',
           'map_styles' => '[{"featureType":"all","elementType":"geometry.stroke","stylers":[{"color":"#ff0064"}]},{"featureType":"all","elementType":"labels.text.fill","stylers":[{"color":"#03304f"}]},{"featureType":"administrative","elementType":"labels.text.fill","stylers":[{"color":"#03304f"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#f2f2f2"}]},{"featureType":"landscape","elementType":"geometry.stroke","stylers":[{"color":"#ff0064"}]},{"featureType":"landscape","elementType":"labels.text.fill","stylers":[{"color":"#03304f"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"all","stylers":[{"saturation":-100},{"lightness":45}]},{"featureType":"road.highway","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"road.arterial","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#41b2ec"},{"visibility":"on"}]}]',
           'zoom_after_click' => '15',
           'marker_image' => _PS_BASE_URL_ . '/modules/googlemanymarkers/lib/img/marker.png',
        );
        
        foreach($settings as $single_setting_key => $single_setting_value) {
            $setting = new GoogleManyMarkersSettings();
            $setting->option_name = $single_setting_key;
            $setting->option_value = $single_setting_value;
            $setting->save();
        }
        
    }
    
    public function insertFirstMarker() 
    {
        $marker = new GoogleManyMarkersData();
        $marker->marker_name = 'PodwysockiDESIGN';
        $marker->street = 'Trójkątna 1';
        $marker->postcode = '54-414';
        $marker->city = 'Wrocław';
        $marker->phone = '+48 889 540 537';
        $marker->latitude = '51.1398341';
        $marker->longitude = '16.8558244';
        $marker->url = 'http://podwysockidesign.pl';
        $marker->save();
    }

        
}

