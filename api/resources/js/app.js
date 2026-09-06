import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
Alpine.plugin(collapse);

/* ---------------------------------------------------------------------------
   Benja Kikoba palette for charts
--------------------------------------------------------------------------- */
const K = {
  primary: '#115e59',
  secondary: '#2563eb',
  tertiary: '#16a34a',
  danger: '#dc2626',
  warning: '#d97706',
  purple: '#7c3aed',
  neutral: '#94a3b8',
};
const PALETTE = [K.primary, K.secondary, K.tertiary, K.warning, K.danger, K.purple, '#0d9488', K.neutral];

Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = '#94a3b8';
Chart.defaults.plugins.legend.display = false;

function compact(v) {
  const n = Math.abs(v);
  if (n >= 1e9) return (v / 1e9).toFixed(1) + 'B';
  if (n >= 1e6) return (v / 1e6).toFixed(1) + 'M';
  if (n >= 1e3) return Math.round(v / 1e3) + 'K';
  return String(Math.round(v));
}

/* Alpine directive: x-chart="{ type, data }" */
Alpine.directive('chart', (el, { expression }, { evaluate, cleanup }) => {
  const cfg = evaluate(expression);
  const type = cfg.type || 'line';
  let chart;

  const build = () => {
    const common = {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      scales: type === 'doughnut' ? {} : {
        x: { grid: { display: false }, border: { display: false } },
        y: {
          grid: { color: '#e2e8f0', dash: [4, 4] },
          border: { display: false },
          ticks: { callback: (v) => compact(v) },
          beginAtZero: true,
        },
      },
    };

    if (type === 'area' || type === 'line') {
      chart = new Chart(el, {
        type: 'line',
        data: {
          labels: cfg.labels,
          datasets: (cfg.series || []).map((s, i) => ({
            label: s.label,
            data: s.data,
            borderColor: s.color || PALETTE[i],
            backgroundColor: (s.color || PALETTE[i]) + '22',
            borderWidth: 2,
            fill: type === 'area',
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 4,
          })),
        },
        options: common,
      });
    } else if (type === 'bar') {
      chart = new Chart(el, {
        type: 'bar',
        data: {
          labels: cfg.labels,
          datasets: (cfg.series || []).map((s, i) => ({
            label: s.label,
            data: s.data,
            backgroundColor: s.color || PALETTE[i],
            borderRadius: 5,
            maxBarThickness: 26,
          })),
        },
        options: common,
      });
    } else if (type === 'doughnut') {
      chart = new Chart(el, {
        type: 'doughnut',
        data: {
          labels: cfg.labels,
          datasets: [{ data: cfg.data, backgroundColor: PALETTE, borderWidth: 2, borderColor: '#fff' }],
        },
        options: { ...common, cutout: '62%', plugins: { legend: { display: true, position: 'right', labels: { boxWidth: 8, boxHeight: 8, padding: 8 } } } },
      });
    }
  };

  build();
  cleanup(() => chart && chart.destroy());
});

/* toast helper — window.toast('message', 'error'?) */
window.toast = (message, tone = 'success') => {
  window.dispatchEvent(new CustomEvent('k-toast', { detail: { message, tone } }));
};

Alpine.start();
