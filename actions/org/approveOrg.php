<?php
    admin_gatekeeper();
	action_gatekeeper();
	
	$guid = (int)get_input('org_guid');
	$entity = get_entity($guid);
	
	if (($entity) && ($entity instanceof Organization))
	{
        $entity->approve();
        $entity->save();    
	    system_message(elgg_echo('org:approved'));	
	}
	else
    {
		register_error(elgg_echo('org:notapproved'));
    }    
		
	forward($entity->getUrl());
?>