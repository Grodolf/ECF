/**
 * Sales statistics charts rendered with Plotly.
 *
 * Reads globalThis.ordersData and globalThis.revenuesData (arrays injected by the
 * server-side view) and renders two bar charts: total orders per menu and revenue
 * per menu. Charts are re-rendered whenever the body data-theme attribute changes
 * so colours stay in sync with the active theme.
 *
 * Expected DOM:
 *   - #chart-orders   — container for the orders-per-menu bar chart
 *   - #chart-revenues — container for the revenue-per-menu bar chart
 */
import Plotly from 'https://esm.sh/plotly.js-dist-min';

/**
 * Reads the current body text colour for use as Plotly font colour.
 *
 * @returns {{ text: string }} Object with the resolved text colour string.
 */
function getThemeColors() {
    const styles = getComputedStyle(document.body);
    return {
        text: styles.color,
    };
}

/**
 * Builds a shared Plotly layout object with transparent background and themed font.
 *
 * @param {string} yTitle Label for the y-axis.
 * @returns {object} Plotly layout configuration.
 */
function buildLayout(yTitle) {
    const { text } = getThemeColors();
    return {
        paper_bgcolor: 'transparent',
        plot_bgcolor:  'transparent',
        font:          { family: 'inherit', size: 13, color: text },
        margin:        { t: 50, r: 20, b: 100, l: 30 },
        xaxis:         { tickangle: -30 },
        yaxis:         { title: yTitle, rangemode: 'tozero' },
        hovermode:     false,
    };
}

const config = { responsive: true, displayModeBar: false };

/**
 * Renders a bar chart of total orders per menu into #chart-orders.
 *
 * @param {Array<{title: string, total_sales: number}>} data Aggregated orders data.
 */
function renderOrdersChart(data) {
    const el = document.getElementById('chart-orders');
    if (!el || !data?.length) return;

    const trace = {
        type:        'bar',
        x:           data.map(d => d.title.replace('Menu ', '').replace(' ', '<br>')),
        y:           data.map(d => d.total_sales),
        text:        data.map(d => d.total_sales),
        textposition:'auto',
        marker:      { color: '#6b1f3f' },
    };

    Plotly.newPlot(el, [trace], buildLayout('Nombre de ventes'), config);
}

/**
 * Renders a bar chart of total revenue per menu into #chart-revenues.
 *
 * @param {Array<{title: string, total_price: number}>} data Aggregated revenue data.
 */
function renderRevenuesChart(data) {
    const el = document.getElementById('chart-revenues');
    if (!el || !data?.length) return;

    const trace = {
        type:        'bar',
        x:           data.map(d => d.title.replace('Menu ', '').replace(' ', '<br>')),
        y:           data.map(d => d.total_price),
        text:        data.map(d => `${d.total_price.toFixed(2)} €`),
        textfont:    { color: '#1a1a1a' },
        textposition:'auto',
        marker:      { color: '#d4af37' },
    };

    Plotly.newPlot(el, [trace], buildLayout('Chiffre d\'affaires (€)'), config);
}

const observer = new MutationObserver(() => {
    setTimeout(() => {
        renderOrdersChart(globalThis.ordersData);
        renderRevenuesChart(globalThis.revenuesData);
    }, 400);
});

observer.observe(document.body, { 
    attributes: true, 
    attributeFilter: ['data-theme'] 
});

renderOrdersChart(globalThis.ordersData);
renderRevenuesChart(globalThis.revenuesData);
