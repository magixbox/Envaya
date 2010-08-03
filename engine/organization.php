<?php

class Organization extends ElggUser
{
    protected function initialise_attributes()
    {
        parent::initialise_attributes();
        $this->attributes['subtype'] = T_organization;
    }

    static $subtype_id = T_organization;

    public function getFeedNames()
    {
        $feedNames = parent::getFeedNames();

        if ($this->region)
        {
            $feedNames[] = get_feed_name(array("region" => $this->region));
        }

        foreach ($this->getSectors() as $sector)
        {
            $feedNames[] = get_feed_name(array('sector' => $sector));

            if ($this->region)
            {
                $feedNames[] = get_feed_name(array('region' => $this->region, 'sector' => $sector));
            }
        }

        return $feedNames;

    }

    public function getRelatedFeedNames()
    {
        $feedNames = array();
        $sectors = $this->getSectors();

        foreach ($sectors as $sector)
        {
            $feedNames[] = get_feed_name(array('sector' => $sector));
        }

        /*
        if ($org->region)
        {
            $feedNames[] = get_feed_name(array('region' => $this->region));
        }
        */

        foreach ($this->getPartnerships($limit = 25) as $partnership)
        {
            $feedNames[] = get_feed_name(array('user' => $partnerhip->partner_guid));
        }

        return $feedNames;
    }

    public function canView()
    {
        return $this->approval > 0 || $this->canEdit();
    }

    public function canCommunicateWith()
    {
        return $this->canView() && isloggedin() && get_loggedin_userid() != $this->guid;
    }

    public function showCantViewMessage()
    {
        if ($this->approval == 0)
        {
            system_message(__('approval:waiting'));
        }
        else if ($this->approval < 0)
        {
            system_message(__('approval:rejected'));
        }
    }

    public function getAvailableThemes()
    {
        $themes = get_themes();
        if ($this->username == 'envaya')
        {
            $themes[] = 'sidebar';
        }        
        return $themes;
    }
    
