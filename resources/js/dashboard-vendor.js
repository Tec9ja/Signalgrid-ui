import Chart from 'chart.js/auto';
import { createIcons as lucideCreateIcons, icons } from 'lucide';

window.Chart = Chart;

const createIcons = (options = {}) => lucideCreateIcons({
    icons,
    ...options,
});

window.lucide = { createIcons, icons };

const renderIcons = () => createIcons();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderIcons, { once: true });
} else {
    renderIcons();
}
