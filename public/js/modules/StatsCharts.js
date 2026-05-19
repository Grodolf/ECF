import Plotly from 'https://esm.sh/plotly.js-dist-min';

function getThemeColors() {
    const styles = getComputedStyle(document.body);
    return {
        text: styles.color,
    };
}

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