    public function generateEmailCode()
    {
        $code = '';
        $characters = "0123456789abcdefghijklmnopqrstuvwxyz";
        for ($p = 0; $p < 8; $p++)
        {
            $code .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        $this->email_code = $code;
        $this->save();
    }

    public function getPostEmail()
    {
        if (!$this->email_code)
        {
            $this->generateEmailCode();
        }
        global $CONFIG;
        $postEmailParts = explode('@', $CONFIG->post_email, 2);
        return "{$postEmailParts[0]}+{$this->email_code}@{$postEmailParts[1]}";
    }

    public function getCountryText()
    {
        return __("country:{$this->country}");
    }

    public function getLocationText($includeRegion = true)
    {
        $res = '';

        if ($this->city)
        {
            $res .= "{$this->city}, ";
        }
        if ($this->region && $includeRegion)
        {
            $regionText = __($this->region);

            if ($regionText != $this->city)
            {
                $res .= "$regionText, ";
            }
        }
        $res .= $this->getCountryText();

        return $res;
    }

    protected $sectors;
    protected $sectors_dirty = false;

    static function getSectorOptions()
    {
        $sectors = array(
            1 => __('sector:agriculture'),
            2 => __('sector:communications'),
            3 => __('sector:conflict_res'),
            4 => __('sector:cooperative'),
            5 => __('sector:culture'),
            6 => __('sector:education'),
            7 => __('sector:environment'),
            8 => __('sector:health'),
            9 => __('sector:hiv_aids'),
            13 => __('sector:human_rights'),
            14 => __('sector:labor_rights'),
            15 => __('sector:microenterprise'),
            16 => __('sector:natural_resources'),
            17 => __('sector:prof_training'),
            18 => __('sector:rural_dev'),
            19 => __('sector:sci_tech'),
            20 => __('sector:substance_abuse'),
            21 => __('sector:tourism'),
            22 => __('sector:trade'),
            23 => __('sector:women'),
        );

        asort($sectors);

        $sectors[SECTOR_OTHER] = __('sector:other');

        return $sectors;
    }

    public function getSectors()
    {
        if (!isset($this->sectors))
        {
            $sectorRows = get_data("select * from org_sectors where container_guid = ?", array($this->guid));
            $sectors = array();
            foreach ($sectorRows as $row)
            {
                $sectors[] = $row->sector_id;
            }
            $this->sectors = $sectors;
        }
        return $this->sectors;
    }

    public function setSectors($arr)
    {
        $this->sectors = $arr;
        $this->sectors_dirty = true;
    }

    public function save()
    {
        if ($this->sectors_dirty)
        {
            delete_data("delete from org_sectors where container_guid = ?", array($this->guid));
            foreach ($this->sectors as $sector)
            {
                insert_data("insert into org_sectors (container_guid, sector_id) VALUES (?,?)", array($this->guid, $sector));
            }
            $this->sectors_dirty = false;
        }

        return parent::save();
    }

    public function getWidgetByName($name)
    {
        $where = array();
        $args = array();

        $where[] = "container_guid=?";
        $args[] = $this->guid;

        $where[] = "widget_name=?";
        $args[] = $name;

        $showHidden = access_get_show_hidden_status();
        access_show_hidden_entities(true);

        $widget = Widget::getByCondition($where, $args);

        $showHidden = access_show_hidden_entities($showHidden);

        if (!$widget)
        {
            $widget = new Widget();
            $widget->container_guid = $this->guid;
            $widget->widget_name = $name;
        }
        return $widget;
    }

    private function getSavedWidgets()
    {
        $where = array();
        $args = array();

        $where[] = "container_guid=?";
        $args[] = $this->guid;

        return Widget::filterByCondition($where, $args);
    }
    
    public function getAvailableWidgets()
    {        
        $savedWidgetsMap = array();
        $availableWidgets = array();
        
        foreach ($this->getSavedWidgets() as $widget)
        {
            $savedWidgetsMap[$widget->widget_name] = $widget;
            $availableWidgets[] = $widget;
        }        

        foreach (Widget::getDefaultNames() as $name)
        {
            if (!isset($savedWidgetsMap[$name]))
            {
                $widget = new Widget();
                $widget->container_guid = $this->guid;
                $widget->widget_name = $name;
                $availableWidgets[] = $widget;
            }            
        }        
        usort($availableWidgets, array('Widget', 'sort'));
        return $availableWidgets;
    }
    
    static function search($name, $sector, $region, $limit = 10, $offset = 0, $count = false)
    {
        $where = array();
        $args = array();
        if ($name)
        {
            $where[] = "(INSTR(u.username, ?) > 0 OR INSTR(u.name, ?) > 0)";
            $args[] = $name;
            $args[] = $name;
        }

        $join = '';
        if ($sector)
        {
            $join = "INNER JOIN org_sectors s ON s.container_guid = e.guid";
            $where[] = "s.sector_id=?";
            $args[] = $sector;
        }

        if ($region)
        {
            $where[] = "region=?";
            $args[] = $region;
        }

        return static::filterByCondition($where, $args, 'u.name', $limit, $offset, $count, $join);
    }

    static function listSearch($name, $sector, $region, $limit = 10, $pagination = true)
    {
        $offset = (int) get_input('offset');

        $count = static::search($name, $sector, $region, $limit, $offset, true);
        $entities = static::search($name, $sector, $region, $limit, $offset);

        return elgg_view_entity_list($entities, $count, $offset, $limit, false, false, $pagination);
    }

    static function filterByArea($latLongArr, $sector, $limit = 10, $offset = 0, $count = false)
    {
        $where = array();
        $args = array();

        $where[] = "latitude >= ?";
        $args[] = $latLongArr[0];

        $where[] = "latitude <= ?";
        $args[] = $latLongArr[2];

        $where[] = "longitude >= ?";
        $args[] = $latLongArr[1];

        $where[] = "longitude <= ?";
        $args[] = $latLongArr[3];

        $join = '';
        if ($sector)
        {
            $join = "INNER JOIN org_sectors s ON s.container_guid = e.guid";
            $where[] = "s.sector_id=?";
            $args[] = $sector;
        }

        return static::filterByCondition($where, $args, '', $limit, $offset, $count, $join);
    }

    function getPartnerships($limit = 10, $offset = 0, $count = false)
    {
        $where = array("container_guid = ? AND approval >= 3");
        $args = array($this->guid);

        return Partnership::filterByCondition($where, $args, '', $limit, $offset, $count);
    }
    
    function getPartnership($partnerOrg)
    {
        $partnership = Partnership::getByCondition(array("container_guid = ? AND partner_guid = ?"),
            array($this->guid, $partnerOrg->guid)
        );
        if (!$partnership)
        {
            $partnership = new Partnership();
            $partnership->container_guid = $this->guid;
            $partnership->partner_guid = $partnerOrg->guid;
        }
        return $partnership;
    }
}