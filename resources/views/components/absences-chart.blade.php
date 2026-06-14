<div>
    <canvas id="absencesByClassroomChart"></canvas>
</div>

<script src="{{ asset('js/chart.js') }}"></script>
<script>
    new Chart(document.getElementById('absencesByClassroomChart'), {
        type: 'line',
        data: {
            labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13'],
            datasets: [{
                label: 'Absence days',
                data: @json($absenceTotals),
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
