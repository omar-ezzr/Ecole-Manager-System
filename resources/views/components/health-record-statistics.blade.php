<div>
    <canvas id="healthRecordStatistics"></canvas>
</div>

<script src="{{ asset('js/chart.js') }}"></script>
<script>
    new Chart(document.getElementById('healthRecordStatistics'), {
        type: 'bar',
        data: {
            labels: @json($types),
            datasets: [{
                label: 'Health records',
                data: @json($counts),
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
