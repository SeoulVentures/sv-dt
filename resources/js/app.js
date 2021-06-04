require('./bootstrap');

import { createApp } from 'vue';
import App from './vue/App.vue';

// import { Grid } from '@toast-ui/vue-grid';
import SvDataTable from './vue/SvDataTable.vue';

const app = createApp(App);

// app.component('grid', Grid);
app.component('sv-data-table', SvDataTable);

app.mount('#app');
