// require('./bootstrap');
import './bootstrap';

import { createApp } from 'vue';
import App from './vue/App.vue';

// import { Grid } from '@toast-ui/vue-grid';
// import SvDataTable from '../../packages/sv-dt-vue/src/SvDataTable.vue';
import SvDataTable from '@seoulventures/sv-dt';

const app = createApp(App);

// app.component('grid', Grid);
app.component('sv-data-table', SvDataTable);

app.mount('#app');
