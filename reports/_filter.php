<?php
declare(strict_types=1);
$period = $period ?? 'today';
$from = $from ?? '';
$to = $to ?? '';
?>
<div class="filter-bar">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Period</label>
            <select name="period" class="form-select" id="periodSelect">
                <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Today</option>
                <option value="yesterday" <?= $period === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>This Week</option>
                <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>This Month</option>
                <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom Range</option>
            </select>
        </div>
        <div class="col-md-3 custom-dates" style="<?= $period === 'custom' ? '' : 'display:none' ?>">
            <label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?= e($from) ?>">
        </div>
        <div class="col-md-3 custom-dates" style="<?= $period === 'custom' ? '' : 'display:none' ?>">
            <label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?= e($to) ?>">
        </div>
        <div class="col-md-3"><button class="btn btn-primary-custom w-100">Apply</button></div>
    </form>
</div>
<script>
document.getElementById('periodSelect')?.addEventListener('change', function() {
    document.querySelectorAll('.custom-dates').forEach(el => el.style.display = this.value === 'custom' ? '' : 'none');
});
</script>
