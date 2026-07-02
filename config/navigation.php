<?php
/**
 * navigation.php - Navigationsstruktur (siehe itdesign.md Abschnitt 10).
 * Jeder Eintrag: page/action (Routing-Ziel), label (Sprachkey), icon (Bootstrap-Icon-Klasse),
 * visible (Closure, ob der Eintrag für den aktuellen Benutzer sichtbar ist).
 */
return [
    [
        'page'    => 'dashboard',
        'label'   => 'nav.dashboard',
        'icon'    => 'bi-speedometer2',
        'visible' => fn() => true,
    ],
    [
        'page'    => 'persons',
        'label'   => 'nav.persons',
        'icon'    => 'bi-people',
        'visible' => fn() => true,
    ],
    [
        'page'    => 'admin',
        'label'   => 'nav.admin',
        'icon'    => 'bi-shield-lock',
        'visible' => fn() => AuthHelper::isAdmin(),
    ],
    [
        'page'    => 'profile',
        'label'   => 'nav.profile',
        'icon'    => 'bi-person-circle',
        'visible' => fn() => true,
    ],
];
