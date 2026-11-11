import './shared-sidebar';
import './accessibility-controls';
import './dashboard-actions';
import './dashboard-vendor';
import './bootstrap';
import '../css/app.css';
import '../css/signalgrid.css';
import '../css/ui.css';
import './signalgrid';

import './survey-workspace';
import './heatmap';
import './dashboard-analytics';

import './global-navigation';
import './operations';


// Page-scoped modules: load only where their required DOM/config exists.
document.addEventListener('DOMContentLoaded',()=>{
 if(window.adminConfig) import('./admin.js');
 if(window.validationConfig) import('./validation.js');
 if(window.reportConfig || window.reportsConfig) import('./reports.js');
 if(window.comparisonConfig || window.comparisonsConfig) import('./comparisons.js');
});
