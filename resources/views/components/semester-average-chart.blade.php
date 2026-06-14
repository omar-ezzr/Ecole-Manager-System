@php($hasData = count($chart['labels']) > 0 && count($chart['data']) > 0)

@if($hasData)
    <div class="space-y-3">
        <div class="flex items-baseline justify-between gap-3 px-1">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Grouped by {{ strtolower($chart['groupedBy']) }}</p>
            <p class="text-lg font-semibold text-zinc-950 dark:text-white">{{ number_format($chart['summary'], 2) }}/20</p>
        </div>
        <div class="h-56">
            <canvas id="{{ $canvasId }}"></canvas>
        </div>
    </div>

    @once
        <script src="{{ asset('js/chart.js') }}"></script>
    @endonce

    <script>
        (() => {
            const canvas = document.getElementById(@json($canvasId));

            if (!canvas || typeof Chart === 'undefined') {
                return;
            }

            const isDarkMode = document.documentElement.classList.contains('dark')
                || window.matchMedia('(prefers-color-scheme: dark)').matches;
            const textColor = isDarkMode ? '#e4e4e7' : '#3f3f46';
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.12)' : 'rgba(63, 63, 70, 0.12)';

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: @json($chart['labels']),
                    datasets: [{
                        label: @json('Semester '.$position.' average'),
                        data: @json($chart['data']),
                        backgroundColor: 'rgba(20, 184, 166, 0.72)',
                        borderColor: 'rgb(15, 118, 110)',
                        borderRadius: 5,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                            labels: {
                                color: textColor
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.dataset.label}: ${context.parsed.y}/20`
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: textColor
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: 20,
                            ticks: {
                                color: textColor
                            },
                            grid: {
                                color: gridColor
                            }
                        }
                    }
                }
            });
        })();
    </script>
@else
    <div class="flex h-56 items-center justify-center rounded-xl border border-dashed border-zinc-200 bg-zinc-50 px-4 text-center text-sm text-zinc-500 dark:border-white/10 dark:bg-zinc-950 dark:text-zinc-400">
        No semester grade data available yet.
    </div>
@endif
