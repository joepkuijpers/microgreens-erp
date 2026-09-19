<?php

function getRackCapacity(PDO $db): array
{
    $locations = $db->query("
        SELECT
            rl.id,
            rl.rack_code,
            rl.shelf_number,
            rl.tray_position,
            rl.tray_type,
            rl.is_active,
            ta.batch_id,
            gb.crop,
            gb.status AS batch_status
        FROM rack_locations rl
        LEFT JOIN tray_assignments ta
            ON ta.rack_location_id = rl.id
            AND ta.released_at IS NULL
            AND lower(COALESCE(ta.status, '')) = 'occupied'
        LEFT JOIN grow_batches gb
            ON gb.id = ta.batch_id
        WHERE rl.is_active = 1
        ORDER BY rl.rack_code, rl.shelf_number, rl.tray_position
    ")->fetchAll(PDO::FETCH_ASSOC);

    $racks = [];
    $total = 0;
    $occupied = 0;

    foreach ($locations as $location) {
        $rackCode = $location['rack_code'];

        if (!isset($racks[$rackCode])) {
            $racks[$rackCode] = [
                'rack_code' => $rackCode,
                'total_positions' => 0,
                'occupied_positions' => 0,
                'free_positions' => 0,
                'shelves' => [],
            ];
        }

        $isOccupied = !empty($location['batch_id']);

        $racks[$rackCode]['total_positions']++;
        $total++;

        if ($isOccupied) {
            $racks[$rackCode]['occupied_positions']++;
            $occupied++;
        }

        $shelf = (int)$location['shelf_number'];

        if (!isset($racks[$rackCode]['shelves'][$shelf])) {
            $racks[$rackCode]['shelves'][$shelf] = [];
        }

        $racks[$rackCode]['shelves'][$shelf][] = [
            'location_id' => (int)$location['id'],
            'position' => (int)$location['tray_position'],
            'tray_type' => $location['tray_type'],
            'occupied' => $isOccupied,
            'batch_id' => $location['batch_id'] ? (int)$location['batch_id'] : null,
            'crop' => $location['crop'],
            'batch_status' => $location['batch_status'],
        ];
    }

    foreach ($racks as &$rack) {
        $rack['free_positions'] = $rack['total_positions'] - $rack['occupied_positions'];
        ksort($rack['shelves']);
    }

    return [
        'generated_at' => date('Y-m-d H:i:s'),
        'summary' => [
            'total_positions' => $total,
            'occupied_positions' => $occupied,
            'free_positions' => $total - $occupied,
            'occupancy_percent' => $total > 0 ? round(($occupied / $total) * 100, 1) : 0,
        ],
        'racks' => array_values($racks),
    ];
}
