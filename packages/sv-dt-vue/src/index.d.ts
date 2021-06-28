// declare module '@seoulventures/sv-dt' {
//     import type { DefineComponent } from 'vue'
//     const component: DefineComponent<{}, {}, any>
//     export default component
// }

import { FilterState } from 'tui-grid';

export declare class SvDataTable {
    filter: (columnName: string, state: FilterState) => void;
    unfilter: (columnName?: string) => void;
    reloadData: () => void;
}
