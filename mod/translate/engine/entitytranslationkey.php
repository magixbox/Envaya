<?php

class EntityTranslationKey extends TranslationKey
{    
    function get_url()
    {
        return $this->get_language()->get_url()."/content/".urlencode_alpha($this->name);
    }
    
    protected function get_entity_property()
    {
        $name_parts = explode(':', $this->name);
        
        $entity = Entity::get_by_guid($name_parts[0]);
        if ($entity)
        {
            return array($entity, $name_parts[1]);
        }
        else
        {
            return null;
        }
    }
    
    function get_default_value()
    {
        $entity_prop = $this->get_entity_property();
        if ($entity_prop)
        {
            $prop = $entity_prop[1];
            return $entity_prop[0]->$prop;
        }
        else
        {
            return null;
        }        
    }
    
    private function call_entity_method($format, $args)
    {
        $entity_prop = $this->get_entity_property();
        if (!$entity_prop)
        {
            throw new CallException("get_entity_property");
        }
        
        $entity = $entity_prop[0];
        $property = $entity_prop[1];
        
        $method = sprintf($format, $property);        
        return call_user_func_array(array($entity,$method), $args);
    }       
    
    function get_behavior()
    {
        try
        {
            if ($this->call_entity_method("get_%s_mime_type", array()) == 'text/html')
            {
                return 'TranslationKeyBehavior_HTML';
            }
            else
            {
                return 'TranslationKeyBehavior_UserText';
            }
        }
        catch (CallException $ex)
        {
            return 'TranslationKeyBehavior_UserText';
        }
    }
    
    function get_current_base_lang()
    {
        return $this->get_default_value_lang();
    }
    
    function get_default_value_lang()
    {
        $entity = $this->get_container_entity();
        return $entity->language;        
    }
}