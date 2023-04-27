import Notification from "@typo3/backend/notification.js";
import { Translation } from "Translation";
const lol = document.getElementById('main');
console.log(Translation.translate('hey'));
Notification.info('Nearly there', 'You may head to the Page module to see what we did for you', 10, [
    {
        label: 'Go to module'
    }
]);
