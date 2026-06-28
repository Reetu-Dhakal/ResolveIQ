<?php
$labels = [];
$data = [];

while ($m = $monthly->fetch_assoc()) {
    $labels[] = $m['month'];
    $data[] = $m['total'];
}
?>

<canvas id="complaintChart"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctx = document.getElementById('complaintChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Complaints',
            data: <?= json_encode($data) ?>,
            backgroundColor: '#4338CA'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true
            }
        }
    }
});
</script>