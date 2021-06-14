<template>
    <div ref="grid"></div>
</template>

<script lang="ts">
import { computed, defineComponent, reactive, toRefs, watch, ref, onMounted, ComponentPublicInstance } from 'vue';
import Grid from 'tui-grid';
import { NumberFilterCode, TextFilterCode } from 'tui-grid/types/store/filterLayerState';
import { OptColumn } from 'tui-grid/types/options';

export default defineComponent({
    props: {
        queryId: {
            type: Number,
            required: true,
            default: 0
        },
        scrollX: {
            type: Boolean,
            default: false
        },
        scrollY: {
            type: Boolean,
            default: false
        },
        perPage: {
            type: Number,
            default: 50
        }
    },
    setup(props) {
        const state = reactive({
            headers: [] as OptColumn[],
            gridInstance: undefined as Grid | undefined,
            filters: {} as { [key: string]: { value: string; code: NumberFilterCode | TextFilterCode; } }
        });
        const grid = ref<ComponentPublicInstance<HTMLDivElement>>();

        onMounted(() => {
            state.gridInstance = new Grid({
                el: grid.value!,
                scrollX: props.scrollX,
                scrollY: props.scrollY,
                minBodyHeight: 30,
                pageOptions: {
                    perPage: props.perPage
                },
                copyOptions: {
                    customValue: value => {
                        const e = document.createElement('div');
                        e.innerHTML = typeof value === 'string' ? value : value?.toString() || '';
                        return e.childNodes[0]?.nodeValue || '';
                    }
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
        });

        watch(computed(() => props.queryId), async () => {
            const res = await fetch(`/api/table/headers?queryId=${props.queryId}`);
            state.headers = await res.json();
            if(state.gridInstance) state.gridInstance.setColumns(state.headers);
        }, { immediate: true });

        watch(computed(() => state.gridInstance), async () => {
            if(!state.gridInstance) return;
            state.gridInstance.on('onGridUpdated', (_ev) => {
                for(const [ key, value ] of Object.entries(state.filters)) {
                    state.gridInstance!.filter(key, [ value ]);
                }
            });

            state.gridInstance.on('beforeSort', (_ev, { columns } = state.gridInstance!.store.data.sortState) => columns.length && columns.shift()); // Issue #1379

            state.gridInstance.on('filter', (ev) => {
                const filters = ev.filterState?.map(e => {
                    const { columnName, state } = e;
                    return {
                        name: columnName,
                        code: `${state[0].code}` as NumberFilterCode | TextFilterCode,
                        value: `${state[0].value}`
                    };
                }).reduce((o, v) => (o[v.name] = { value: v.value, code: v.code }, o), {} as typeof state.filters);
                if(!filters) return;
                if(JSON.stringify(state.filters) === JSON.stringify(filters)) return;
                state.filters = filters;

                state.gridInstance!.resetData([]);
                state.gridInstance!.readData(1);
                document.querySelectorAll('.tui-grid-filter-btn-clear').forEach(e => e.addEventListener('click', () => {
                    state.filters = {};
                    const { data, filterLayerState } = state.gridInstance!.store;
                    filterLayerState.activeFilterState = null;
                    filterLayerState.activeColumnAddress = null;
                    data.filters = null;
                    state.gridInstance!.readData(1);
                }));
                document.querySelectorAll('.tui-grid-filter-btn-apply').forEach(e => e.addEventListener('click', () => {
                    if(e.parentElement?.parentElement?.querySelector('input')?.value.length === 0) { // Element exists & value length 0
                        e.parentElement?.querySelector('.tui-grid-filter-btn-clear')?.dispatchEvent(new Event('click'));
                    }
                }));
            });
        })

        return {
            ...toRefs(state),
            grid
        }
    }
});
</script>

<style lang="scss">
@import 'tui-grid/dist/tui-grid.min.css';
@import 'tui-pagination/dist/tui-pagination.min.css';
</style>
