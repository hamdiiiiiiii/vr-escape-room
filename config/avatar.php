<?php

/*
 | The avatar catalogue: the ONLY choices a student can make.
 | Students never type free text or upload pictures (the players are children), so nothing here can
 | hold a real name, contact detail or inappropriate word. Unity reads the same list from
 | GET /api/avatar/options and builds the avatar from the ids, so web and game never disagree.
 |
 | Faces have no names on purpose (the page shows pictures only). To add an option later: add one entry here.
 | Never rename an id once students have saved it.
 */
return [
    'skin_tones' => [
        'tone_1' => '#f6d9c0',
        'tone_2' => '#ecc09a',
        'tone_3' => '#d19a66',
        'tone_4' => '#a86f3f',
        'tone_5' => '#7a4a28',
        'tone_6' => '#4e2f1b',
    ],

    'hair_colors' => [
        'black'    => '#17120e',
        'brown'    => '#4a2f1a',
        'chestnut' => '#7a4a22',
        'ginger'   => '#b5531f',
        'red'      => '#a3261b',
        'blonde'   => '#d2aa4f',
        'platinum' => '#d9d3c4',
        'grey'     => '#8a8d94',
        'blue'     => '#2f6bff',
        'pink'     => '#e0509a',
        'teal'     => '#1fb890',
    ],

    // Faces: a gender look (boy / girl) and a hair style. Boys and girls alternate so both show in the grid.
    'heads' => [
        ['id' => 'head_01', 'gender' => 'boy',  'style' => 'short'],
        ['id' => 'head_02', 'gender' => 'girl', 'style' => 'long'],
        ['id' => 'head_03', 'gender' => 'boy',  'style' => 'side'],
        ['id' => 'head_04', 'gender' => 'girl', 'style' => 'bob'],
        ['id' => 'head_05', 'gender' => 'boy',  'style' => 'fade'],
        ['id' => 'head_06', 'gender' => 'girl', 'style' => 'bangs'],
        ['id' => 'head_07', 'gender' => 'boy',  'style' => 'curly'],
        ['id' => 'head_08', 'gender' => 'girl', 'style' => 'ponytail'],
        ['id' => 'head_09', 'gender' => 'boy',  'style' => 'spiky'],
        ['id' => 'head_10', 'gender' => 'girl', 'style' => 'pigtails'],
        ['id' => 'head_11', 'gender' => 'boy',  'style' => 'buzz'],
        ['id' => 'head_12', 'gender' => 'girl', 'style' => 'bun'],
        ['id' => 'head_13', 'gender' => 'boy',  'style' => 'beard'],
        ['id' => 'head_14', 'gender' => 'girl', 'style' => 'braid'],
        ['id' => 'head_15', 'gender' => 'boy',  'style' => 'wavy'],
        ['id' => 'head_16', 'gender' => 'girl', 'style' => 'afro'],
    ],

    'outfits' => [
        ['id' => 'outfit_field',     'label' => 'Field Suit'],
        ['id' => 'outfit_labcoat',   'label' => 'Lab Coat'],
        ['id' => 'outfit_hazmat',    'label' => 'Hazard Gear'],
        ['id' => 'outfit_hoodie',    'label' => 'Hacker Hoodie'],
        ['id' => 'outfit_armor',     'label' => 'Tactical Vest'],
        ['id' => 'outfit_jumpsuit',  'label' => 'Jumpsuit'],
        ['id' => 'outfit_detective', 'label' => 'Detective Coat'],
        ['id' => 'outfit_explorer',  'label' => 'Explorer Vest'],
        ['id' => 'outfit_mechanic',  'label' => 'Mechanic Overalls'],
        ['id' => 'outfit_agent',     'label' => 'Agent Suit'],
    ],

    'headgear' => [
        ['id' => 'none',     'label' => 'None'],
        ['id' => 'cap',      'label' => 'Cap'],
        ['id' => 'headset',  'label' => 'Headset'],
        ['id' => 'visor',    'label' => 'Visor'],
        ['id' => 'goggles',  'label' => 'Goggles'],
        ['id' => 'helmet',   'label' => 'Helmet'],
        ['id' => 'fedora',   'label' => 'Detective Hat'],
        ['id' => 'headlamp', 'label' => 'Headlamp'],
        ['id' => 'bandana',  'label' => 'Bandana'],
        ['id' => 'beret',    'label' => 'Beret'],
    ],

    // worn on the body
    'gear' => [
        ['id' => 'none',     'label' => 'None'],
        ['id' => 'backpack', 'label' => 'Backpack'],
        ['id' => 'toolbelt', 'label' => 'Tool Belt'],
        ['id' => 'badge',    'label' => 'ID Badge'],
        ['id' => 'keycard',  'label' => 'Keycard'],
    ],

    // carried in the hand
    'held' => [
        ['id' => 'none',        'label' => 'Nothing'],
        ['id' => 'lantern',     'label' => 'Lantern'],
        ['id' => 'flashlight',  'label' => 'Flashlight'],
        ['id' => 'magnifier',   'label' => 'Magnifier'],
        ['id' => 'clipboard',   'label' => 'Test Plan'],
        ['id' => 'bugjar',      'label' => 'Bug Jar'],
        ['id' => 'radio',       'label' => 'Radio'],
    ],

    'suit_colors' => [
        'steel'  => '#5d6670',
        'night'  => '#262b34',
        'olive'  => '#59623a',
        'sand'   => '#a38a5c',
        'maroon' => '#7a2a2a',
        'navy'   => '#243a66',
        'teal'   => '#1f6a6a',
        'white'  => '#d8dce2',
    ],

    'team_colors' => [
        'blue'   => '#2f6bff',
        'amber'  => '#ff8a1f',
        'green'  => '#27b345',
        'purple' => '#7a4dff',
        'red'    => '#e23b3b',
    ],

    // Public player names are built from these words: first + optional second + optional number, e.g. Raven_77, CyberNova.
    'name_words' => [
        'Raven', 'Cyber', 'Nova', 'Pixel', 'Echo', 'Bolt', 'Ghost', 'Comet',
        'Rogue', 'Vector', 'Cipher', 'Orbit', 'Spark', 'Shadow', 'Turbo', 'Quantum',
        'Falcon', 'Matrix', 'Binary', 'Glitch', 'Neon', 'Zero', 'Alpha', 'Delta',
    ],

    'defaults' => [
        'head' => 'head_01',
        'skin_tone' => 'tone_2',
        'hair_color' => 'black',
        'outfit' => 'outfit_field',
        'headgear' => 'none',
        'gear' => 'none',
        'held' => 'none',
        'suit_color' => 'steel',
        'team_color' => 'amber',
    ],

    // A squad has between 1 player (solo) and max_players. Multiplayer needs at least min_for_multiplayer.
    'squad' => [
        'max_players' => 4,
        'min_for_multiplayer' => 2,
    ],
];
