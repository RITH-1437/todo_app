// Get chart canvas element
const ctx = document.getElementById('taskChart');
let taskChart = null;

const chartThemes = {
    dark: {
        legendColor: '#cbd5e1',
        borderColor: '#0f172a'
    },
    light: {
        legendColor: '#334155',
        borderColor: '#ffffff'
    }
};

function getCurrentChartTheme() {
    const pageBody = document.getElementById('body');
    return pageBody?.dataset.theme === 'light' ? 'light' : 'dark';
}

window.updateTaskChartTheme = function (theme) {
    if (!taskChart) {
        return;
    }

    const colors = chartThemes[theme] || chartThemes.dark;

    taskChart.data.datasets[0].borderColor = colors.borderColor;
    taskChart.options.plugins.legend.labels.color = colors.legendColor;
    taskChart.update();
};

window.updateTaskChartCounts = function (completed, pending, overdue) {
    if (!taskChart) {
        return;
    }

    taskChart.data.datasets[0].data = [
        Number.parseInt(completed, 10) || 0,
        Number.parseInt(pending, 10) || 0,
        Number.parseInt(overdue, 10) || 0
    ];
    taskChart.update();
};

if (ctx) {
    // Get task counts from the DOM
    const completedElement = document.querySelector('#completed-card [data-stat-value]');
    const pendingElement = document.querySelector('#pending-card [data-stat-value]');
    const overdueElement = document.querySelector('#overdue-card [data-stat-value]');

    const completedTasks = completedElement ? parseInt(completedElement.textContent) : 0;
    const pendingTasks = pendingElement ? parseInt(pendingElement.textContent) : 0;
    const overdueTasks = overdueElement ? parseInt(overdueElement.textContent) : 0;

    const initialTheme = getCurrentChartTheme();

    taskChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Pending', 'Overdue'],
            datasets: [{
                data: [
                    completedTasks,
                    pendingTasks,
                    overdueTasks
                ],
                backgroundColor: [
                    '#22c55e',
                    '#eab308',
                    '#ef4444'
                ],
                borderColor: chartThemes[initialTheme].borderColor,
                borderWidth: 4,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: chartThemes[initialTheme].legendColor,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 24,
                        font: {
                            size: 13,
                            weight: '500'
                        }
                    }
                }
            }
        }
    });
}
