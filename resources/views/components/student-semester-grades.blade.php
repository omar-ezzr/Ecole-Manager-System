<div>
    <div class="container">
        <canvas id="studentSemesterGrades" role="img" aria-label="Student semester grades chart"></canvas>
    </div>

    <script>
        new Chart(document.getElementById('studentSemesterGrades'), {
            type: 'line',
            data: {
                labels: ['Semester 1', 'Semester 2'],
                datasets: [{
                    label: 'Grades',
                    data: @json($grades),
                    borderWidth: 1
                }]
            }
        });
    </script>
</div>
