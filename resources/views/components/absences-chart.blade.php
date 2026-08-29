<div>
    <canvas id="absencesByClassroomChart" role="img" aria-label="Recorded absences by classroom chart"></canvas>
</div>

@once <script src="{{ asset('js/chart.js') }}"></script> @endonce
<script>
    const canvas = document.getElementById('absencesByClassroomChart');
    Chart.getChart(canvas)?.destroy();
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: {{ Illuminate\Support\Js::from($labels) }},
            datasets: [{
                label: 'Recorded absences',
                data: {{ Illuminate\Support\Js::from($absenceTotals) }},
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
