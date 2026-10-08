<?php if(!empty($runningProcesses)): ?>
<div class="card" style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-bottom:30px;">
    <h2 style="margin-top:0; color:#2c3e50;">⏳ Lopende Processen</h2>
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:#f4f4f9; text-align:left;">
                <th style="padding:10px; border-bottom:2px solid #ddd;">Cycle</th>
                <th style="padding:10px; border-bottom:2px solid #ddd;">Product</th>
                <th style="padding:10px; border-bottom:2px solid #ddd;">Batch</th>
                <th style="padding:10px; border-bottom:2px solid #ddd;">Machine</th>
                <th style="padding:10px; border-bottom:2px solid #ddd;">Input</th>
                <th style="padding:10px; border-bottom:2px solid #ddd;">Gestart</th>
                <th style="padding:10px; border-bottom:2px solid #ddd;">Actie</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($runningProcesses as $rp): ?>
            <tr>
                <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($rp["cycle_code"]); ?></td>
                <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($rp["product_name"]); ?></td>
                <td style="padding:10px; border-bottom:1px solid #eee;">#<?php echo $rp["batch_id"]; ?></td>
                <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo htmlspecialchars($rp["machine_id"]); ?></td>
                <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo number_format($rp["input_weight_g"], 1); ?>g</td>
                <td style="padding:10px; border-bottom:1px solid #eee;"><?php echo $rp["started_at"]; ?></td>
                <td style="padding:10px; border-bottom:1px solid #eee; text-align:right;">
                    <form method="POST" style="display:inline-flex; gap:5px; align-items:center;">
                        <input type="hidden" name="action" value="complete_cycle">
                        <input type="hidden" name="process_id" value="<?php echo $rp["id"]; ?>">
                        <input type="number" step="0.01" name="final_weight" required placeholder="g" style="width:70px; padding:5px; border:1px solid #ddd; border-radius:3px;">
                        <input type="number" step="0.01" name="energy_kwh" placeholder="kWh" style="width:60px; padding:5px; border:1px solid #ddd; border-radius:3px;">
                        <button type="submit" style="background:#28a745; color:#fff; border:none; padding:5px 12px; border-radius:3px; cursor:pointer;">✓ Voltooien</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
