<?php
// ============================================================
//  dashboard/get_programs_api.php
//  Returns list of programs for a given exam type
//  GET: exam
//  Returns JSON
// ============================================================

$exam = $_GET['exam'] ?? 'CEE';

// Define programs (same as in the main page)
$allPrograms = [
    'btech_cse_aiml'      => ['name' => 'B.Tech CSE (AI & ML)',          'type' => 'btech'],
    'btech_cse_cyber'     => ['name' => 'B.Tech CSE (Cyber Security)',   'type' => 'btech'],
    'btech_ece_vlsi'      => ['name' => 'B.Tech ECE (VLSI)',             'type' => 'btech'],
    'btech_ece_comm'      => ['name' => 'B.Tech ECE (Communication)',    'type' => 'btech'],
    'btech_civil'         => ['name' => 'B.Tech Civil Engineering',      'type' => 'btech'],
    'lat_cse_aiml'        => ['name' => 'Lateral Entry CSE (AI & ML)',   'type' => 'other'],
    'lat_cse_cyber'       => ['name' => 'Lateral Entry CSE (Cyber)',     'type' => 'other'],
    'lat_civil'           => ['name' => 'Lateral Entry Civil Engg.',     'type' => 'other'],
    'int_btech_mech_cadcam'=> ['name' => 'Integrated B.Tech Mech (CAD/CAM)', 'type' => 'integrated'],
    'dip_elec_eng'        => ['name' => 'Diploma Electrical Engineering', 'type' => 'diploma'],
    'dip_elec_ev'         => ['name' => 'Diploma Electrical (EV)',       'type' => 'diploma'],
    'mtech_it_aiml'       => ['name' => 'M.Tech IT (AI & ML)',           'type' => 'other'],
    'mtech_ece_vlsi'      => ['name' => 'M.Tech ECE (VLSI)',             'type' => 'other'],
    'mtech_ece_wireless'  => ['name' => 'M.Tech ECE (Wireless)',        'type' => 'other'],
    'mtech_civil_const'   => ['name' => 'M.Tech Civil (Construction)',  'type' => 'other'],
    'pgdip_aiml'          => ['name' => 'PG Diploma in AI & ML',         'type' => 'other'],
    'pgdip_const_tech'    => ['name' => 'PG Diploma in Construction Tech','type' => 'other'],
    'fyimp_food_tech'     => ['name' => 'FYIMP Food Technology',         'type' => 'other'],
    'fyimp_travel_tour'   => ['name' => 'FYIMP Travel & Tourism',        'type' => 'other'],
    'mttm'                => ['name' => 'MTTM',                          'type' => 'other'],
    'mba'                 => ['name' => 'MBA',                           'type' => 'other'],
    'bba'                 => ['name' => 'BBA',                           'type' => 'other'],
];

$programs = [];
foreach ($allPrograms as $key => $info) {
    $show = false;
    if ($exam === 'CEE' || $exam === 'JEE') {
        if ($info['type'] === 'btech') $show = true;
    } elseif ($exam === 'ASUEE') {
        if ($info['type'] !== 'diploma' && $info['type'] !== 'integrated') $show = true;
    } elseif ($exam === 'Merit Based') {
        if ($info['type'] === 'diploma' || $info['type'] === 'integrated') $show = true;
    }
    if ($show) {
        $programs[] = ['key' => $key, 'name' => $info['name']];
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'programs' => $programs]);