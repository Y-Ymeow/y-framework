// Y-UX - 组件交互层
import UX from './core/base.js';
import Modal from './components/modal.js';
import Drawer from './components/drawer.js';
import Tabs from './components/tabs.js';
import Dropdown from './components/dropdown.js';
import Accordion from './components/accordion.js';
import Toast from './components/toast.js';
import RichEditor from './components/richEditor.js';
import { dataTable } from './components/dataTable.js';
import Chart from './components/chart.js';

const UXFramework = {
    ...UX,
    modal: Modal,
    drawer: Drawer,
    tabs: Tabs,
    dropdown: Dropdown,
    accordion: Accordion,
    toast: Toast,
    richEditor: RichEditor,
    dataTable,
    chart: Chart,

    init() {
        UX.registerSafeAttrs();
        Modal.init();
        Drawer.init();
        Tabs.init();
        Dropdown.init();
        Accordion.init();
        RichEditor.init();
        dataTable.init();
        Chart.init();

        if (window.L) this.hookLive(window.L);
        window.addEventListener('l:ready', (e) => this.hookLive(e.detail || window.L));
    },

    hookLive(L) {
        if (L._ux_hooked) return;
        L._ux_hooked = true;
        const originalExecute = L.executeOperation;
        L.executeOperation = (op) => {
            if (op.op && op.op.startsWith('ux:')) {
                const component = op.op.split(':')[1];
                if (component === 'modal') op.action === 'open' ? Modal.open(op.id) : Modal.close(op.id);
                else if (component === 'tabs') Tabs.select(op.id, op.tabId);
                else if (component === 'toast') Toast.show(op);
                else if (component === 'accordion') Accordion.toggle(op.id, op.open);
                else if (component === 'drawer') op.action === 'open' ? Drawer.open(op.id) : Drawer.close(op.id);
                else if (component === 'richEditor') RichEditor.exec(op.editorId, op.action);
                return;
            }
            return originalExecute.call(L, op);
        };
    }
};

window.UX = UXFramework;

document.addEventListener('DOMContentLoaded', () => UXFramework.init());

export default UXFramework;
export { Modal, Drawer, Tabs, Dropdown, Accordion, Toast, RichEditor, dataTable, Chart };
