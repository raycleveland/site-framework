<?php

define('SMARTY_SKIN_PLUGIN', true);

// put these function somewhere in your application
function skin_get_template($tpl_name, &$tpl_source, &$smarty)
{
    $skin_path = Control::getVar('skinPath');

    if(empty($skin_path)) {
        $tpl_source = '';
        return true;
    }

    $tpl_source = $smarty->fetch("file:{$skin_path}/{$tpl_name}");

    return true;
}

function skin_get_timestamp($tpl_name, &$tpl_timestamp, &$smarty)
{
    // do database call here to populate $tpl_timestamp
    // with unix epoch time value of last template modification.
    // This is used to determine if recompile is necessary.
    $tpl_timestamp = time(); // this example will always recompile!
    // return true on success, false to generate failure notification
    return true;
}

function skin_get_secure($tpl_name, &$smarty)
{
    // assume all templates are secure
    return true;
}

function skin_get_trusted($tpl_name, &$smarty)
{
    // not used for templates
}

// register the resource name "db"
$smarty->registerResource('skin', array('skin_get_template',
                                         'skin_get_timestamp',
                                         'skin_get_secure',
                                         'skin_get_trusted'));

?>

