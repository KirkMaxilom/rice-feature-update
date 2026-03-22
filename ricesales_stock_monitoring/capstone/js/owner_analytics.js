// ../js/owner_analytics.js
(function () {
  "use strict";

  const DATA = window.OWNER_ANALYTICS_DATA || {};

  function safeChart(el, cfg) {
    try { return new Chart(el, cfg); } catch (e) { console.error(e); return null; }
  }

  function baseOptions(extra = {}) {
    return Object.assign(
      {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: true },
          tooltip: { mode: "index", intersect: false }
        },
        interaction: { mode: "index", intersect: false },
        scales: { y: { beginAtZero: true } }
      },
      extra
    );
  }

  document.addEventListener("DOMContentLoaded", function () {

    // Day of week chart (bar)
    const dowEl = document.getElementById("dowChart");
    if (dowEl) {
      safeChart(dowEl, {
        type: "bar",
        data: {
          labels: DATA.daySalesLabels || [],
          datasets: [{
            label: "Kg Sold",
            data: DATA.daySalesData || []
          }]
        },
        options: baseOptions({ plugins: { legend: { display: false } } })
      });
    }

    // Sales over time + Forecast (line)
    const salesEl = document.getElementById("salesChart");
    if (salesEl) {
      safeChart(salesEl, {
        type: "line",
        data: {
          labels: DATA.combinedLabels || [],
          datasets: [
            {
              label: "Actual (kg)",
              data: DATA.actualPadded || [],
              tension: 0.25,
              spanGaps: true
            },
            {
              label: "Forecast (kg)",
              data: DATA.forecastPadded || [],
              tension: 0.25,
              spanGaps: true,
              borderDash: [6, 6]
            }
          ]
        },
        options: baseOptions()
      });
    }

    // Auto popup (restock first, else aging)
    const ap = (DATA.autoPopup || "").toLowerCase();
    if (ap === "restock") {
      const el = document.getElementById("restockModal");
      if (el) new bootstrap.Modal(el).show();
    } else if (ap === "aging") {
      const el = document.getElementById("agingModal");
      if (el) new bootstrap.Modal(el).show();
    }
  });
})();