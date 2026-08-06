import './bootstrap';

import Alpine from 'alpinejs';
import { registerLeadFollowUpAlerts } from './follow-up-alerts';
import { registerFollowUpDatetime } from './follow-up-datetime';
import { registerShellStore, registerShellComponents } from './stores/shell';

window.Alpine = Alpine;

registerShellStore();
registerShellComponents();
registerLeadFollowUpAlerts();
registerFollowUpDatetime();

Alpine.start();
