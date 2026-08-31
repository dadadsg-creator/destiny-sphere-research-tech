<?
function sort_total_asc($a, $b) {
    if ($a['total'] == $b['total']) {
        return 0;
    }
    return ($a['total'] < $b['total']) ? -1 : 1;
}
function sort_totalplace_asc($a, $b) {
    if ($a['totalplace'] == $b['totalplace']) {
        return 0;
    }
    return ($a['totalplace'] < $b['totalplace']) ? -1 : 1;
}
function sort_totalplaceDelta_asc($a, $b) {
    if ($a['totalplaceDelta'] == $b['totalplaceDelta']) {
        return 0;
    }
    return ($a['totalplaceDelta'] < $b['totalplaceDelta']) ? -1 : 1;
}
function sort_totalDelta_asc($a, $b) {
    if ($a['totalDelta'] == $b['totalDelta']) {
        return 0;
    }
    return ($a['totalDelta'] < $b['totalDelta']) ? -1 : 1;
}

function sort_science_asc($a, $b) {
    if ($a['science'] == $b['science']) {
        return 0;
    }
    return ($a['science'] < $b['science']) ? -1 : 1;
}
function sort_scienceplace_asc($a, $b) {
    if ($a['scienceplace'] == $b['scienceplace']) {
        return 0;
    }
    return ($a['scienceplace'] < $b['scienceplace']) ? -1 : 1;
}
function sort_scienceplaceDelta_asc($a, $b) {
    if ($a['scienceplaceDelta'] == $b['scienceplaceDelta']) {
        return 0;
    }
    return ($a['scienceplaceDelta'] < $b['scienceplaceDelta']) ? -1 : 1;
}
function sort_scienceDelta_asc($a, $b) {
    if ($a['scienceDelta'] == $b['scienceDelta']) {
        return 0;
    }
    return ($a['scienceDelta'] < $b['scienceDelta']) ? -1 : 1;
}

function sort_arch_asc($a, $b) {
    if ($a['arch'] == $b['arch']) {
        return 0;
    }
    return ($a['arch'] < $b['arch']) ? -1 : 1;
}
function sort_archplace_asc($a, $b) {
    if ($a['archplace'] == $b['archplace']) {
        return 0;
    }
    return ($a['archplace'] < $b['archplace']) ? -1 : 1;
}
function sort_archplaceDelta_asc($a, $b) {
    if ($a['archplaceDelta'] == $b['archplaceDelta']) {
        return 0;
    }
    return ($a['archplaceDelta'] < $b['archplaceDelta']) ? -1 : 1;
}
function sort_archDelta_asc($a, $b) {
    if ($a['archDelta'] == $b['archDelta']) {
        return 0;
    }
    return ($a['archDelta'] < $b['archDelta']) ? -1 : 1;
}

function sort_build_asc($a, $b) {
    if ($a['build'] == $b['build']) {
        return 0;
    }
    return ($a['build'] < $b['build']) ? -1 : 1;
}
function sort_buildplace_asc($a, $b) {
    if ($a['buildplace'] == $b['buildplace']) {
        return 0;
    }
    return ($a['buildplace'] < $b['buildplace']) ? -1 : 1;
}
function sort_buildplaceDelta_asc($a, $b) {
    if ($a['buildplaceDelta'] == $b['buildplaceDelta']) {
        return 0;
    }
    return ($a['buildplaceDelta'] < $b['buildplaceDelta']) ? -1 : 1;
}
function sort_buildDelta_asc($a, $b) {
    if ($a['buildDelta'] == $b['buildDelta']) {
        return 0;
    }
    return ($a['buildDelta'] < $b['buildDelta']) ? -1 : 1;
}

function sort_battle_asc($a, $b) {
    if ($a['battle'] == $b['battle']) {
        return 0;
    }
    return ($a['battle'] < $b['battle']) ? -1 : 1;
}
function sort_battleplace_asc($a, $b) {
    if ($a['battleplace'] == $b['battleplace']) {
        return 0;
    }
    return ($a['battleplace'] < $b['battleplace']) ? -1 : 1;
}
function sort_battleplaceDelta_asc($a, $b) {
    if ($a['battleplaceDelta'] == $b['battleplaceDelta']) {
        return 0;
    }
    return ($a['battleplaceDelta'] < $b['battleplaceDelta']) ? -1 : 1;
}
function sort_battleDelta_asc($a, $b) {
    if ($a['battleDelta'] == $b['battleDelta']) {
        return 0;
    }
    return ($a['battleDelta'] < $b['battleDelta']) ? -1 : 1;
}

function sort_npc_asc($a, $b) {
    if ($a['npc'] == $b['npc']) {
        return 0;
    }
    return ($a['npc'] < $b['npc']) ? -1 : 1;
}
function sort_npcplace_asc($a, $b) {
    if ($a['npcplace'] == $b['npcplace']) {
        return 0;
    }
    return ($a['npcplace'] < $b['npcplace']) ? -1 : 1;
}
function sort_npcplaceDelta_asc($a, $b) {
    if ($a['npcplaceDelta'] == $b['npcplaceDelta']) {
        return 0;
    }
    return ($a['npcplaceDelta'] < $b['npcplaceDelta']) ? -1 : 1;
}
function sort_npcDelta_asc($a, $b) {
    if ($a['npcDelta'] == $b['npcDelta']) {
        return 0;
    }
    return ($a['npcDelta'] < $b['npcDelta']) ? -1 : 1;
}

