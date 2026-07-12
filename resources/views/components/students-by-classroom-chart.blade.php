<div>
    <canvas id="studentsByClassroomChart"></canvas>
</div>

@once <script src="{{ asset('js/chart.js') }}"></script> @endonce
<script>
    const canvas = document.getElementById('studentsByClassroomChart');
    Chart.getChart(canvas)?.destroy();
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: {{ Illuminate\Support\Js::from($labels) }},
            datasets: [{
                label: 'Students',
                data: {{ Illuminate\Support\Js::from($counts) }},
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
