import Notification from "@typo3/backend/notification.js";

declare global {
  interface Window { TYPO3: any; }
}

Notification.info('Nearly there', 'You may head to the Page module to see what we did for you', 10, [
  {
    label: 'Go to module'
  }
]);
