import './bootstrap';

import Alpine from 'alpinejs';
import { registerLeadFollowUpAlerts } from './follow-up-alerts';
import { registerFollowUpDatetime } from './follow-up-datetime';

window.Alpine = Alpine;

registerLeadFollowUpAlerts();
registerFollowUpDatetime();

Alpine.start();
