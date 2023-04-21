import {Semantilizer} from "./Semantilizer";

(window as any).instanceSemantilizer = (callback: (instance: Semantilizer) => any, ...args: any[]) => document.addEventListener('DOMContentLoaded', () => {
  // @ts-ignore
  const instance = new Semantilizer(...args);

  typeof callback === 'function' && callback(instance);
});