function sort_total_desc($a, $b) {
    if ($a['total'] == $b['total']) {
        return 0;
    }
    return ($a['total'] > $b['total']) ? -1 : 1;
}
function sort_totalplace_desc($a, $b) {
    if ($a['totalplace'] == $b['totalplace']) {
        return 0;
    }
    return ($a['totalplace'] > $b['totalplace']) ? -1 : 1;
}
function sort_totalplaceDelta_desc($a, $b) {
    if ($a['totalplaceDelta'] == $b['totalplaceDelta']) {
        return 0;
    }
    return ($a['totalplaceDelta'] > $b['totalplaceDelta']) ? -1 : 1;
}
function sort_totalDelta_desc($a, $b) {
    if ($a['totalDelta'] == $b['totalDelta']) {
        return 0;
    }
    return ($a['totalDelta'] > $b['totalDelta']) ? -1 : 1;
}

function sort_science_desc($a, $b) {
    if ($a['science'] == $b['science']) {
        return 0;
    }
    return ($a['science'] > $b['science']) ? -1 : 1;
}
function sort_scienceplace_desc($a, $b) {
    if ($a['scienceplace'] == $b['scienceplace']) {
        return 0;
    }
    return ($a['scienceplace'] > $b['scienceplace']) ? -1 : 1;
}
function sort_scienceplaceDelta_desc($a, $b) {
    if ($a['scienceplaceDelta'] == $b['scienceplaceDelta']) {
        return 0;
    }
    return ($a['scienceplaceDelta'] > $b['scienceplaceDelta']) ? -1 : 1;
}
function sort_scienceDelta_desc($a, $b) {
    if ($a['scienceDelta'] == $b['scienceDelta']) {
        return 0;
    }
    return ($a['scienceDelta'] > $b['scienceDelta']) ? -1 : 1;
}

function sort_arch_desc($a, $b) {
    if ($a['arch'] == $b['arch']) {
        return 0;
    }
    return ($a['arch'] > $b['arch']) ? -1 : 1;
}
function sort_archplace_desc($a, $b) {
    if ($a['archplace'] == $b['archplace']) {
        return 0;
    }
    return ($a['archplace'] > $b['archplace']) ? -1 : 1;
}
function sort_archplaceDelta_desc($a, $b) {
    if ($a['archplaceDelta'] == $b['archplaceDelta']) {
        return 0;
    }
    return ($a['archplaceDelta'] > $b['archplaceDelta']) ? -1 : 1;
}
function sort_archDelta_desc($a, $b) {
    if ($a['archDelta'] == $b['archDelta']) {
        return 0;
    }
    return ($a['archDelta'] > $b['archDelta']) ? -1 : 1;
}

function sort_build_desc($a, $b) {
    if ($a['build'] == $b['build']) {
        return 0;
    }
    return ($a['build'] > $b['build']) ? -1 : 1;
}
function sort_buildplace_desc($a, $b) {
    if ($a['buildplace'] == $b['buildplace']) {
        return 0;
    }
    return ($a['buildplace'] > $b['buildplace']) ? -1 : 1;
}
function sort_buildplaceDelta_desc($a, $b) {
    if ($a['buildplaceDelta'] == $b['buildplaceDelta']) {
        return 0;
    }
    return ($a['buildplaceDelta'] > $b['buildplaceDelta']) ? -1 : 1;
}
function sort_buildDelta_desc($a, $b) {
    if ($a['buildDelta'] == $b['buildDelta']) {
        return 0;
    }
    return ($a['buildDelta'] > $b['buildDelta']) ? -1 : 1;
}

function sort_battle_desc($a, $b) {
    if ($a['battle'] == $b['battle']) {
        return 0;
    }
    return ($a['battle'] > $b['battle']) ? -1 : 1;
}
function sort_battleplace_desc($a, $b) {
    if ($a['battleplace'] == $b['battleplace']) {
        return 0;
    }
    return ($a['battleplace'] > $b['battleplace']) ? -1 : 1;
}
function sort_battleplaceDelta_desc($a, $b) {
    if ($a['battleplaceDelta'] == $b['battleplaceDelta']) {
        return 0;
    }
    return ($a['battleplaceDelta'] > $b['battleplaceDelta']) ? -1 : 1;
}
function sort_battleDelta_desc($a, $b) {
    if ($a['battleDelta'] == $b['battleDelta']) {
        return 0;
    }
    return ($a['battleDelta'] > $b['battleDelta']) ? -1 : 1;
}

function sort_npc_desc($a, $b) {
    if ($a['npc'] == $b['npc']) {
        return 0;
    }
    return ($a['npc'] > $b['npc']) ? -1 : 1;
}
function sort_npcplace_desc($a, $b) {
    if ($a['npcplace'] == $b['npcplace']) {
        return 0;
    }
    return ($a['npcplace'] > $b['npcplace']) ? -1 : 1;
}
function sort_npcplaceDelta_desc($a, $b) {
    if ($a['npcplaceDelta'] == $b['npcplaceDelta']) {
        return 0;
    }
    return ($a['npcplaceDelta'] > $b['npcplaceDelta']) ? -1 : 1;
}
function sort_npcDelta_desc($a, $b) {
    if ($a['npcDelta'] == $b['npcDelta']) {
        return 0;
    }
    return ($a['npcDelta'] > $b['npcDelta']) ? -1 : 1;
}
?>
