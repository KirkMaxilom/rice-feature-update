// ../js/admin_analytics.js
(function () {
  "use strict";

  function isSmallScreen() {
    return window.matchMedia && window.matchMedia("(max-width: 576px)").matches;
  }

  // Base chart options (re-used for all charts)
  function baseOptions() {
    const small = isSmallScreen();

    return {
      responsive: true,                 // Make chart responsive
      maintainAspectRatio: false,       // Let CSS wrapper control height
      plugins: {
        legend: { display: true }       // Show legend
      },
      scales: {
        x: {
          ticks: {
            autoSkip: true,
            maxTicksLimit: small ? 4 : 8 // prevent overcrowding on mobile
          }
        },
        y: {
          beginAtZero: true,
          ticks: {
            maxTicksLimit: small ? 4 : 6
          }
        }
      }
    };
  }

  // Render Sales + Forecast line chart
  function renderSalesChart(el, labels, actual, forecast) {
    if (!el) return;

    const small = isSmallScreen();

    new Chart(el, {
      type: "line",
      data: {
        labels: labels,
        datasets: [
          {
            label: "Actual",
            data: actual,
            tension: 0.4,
            fill: false,
            pointRadius: small ? 0 : 3,       // ✅ hide dots on mobile
            pointHoverRadius: small ? 2 : 5
          },
          {
            label: "Forecast (Hybrid)",
            data: forecast,
            borderDash: [6, 6],
            tension: 0.4,
            fill: false,
            pointRadius: small ? 0 : 3,
            pointHoverRadius: small ? 2 : 5
          }
        ]
      },
      options: baseOptions()
    });
  }

  // Render Day-of-Week bar chart
  function renderDowChart(el, labels, data) {
    if (!el) return;

    new Chart(el, {
      type: "bar",
      data: {
        labels: labels,
        datasets: [
          {
            label: "Total Sold (kg)",
            data: data
          }
        ]
      },
      options: baseOptions()
    });
  }

  // Run after page is fully loaded
  document.addEventListener("DOMContentLoaded", function () {
    const d = window.ADMIN_ANALYTICS_DATA;
    if (!d) return;

    renderSalesChart(
      document.getElementById("salesChart"),
      d.combinedLabels,
      d.actualPadded,
      d.forecastPadded
    );

    renderDowChart(
      document.getElementById("dowChart"),
      d.daySalesLabels,
      d.daySalesData
    );
  });
})();
