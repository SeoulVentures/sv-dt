import { Grid, GridEventName } from 'tui-grid';
import { GridEventProps, TuiGridEvent } from 'tui-grid/types/event';
import { GridEventListener, LifeCycleEventName } from 'tui-grid/types/options';
import { Store } from 'tui-grid/types/store';

declare module 'tui-grid' {
    export default class Grid extends Grid {
        public on(eventName: GridEventName | LifeCycleEventName, fn: (event: TuiGridEvent & GridEventProps) => void): void;

        public off(eventName: GridEventName | LifeCycleEventName, fn?: (event: TuiGridEvent & GridEventProps) => void): void;

        public store: Store;
    }
}
