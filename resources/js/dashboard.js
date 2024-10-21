import "../css/dashboard.scss"
import Chart from 'chart.js/auto'
import { get } from './ajax'
import flatpickr from "flatpickr";

window.addEventListener('DOMContentLoaded', function () {

    const startDateInput = document.getElementById('startDate')
    const endDateInput = document.getElementById('endDate')
    const datesForm = document.getElementById('dates')

    function setUpFlatpickr(input, form) {
        flatpickr(input, {
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
            default: input.value,
            onChange: function (selectedDates, dateStr, instance) {
                form.submit()
            }
        })
    }

    setUpFlatpickr(startDateInput, datesForm)
    setUpFlatpickr(endDateInput, datesForm)

    const increaseYearBtn = document.getElementById('increaseYear')
    const decreaseYearBtn = document.getElementById('decreaseYear')
    const yearDisplay = document.getElementById('yearDisplay');
    const ctx = document.getElementById('yearToDateChart')

    let chartInstance = null; // Переменная для хранения экземпляра графика

    increaseYearBtn.addEventListener('click', event => {
        yearDisplay.innerText = String(+yearDisplay.innerText + 1);
        getChart(yearDisplay.innerText)
    })
    decreaseYearBtn.addEventListener('click', event => {
        yearDisplay.innerText = String(+yearDisplay.innerText - 1);
        getChart(yearDisplay.innerText)
    })

    getChart(yearDisplay.innerText)
    function getChart(year = null) {
        if (chartInstance) {
            chartInstance.destroy();
        }

        get(`/stats/ytd?year=${year}`).then(response => response.json()).then(response => {
            let expensesData = Array(12).fill(null)
            let incomeData = Array(12).fill(null)

            response.forEach(({ m, expense, income }) => {
                expensesData[m - 1] = expense
                incomeData[m - 1] = income
            })

            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [
                        {
                            label: 'Expense',
                            data: expensesData,
                            borderWidth: 1,
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                        },
                        {
                            label: 'Income',
                            data: incomeData,
                            borderWidth: 1,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                        }
                    ]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        })
    }
})