<?php
// helpers.php

function formatDate($date, $with_time = false) {
    return $with_time 
        ? date('d.m.Y H:i', strtotime($date))
        : date('d.m.Y', strtotime($date));
}

function getDeviceName($user_agent) {
    $device = 'Bilinmeyen Cihaz';
    if (strpos($user_agent, 'Mobile') !== false) $device = 'Mobil';
    elseif (strpos($user_agent, 'Tablet') !== false) $device = 'Tablet';
    elseif (strpos($user_agent, 'Windows') !== false) $device = 'Windows Bilgisayar';
    elseif (strpos($user_agent, 'Mac') !== false) $device = 'Mac';
    elseif (strpos($user_agent, 'Linux') !== false) $device = 'Linux';
    return $device;
}

function getActivityTypeName($type) {
    $translations = [
        'view_dashboard' => 'Dashboard Görüntülendi',
        'login' => 'Giriş Yapıldı',
        'profile_update' => 'Profil Güncellendi'
    ];
    return $translations[$type] ?? $type;
}