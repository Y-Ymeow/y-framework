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
import Tooltip from './components/tooltip.js';
import Popover from './components/popover.js';
import Rate from './components/rate.js';
import Slider from './components/slider.js';
import ColorPicker from './components/colorPicker.js';
import Collapse from './components/collapse.js';
import DatePicker from './components/datePicker.js';
import Carousel from './components/carousel.js';
import TagInput from './components/tagInput.js';
import Transfer from './components/transfer.js';
import TreeSelect from './components/treeSelect.js';
import QRCode from './components/qrcode.js';
import Calendar from './components/calendar.js';
import UXLiveBridge from './components/uxLiveBridge.js';
import DateRangePicker from './components/dateRangePicker.js';

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
    tooltip: Tooltip,
    popover: Popover,
    rate: Rate,
    slider: Slider,
    colorPicker: ColorPicker,
    collapse: Collapse,
    datePicker: DatePicker,
    carousel: Carousel,
    tagInput: TagInput,
    transfer: Transfer,
    treeSelect: TreeSelect,
    qrcode: QRCode,
    calendar: Calendar,
    bridge: UXLiveBridge,
    dateRangePicker: DateRangePicker,

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
        Tooltip.init();
        Popover.init();
        Rate.init();
        Slider.init();
        ColorPicker.init();
        Collapse.init();
        DatePicker.init();
        Carousel.init();
        TagInput.init();
        Transfer.init();
        TreeSelect.init();
        QRCode.init();
        Calendar.init();
        UXLiveBridge.init();
        DateRangePicker.init();

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
                else if (component === 'rate' && op.action === 'setValue') Rate.setValue(op.id, op.value);
                else if (component === 'slider' && op.action === 'setValue') Slider.setSliderValue(op.id, op.value);
                else if (component === 'colorPicker' && op.action === 'setColor') ColorPicker.setColor(op.id, op.value);
                else if (component === 'collapse') {
                    if (op.action === 'open') Collapse.openById(op.id);
                    else if (op.action === 'close') Collapse.closeById(op.id);
                    else if (op.action === 'toggle') Collapse.toggleById(op.id);
                }
                else if (component === 'carousel' && op.action === 'goTo') Carousel.goTo(document.getElementById(op.id), op.index);
                return;
            }
            return originalExecute.call(L, op);
        };
    }
};

window.UX = UXFramework;

document.addEventListener('DOMContentLoaded', () => UXFramework.init());

export default UXFramework;
export { Modal, Drawer, Tabs, Dropdown, Accordion, Toast, RichEditor, dataTable, Chart, Tooltip, Popover, Rate, Slider, ColorPicker, Collapse, DatePicker, Carousel, TagInput, Transfer, TreeSelect, QRCode };
