<template>
    <div ref="grid"></div>
</template>

<script lang="ts">
import { computed, defineComponent, PropType, reactive, Ref, toRefs, unref, watch, ref, onMounted } from 'vue';
import Grid from 'tui-grid';

export default defineComponent({
    props: {
        queryId: {
            type: Number
        }
    },
    setup(props) {
        const state = reactive({
            headers: [],
            gridInstance: null,
            filters: {}
        });
        const grid = ref(null);

        onMounted(() => {
            state.gridInstance = new Grid({
                el: grid.value,
                scrollX: false,
                scrollY: false,
                minBodyHeight: 30,
                pageOptions: {
                    perPage: 50
                },
                columns: state.headers,
                useClientSort: false,
                data: {
                    api: {
                        readData: { url: '/api/table/data', method: 'GET' }
                    },
                    serializer(params) {
                        params = Object.assign(params, {
                            filters: JSON.stringify(state.filters),
                            queryId: props.queryId
                        });
                        return Object.keys(params).map(e => `${encodeURIComponent(e)}=${encodeURIComponent((params[e] === null || params[e] === undefined) ? '' : params[e])}`).join('&');
                    }
                }
            });
            Grid.applyTheme('striped');

            state.gridInstance.on('onGridUpdated', ev => {
                for(const [ key, value ] of Object.entries(state.filters)) {
                    state.gridInstance.filter(key, [ value ]);
                }
                return ev;
            });

            state.gridInstance.on('filter', ev => {
                const filters = ev.filterState.map(e => {
                    const { columnName, state } = e;
                    return {
                        name: columnName,
                        code: `${state[0].code}`,
                        value: `${state[0].value}`
                    };
                }).reduce((o, v) => (o[v.name] = { value: v.value, code: v.code }, o), {});
                if(JSON.stringify(state.filters) === JSON.stringify(filters)) return;
                state.filters = filters;

                state.gridInstance.resetData([]);
                state.gridInstance.readData();
                document.querySelectorAll('.tui-grid-filter-btn-clear').forEach(e => e.addEventListener('click', () => {
                    state.filters = {};
                    state.gridInstance.dispatch('resetFilterState');
                    state.gridInstance.readData();
                }));
                document.querySelectorAll('.tui-grid-filter-btn-apply').forEach(e => e.addEventListener('click', () => {
                    if(!e.parentElement.parentElement.querySelector('input').value.length) {
                        e.parentElement.querySelector('.tui-grid-filter-btn-clear').dispatchEvent(new Event('click'));
                    }
                }));
            });
        });

        watch(computed(() => props.queryId), async () => {
            const res = await fetch(`/api/table/headers?queryId=${props.queryId}`);
            state.headers = await res.json();
            if(state.gridInstance) state.gridInstance.setColumns(state.headers);
        }, { immediate: true });

        return {
            ...toRefs(state),
            grid
        }
    }
});
</script>

<style lang="scss">
// @import '../../../node_modules/tui-grid/dist/tui-grid.css';
@import 'tui-grid/dist/tui-grid.min.css';
@import 'tui-pagination/dist/tui-pagination.min.css';
</style>
