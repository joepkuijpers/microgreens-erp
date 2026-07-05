<?php
require_once __DIR__ . '/../seed_planning_engine.php';

$seedPlanning = getSeedPlanning($db, 14);
$seedStatus = $seedPlanning['summary']['shortage_grams'] > 0 ? 'alarm' : 'ok';
?>

<div class="dashboard-section">
    <h2>🌱 <?= htmlspecialchars(t('seed_planning')) ?></h2>
</div>

<div class="live-sensor-card">
    <div class="live-sensor-header">
        <h3>🌱 <?= htmlspecialchars(t('seed_planning')) ?></h3>
        <span class="sensor-status-badge <?= $seedStatus ?>">
            <?= htmlspecialchars((string)$seedPlanning['summary']['available_seed_kg']) ?>
            kg <?= htmlspecialchars(t('available')) ?>
        </span>
    </div>

    <div class="live-sensor-item <?= $seedStatus ?>">
        <span><?= htmlspecialchars(t('seed_requirement_14_days')) ?></span>
        <small>
            <?= htmlspecialchars(t('required')) ?>:
            <?= htmlspecialchars((string)$seedPlanning['summary']['required_seed_kg']) ?> kg |

            <?= htmlspecialchars(t('shortage')) ?>:
            <?= htmlspecialchars((string)round($seedPlanning['summary']['shortage_grams'] / 1000, 3)) ?> kg |

            <?= htmlspecialchars(t('crops_to_plan')) ?>:
            <?= (int)$seedPlanning['summary']['crops_need_seed'] ?>
        </small>
    </div>

    <?php foreach ($seedPlanning['plans'] as $plan): ?>
        <div class="live-sensor-item <?= htmlspecialchars($plan['priority']) ?>">
            <span><?= htmlspecialchars($plan['crop_name']) ?></span>
            <small>
                <?= htmlspecialchars(t('active_planned')) ?>:
                <?= (int)$plan['active_planned_trays'] ?> <?= htmlspecialchars(t('trays')) ?> |

                <?= htmlspecialchars(t('recommended_new_trays')) ?>:
                <?= (int)$plan['recommended_new_trays'] ?><br>

                <?= htmlspecialchars(t('seed_rate')) ?>:
                <?= htmlspecialchars((string)$plan['seed_grams_per_tray']) ?> g/tray |

                <?= htmlspecialchars(t('required')) ?>:
                <?= htmlspecialchars((string)$plan['required_seed_grams']) ?> g<br>

                <?= htmlspecialchars(t('advice')) ?>:
                <?= htmlspecialchars($plan['advice']) ?>
            </small>
        </div>
    <?php endforeach; ?>
</div>